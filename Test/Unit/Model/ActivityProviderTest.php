<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\LiveActivity\Helper\Config;
use Panth\LiveActivity\Model\Activity;
use Panth\LiveActivity\Model\ActivityProvider;
use Panth\LiveActivity\Model\ResourceModel\Activity\Collection as ActivityCollection;
use Panth\LiveActivity\Model\ResourceModel\Activity\CollectionFactory as ActivityCollectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ActivityProviderTest extends TestCase
{
    /** @var array<int,array> */
    private array $activityFilters = [];
    private array $whereCalls = [];
    private array $productFilters = [];
    private int $productCollectionsCreated = 0;

    private array $configValues = [];
    private array $activityRows = [];
    private array $products = [];
    private array $repositoryProducts = [];
    /** @var callable|null */
    private $imageUrl = null;
    private $stockQty = 0;

    private function provider(): ActivityProvider
    {
        $config = $this->createStub(Config::class);
        $config->method('getConfig')->willReturnCallback(fn(string $p) => $this->configValues[$p] ?? null);
        $config->method('anonymizeStoredName')->willReturnCallback(
            static fn(?string $n) => $n === null ? '' : 'anon:' . $n
        );
        $config->method('getEnabledFakeNames')->willReturn(['Fake N.']);
        $config->method('getFakeLocations')->willReturn(['Faketown']);
        $config->method('getFeaturedProductIds')->willReturnCallback(
            fn() => $this->configValues['featured'] ?? []
        );
        $config->method('getExcludedCategoryIds')->willReturnCallback(
            fn() => $this->configValues['excluded'] ?? []
        );

        $select = $this->createStub(Select::class);
        $select->method('where')->willReturnCallback(function ($cond, $value = null) use ($select) {
            $this->whereCalls[] = [$cond, $value];
            return $select;
        });
        $activityCollection = $this->createStub(ActivityCollection::class);
        $activityCollection->method('addFieldToFilter')->willReturnCallback(
            function ($field, $cond = null) use ($activityCollection) {
                $this->activityFilters[] = [$field, $cond];
                return $activityCollection;
            }
        );
        $activityCollection->method('getSelect')->willReturn($select);
        $activityCollection->method('addExpressionFieldToSelect')->willReturnSelf();
        $activityCollection->method('setOrder')->willReturnSelf();
        $activityCollection->method('setPageSize')->willReturnSelf();
        $activityCollection->method('getIterator')->willReturnCallback(
            fn() => new \ArrayIterator($this->activityRows)
        );
        $activityFactory = $this->createStub(ActivityCollectionFactory::class);
        $activityFactory->method('create')->willReturn($activityCollection);

        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $repository = $this->createStub(ProductRepositoryInterface::class);
        $repository->method('getById')->willReturnCallback(function ($id) {
            if (!isset($this->repositoryProducts[$id])) {
                throw new NoSuchEntityException(__('missing'));
            }
            return $this->repositoryProducts[$id];
        });

        $productCollection = $this->createStub(ProductCollection::class);
        foreach (['addAttributeToSelect', 'addStoreFilter', 'setVisibility', 'setPageSize'] as $m) {
            $productCollection->method($m)->willReturnSelf();
        }
        $productCollection->method('addAttributeToFilter')->willReturnCallback(
            function ($attr, $cond = null) use ($productCollection) {
                $this->productFilters[] = [$attr, $cond];
                return $productCollection;
            }
        );
        $productCollection->method('addCategoriesFilter')->willReturnCallback(
            function ($cond) use ($productCollection) {
                $this->productFilters[] = ['category', $cond];
                return $productCollection;
            }
        );
        $productCollection->method('getItems')->willReturnCallback(fn() => $this->products);
        $productFactory = $this->createStub(ProductCollectionFactory::class);
        $productFactory->method('create')->willReturnCallback(function () use ($productCollection) {
            $this->productCollectionsCreated++;
            return $productCollection;
        });

        $visibility = $this->createStub(Visibility::class);
        $visibility->method('getVisibleInSiteIds')->willReturn([2, 3, 4]);

        $stockState = $this->createStub(StockStateInterface::class);
        $stockState->method('getStockQty')->willReturnCallback(function () {
            if ($this->stockQty instanceof \Exception) {
                throw $this->stockQty;
            }
            return $this->stockQty;
        });

        $imageHelper = $this->createStub(ImageHelper::class);
        $imageHelper->method('init')->willReturnSelf();
        $imageHelper->method('setImageFile')->willReturnSelf();
        $imageHelper->method('resize')->willReturnSelf();
        $imageHelper->method('getUrl')->willReturnCallback(function () {
            return $this->imageUrl ? ($this->imageUrl)() : 'https://cdn.test/img.jpg';
        });

        return new ActivityProvider(
            $activityFactory,
            $config,
            $storeManager,
            $repository,
            $this->createStub(DateTime::class),
            $productFactory,
            $visibility,
            $stockState,
            $imageHelper
        );
    }

    private function product(int $id, array $data = []): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn($id);
        $product->method('getName')->willReturn('Product ' . $id);
        $product->method('getProductUrl')->willReturn('https://shop.test/p' . $id . '.html');
        $product->method('getData')->willReturnCallback(static fn($key = '') => $data[$key] ?? null);
        return $product;
    }

    private static function row(array $data): DataObject
    {
        return new DataObject($data + [
            'activity_type' => Activity::TYPE_PURCHASE,
            'product_name' => 'Stored name',
            'customer_name' => 'Jane Doe',
            'customer_location' => null,
            'is_real' => 1,
            'age_seconds' => 0,
        ]);
    }

    public function testNoSourcesEnabledReturnsNothing(): void
    {
        $this->assertSame([], $this->provider()->getRecentActivity());
    }

    public function testRealActivityIsFormattedAndAnonymized(): void
    {
        $this->configValues = [Config::XML_PATH_USE_REAL_DATA => '1'];
        $this->activityRows = [self::row(['age_seconds' => 120, 'customer_location' => 'Leeds'])];

        $result = $this->provider()->getRecentActivity();

        $this->assertCount(1, $result);
        $item = $result[0];
        $this->assertSame('purchase', $item['type']);
        $this->assertSame('Stored name', $item['product_name']);
        $this->assertSame('anon:Jane Doe', $item['customer_name']);
        $this->assertSame('Leeds', $item['customer_location']);
        $this->assertSame('2 minutes ago', $item['time_ago']);
        $this->assertTrue($item['is_real']);
        $this->assertEqualsWithDelta(time() - 120, $item['timestamp'], 2);
        $this->assertArrayNotHasKey('product_url', $item);
    }

    public function testRealActivityFiltersStoreTimeRangeAndEnabledTypes(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_REAL_DATA => '1',
            Config::XML_PATH_TIME_RANGE => '24',
            Config::XML_PATH_SHOW_PURCHASES => '1',
            Config::XML_PATH_SHOW_LOW_STOCK => '1',
        ];

        $this->provider()->getRecentActivity();

        $this->assertContains(['store_id', 1], $this->activityFilters);
        $this->assertContains(['activity_type', ['in' => ['purchase', 'low_stock']]], $this->activityFilters);
        $this->assertCount(1, $this->whereCalls);
        $this->assertSame(24, $this->whereCalls[0][1]);
    }

    public function testRealActivityWithoutTimeRangeOrTypesAddsNoExtraFilters(): void
    {
        $this->configValues = [Config::XML_PATH_USE_REAL_DATA => '1'];

        $this->provider()->getRecentActivity();

        $this->assertSame([], $this->whereCalls);
        $this->assertSame([['store_id', 1]], $this->activityFilters);
    }

    public function testProductIdRestrictsToProductOrGlobalRows(): void
    {
        $this->configValues = [Config::XML_PATH_USE_REAL_DATA => '1'];

        $this->provider()->getRecentActivity(55);

        $this->assertContains(
            [['product_id', 'product_id'], [['eq' => 55], ['null' => true]]],
            $this->activityFilters
        );
    }

    public function testRealActivityEnrichedWithProductData(): void
    {
        $this->configValues = [Config::XML_PATH_USE_REAL_DATA => '1'];
        $this->repositoryProducts = [8 => $this->product(8, ['image' => '/a/b.jpg'])];
        $this->activityRows = [self::row(['product_id' => 8])];

        $item = $this->provider()->getRecentActivity()[0];

        $this->assertSame(8, $item['product_id']);
        $this->assertSame('https://shop.test/p8.html', $item['product_url']);
        $this->assertSame('https://cdn.test/img.jpg', $item['product_image']);
    }

    public function testDeletedProductIsSkippedSilently(): void
    {
        $this->configValues = [Config::XML_PATH_USE_REAL_DATA => '1'];
        $this->activityRows = [self::row(['product_id' => 404])];

        $item = $this->provider()->getRecentActivity()[0];

        $this->assertArrayNotHasKey('product_id', $item);
        $this->assertArrayNotHasKey('product_url', $item);
    }

    public static function imageCases(): array
    {
        return [
            'image attribute' => [['image' => '/x.jpg'], null, 'https://cdn.test/img.jpg'],
            'falls back to small_image' => [
                ['image' => 'no_selection', 'small_image' => '/s.jpg'],
                null,
                'https://cdn.test/img.jpg',
            ],
            'no image at all' => [['image' => 'no_selection'], null, ''],
            'placeholder url hidden' => [['thumbnail' => '/t.jpg'], 'https://cdn.test/placeholder/x.jpg', ''],
            'helper error' => [['image' => '/x.jpg'], 'throw', ''],
        ];
    }

    #[DataProvider('imageCases')]
    public function testProductImageResolution(array $attributes, ?string $url, string $expected): void
    {
        $this->configValues = [Config::XML_PATH_USE_REAL_DATA => '1'];
        $this->repositoryProducts = [8 => $this->product(8, $attributes)];
        $this->activityRows = [self::row(['product_id' => 8])];
        if ($url === 'throw') {
            $this->imageUrl = static function () {
                throw new \RuntimeException('no file');
            };
        } elseif ($url !== null) {
            $this->imageUrl = static fn() => $url;
        }

        $this->assertSame($expected, $this->provider()->getRecentActivity()[0]['product_image']);
    }

    public static function ageCases(): array
    {
        return [
            'negative clamps to now' => [-50, 'just now'],
            'under a minute' => [59, 'just now'],
            'one minute singular' => [60, '1 minute ago'],
            'many minutes' => [59 * 60, '59 minutes ago'],
            'one hour singular' => [3600, '1 hour ago'],
            'hours floor' => [3 * 3600 + 1799, '3 hours ago'],
        ];
    }

    #[DataProvider('ageCases')]
    public function testTimeAgoWording(int $ageSeconds, string $expected): void
    {
        $this->configValues = [Config::XML_PATH_USE_REAL_DATA => '1'];
        $this->activityRows = [self::row(['age_seconds' => $ageSeconds])];

        $this->assertSame($expected, $this->provider()->getRecentActivity()[0]['time_ago']);
    }

    public function testActivitiesSortedNewestFirstAndCappedAtTwenty(): void
    {
        $this->configValues = [Config::XML_PATH_USE_REAL_DATA => '1'];
        $rows = [];
        foreach ([300, 10, 7200] as $age) {
            $rows[] = self::row(['age_seconds' => $age]);
        }
        for ($i = 0; $i < 25; $i++) {
            $rows[] = self::row(['age_seconds' => 9000 + $i]);
        }
        $this->activityRows = $rows;

        $result = $this->provider()->getRecentActivity();

        $this->assertCount(20, $result);
        $this->assertSame('just now', $result[0]['time_ago']);
        $this->assertSame('5 minutes ago', $result[1]['time_ago']);
        $this->assertSame('2 hours ago', $result[2]['time_ago']);
        $timestamps = array_column($result, 'timestamp');
        $sorted = $timestamps;
        rsort($sorted);
        $this->assertSame($sorted, $timestamps);
    }

    public function testSimulatedNeedsAtLeastOneEnabledType(): void
    {
        $this->configValues = [Config::XML_PATH_USE_SIMULATED => '1'];
        $this->assertSame([], $this->provider()->getRecentActivity());
        $this->assertSame(0, $this->productCollectionsCreated);
    }

    public function testSimulatedPurchasesUseFakeNamesLocationsAndProducts(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_PURCHASES => '1',
        ];
        $this->products = [];
        for ($i = 1; $i <= 10; $i++) {
            $this->products[] = $this->product($i, ['image' => '/i.jpg']);
        }

        $result = $this->provider()->getRecentActivity();

        $this->assertGreaterThanOrEqual(3, count($result));
        $this->assertLessThanOrEqual(7, count($result));
        foreach ($result as $item) {
            $this->assertSame('purchase', $item['type']);
            $this->assertFalse($item['is_real']);
            $this->assertSame('Fake N.', $item['customer_name']);
            $this->assertSame('Faketown', $item['customer_location']);
            $this->assertMatchesRegularExpression('/^\d+ (minute|hour)s? ago$/', $item['time_ago']);
            $this->assertArrayHasKey('product_id', $item);
            $this->assertSame('https://cdn.test/img.jpg', $item['product_image']);
            $this->assertLessThanOrEqual(time() - 300, $item['timestamp']);
            $this->assertGreaterThanOrEqual(time() - 180 * 60 - 2, $item['timestamp']);
        }
    }

    public function testSimulatedActivityOnProductPageUsesThatProduct(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_PURCHASES => '1',
        ];
        $this->products = array_map(fn($i) => $this->product($i), range(1, 10));
        $this->repositoryProducts = [42 => $this->product(42)];

        $result = $this->provider()->getRecentActivity(42);

        $this->assertNotEmpty($result);
        foreach ($result as $item) {
            $this->assertSame(42, $item['product_id']);
            $this->assertSame('Product 42', $item['product_name']);
        }
        $this->assertSame(0, $this->productCollectionsCreated);
    }

    public function testSimulatedActivityFallsBackToPoolWhenProductMissing(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_PURCHASES => '1',
        ];
        $this->products = array_map(fn($i) => $this->product($i), range(1, 10));

        $result = $this->provider()->getRecentActivity(99);

        $this->assertNotEmpty($result);
        $this->assertSame(1, $this->productCollectionsCreated);
        foreach ($result as $item) {
            $this->assertLessThanOrEqual(10, $item['product_id']);
        }
    }

    public function testSimulatedWithoutProductsEmitsNothing(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_TRENDING => '1',
            Config::XML_PATH_SHOW_PURCHASES => '1',
            Config::XML_PATH_SHOW_LOW_STOCK => '1',
        ];
        $this->stockQty = 3;

        $this->assertSame([], $this->provider()->getRecentActivity());
    }

    public function testSimulatedTrendingIsTiedToAProduct(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_TRENDING => '1',
        ];
        $this->products = array_map(fn($i) => $this->product($i), range(1, 10));

        $result = $this->provider()->getRecentActivity();

        $this->assertNotEmpty($result);
        foreach ($result as $item) {
            $this->assertSame('trending', $item['type']);
            $this->assertSame('hour', $item['time_period']);
            $this->assertGreaterThanOrEqual(30, $item['view_count']);
            $this->assertLessThanOrEqual(150, $item['view_count']);
            $this->assertArrayHasKey('product_id', $item);
            $this->assertArrayHasKey('product_url', $item);
        }
    }

    public function testSimulatedEntriesNeverExceedTheProductPool(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_CART_ADDS => '1',
        ];
        $this->products = [$this->product(1), $this->product(2)];

        for ($run = 0; $run < 5; $run++) {
            $result = $this->provider()->getRecentActivity();
            $this->assertLessThanOrEqual(2, count($result));
            foreach ($result as $item) {
                $this->assertContains($item['product_id'], [1, 2]);
                $this->assertNotSame('', $item['product_name']);
            }
        }
    }

    public function testSimulatedLiveViewersCountRange(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_VIEWERS => '1',
        ];
        $this->products = [$this->product(1)];

        $result = $this->provider()->getRecentActivity();

        foreach ($result as $item) {
            $this->assertSame('live_viewers', $item['type']);
            $this->assertGreaterThanOrEqual(3, $item['viewer_count']);
            $this->assertLessThanOrEqual(25, $item['viewer_count']);
        }
        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['product_id']);
    }

    public function testSimulatedLowStockUsesRealQuantityWhenLow(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_LOW_STOCK => '1',
        ];
        $this->products = array_map(fn($i) => $this->product($i), range(1, 8));
        $this->stockQty = 8;

        $result = $this->provider()->getRecentActivity();
        $this->assertNotEmpty($result);
        foreach ($result as $item) {
            $this->assertSame(8, $item['stock_qty']);
            $this->assertArrayHasKey('product_id', $item);
        }
    }

    public static function nonLowStockProvider(): array
    {
        return [
            'plenty of stock' => [500],
            'eleven' => [11],
            'zero' => [0],
            'fraction below one' => [0.5],
            'negative' => [-3],
        ];
    }

    #[DataProvider('nonLowStockProvider')]
    public function testSimulatedLowStockSkippedUnlessRealQuantityIsLow($qty): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_LOW_STOCK => '1',
        ];
        $this->products = array_map(fn($i) => $this->product($i), range(1, 8));
        $this->stockQty = $qty;

        $this->assertSame([], $this->provider()->getRecentActivity());
    }

    public function testSimulatedLowStockSkippedWhenStockLookupFails(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_LOW_STOCK => '1',
        ];
        $this->products = array_map(fn($i) => $this->product($i), range(1, 8));
        $this->stockQty = new \RuntimeException('no stock item');

        $this->assertSame([], $this->provider()->getRecentActivity());
    }

    public function testSimulatedLowStockBoundaryQuantities(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_LOW_STOCK => '1',
        ];
        $this->products = array_map(fn($i) => $this->product($i), range(1, 8));

        foreach ([1 => 1, 10 => 10, '4.0000' => 4] as $qty => $expected) {
            $this->stockQty = $qty;
            $result = $this->provider()->getRecentActivity();
            $this->assertNotEmpty($result);
            $this->assertSame([$expected], array_values(array_unique(array_column($result, 'stock_qty'))));
        }
    }

    public function testProductPoolIsLoadedOncePerProvider(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_CART_ADDS => '1',
        ];
        $this->products = [$this->product(1), $this->product(2)];
        $provider = $this->provider();

        $provider->getRecentActivity();
        $provider->getRecentActivity();

        $this->assertSame(1, $this->productCollectionsCreated);
    }

    public function testProductPoolHonoursFeaturedAndExcludedCategories(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_CART_ADDS => '1',
            'featured' => [4, 5],
            'excluded' => [9],
        ];

        $this->provider()->getRecentActivity();

        $this->assertContains(['status', 1], $this->productFilters);
        $this->assertContains(['category', ['nin' => [9]]], $this->productFilters);
        $this->assertContains(['entity_id', ['in' => [4, 5]]], $this->productFilters);
    }

    public function testMixedSourcesAreMerged(): void
    {
        $this->configValues = [
            Config::XML_PATH_USE_REAL_DATA => '1',
            Config::XML_PATH_USE_SIMULATED => '1',
            Config::XML_PATH_SHOW_PURCHASES => '1',
        ];
        $this->products = array_map(fn($i) => $this->product($i), range(1, 10));
        $this->activityRows = [self::row([])];

        $result = $this->provider()->getRecentActivity();

        $this->assertTrue($result[0]['is_real'], 'real row (just now) sorts first');
        $this->assertGreaterThanOrEqual(4, count($result));
        $this->assertCount(1, array_filter($result, static fn($i) => $i['is_real']));
    }

    public function testViewerStatsAreEmptyWhenSimulatedDataIsOff(): void
    {
        $this->configValues = [Config::XML_PATH_USE_REAL_DATA => '1'];

        $this->assertSame([], $this->provider()->getViewerStats(10));
    }

    public function testViewerStatsAreWithinDocumentedRanges(): void
    {
        $this->configValues = [Config::XML_PATH_USE_SIMULATED => '1'];
        $stats = $this->provider()->getViewerStats(10);

        $this->assertTrue($stats['simulated']);
        unset($stats['simulated']);
        $this->assertSame(['current_viewers', 'views_today', 'cart_adds_today', 'purchases_today'], array_keys($stats));
        $this->assertGreaterThanOrEqual(3, $stats['current_viewers']);
        $this->assertLessThanOrEqual(25, $stats['current_viewers']);
        $this->assertGreaterThanOrEqual(50, $stats['views_today']);
        $this->assertLessThanOrEqual(200, $stats['views_today']);
        $this->assertGreaterThanOrEqual(5, $stats['cart_adds_today']);
        $this->assertLessThanOrEqual(30, $stats['cart_adds_today']);
        $this->assertGreaterThanOrEqual(1, $stats['purchases_today']);
        $this->assertLessThanOrEqual(10, $stats['purchases_today']);
    }
}
