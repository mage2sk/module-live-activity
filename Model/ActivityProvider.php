<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Model;

use Panth\LiveActivity\Model\ResourceModel\Activity\CollectionFactory;
use Panth\LiveActivity\Helper\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Catalog\Helper\Image as ImageHelper;

class ActivityProvider
{
    private $activityCollectionFactory;

    private $config;

    private $storeManager;

    private $productRepository;

    private $dateTime;

    private $productCollectionFactory;

    private $productVisibility;

    private $stockState;

    private $imageHelper;

    private $cachedProducts = null;

    public function __construct(
        CollectionFactory $activityCollectionFactory,
        Config $config,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        DateTime $dateTime,
        ProductCollectionFactory $productCollectionFactory,
        Visibility $productVisibility,
        StockStateInterface $stockState,
        ImageHelper $imageHelper
    ) {
        $this->activityCollectionFactory = $activityCollectionFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->dateTime = $dateTime;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productVisibility = $productVisibility;
        $this->stockState = $stockState;
        $this->imageHelper = $imageHelper;
    }

    public function getRecentActivity(?int $productId = null): array
    {
        $activities = [];

        $useRealData = (bool)$this->config->getConfig(Config::XML_PATH_USE_REAL_DATA);
        if ($useRealData) {
            $activities = array_merge($activities, $this->getRealActivity($productId));
        }

        $useSimulated = (bool)$this->config->getConfig(Config::XML_PATH_USE_SIMULATED);
        if ($useSimulated) {
            $activities = array_merge($activities, $this->getSimulatedActivity($productId));
        }

        usort($activities, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        $max = 20;
        return array_slice($activities, 0, $max);
    }

    private function getRealActivity(?int $productId): array
    {
        $collection = $this->activityCollectionFactory->create();
        $collection->addFieldToFilter('store_id', $this->storeManager->getStore()->getId());

        $timeRangeHours = (int)$this->config->getConfig(Config::XML_PATH_TIME_RANGE);
        if ($timeRangeHours > 0) {
            $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$timeRangeHours} hours"));
            $collection->addFieldToFilter('created_at', ['gteq' => $cutoffTime]);
        }

        $enabledTypes = $this->getEnabledActivityTypes();
        if (!empty($enabledTypes)) {
            $collection->addFieldToFilter('activity_type', ['in' => $enabledTypes]);
        }

        if ($productId) {
            $collection->addFieldToFilter(
                ['product_id', 'product_id'],
                [
                    ['eq' => $productId],
                    ['null' => true]
                ]
            );
        }

        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize(10);

        $activities = [];
        foreach ($collection as $activity) {
            $activities[] = $this->formatActivity($activity);
        }

        return $activities;
    }

    private function getSimulatedActivity(?int $productId): array
    {
        $activities = [];
        $count = rand(3, 7);

        $types = $this->getEnabledActivityTypes();

        if (empty($types)) {
            return [];
        }

        $products = $this->getRandomProducts($count);

        for ($i = 0; $i < $count; $i++) {
            $type = $types[array_rand($types)];
            $minutesAgo = rand(5, 180);
            $product = $products[$i] ?? null;

            $activity = [
                'type' => $type,
                'timestamp' => time() - ($minutesAgo * 60),
                'is_real' => false
            ];

            switch ($type) {
                case Activity::TYPE_PURCHASE:
                case Activity::TYPE_CART_ADD:
                case Activity::TYPE_WISHLIST_ADD:
                    $activity['customer_name'] = $this->generateName();
                    $activity['customer_location'] = $this->generateLocation();
                    $activity['time_ago'] = $this->getTimeAgo($minutesAgo);
                    if ($product) {
                        $activity['product_id'] = $product->getId();
                        $activity['product_name'] = $product->getName();
                        $activity['product_url'] = $product->getProductUrl();
                        $activity['product_image'] = $this->getProductImage($product);
                    }
                    break;

                case Activity::TYPE_LIVE_VIEWERS:
                    $activity['viewer_count'] = rand(3, 25);
                    if ($product) {
                        $activity['product_id'] = $product->getId();
                        $activity['product_name'] = $product->getName();
                        $activity['product_url'] = $product->getProductUrl();
                        $activity['product_image'] = $this->getProductImage($product);
                    }
                    break;

                case Activity::TYPE_TRENDING:
                    $activity['view_count'] = rand(30, 150);
                    $activity['time_period'] = 'hour';
                    if ($product) {
                        $activity['product_id'] = $product->getId();
                        $activity['product_name'] = $product->getName();
                        $activity['product_url'] = $product->getProductUrl();
                        $activity['product_image'] = $this->getProductImage($product);
                    }
                    break;

                case Activity::TYPE_LOW_STOCK:
                    $activity['stock_qty'] = rand(1, 5);
                    if ($product) {
                        $activity['product_id'] = $product->getId();
                        $activity['product_name'] = $product->getName();
                        $activity['product_url'] = $product->getProductUrl();
                        $activity['product_image'] = $this->getProductImage($product);

                        try {
                            $stockQty = $this->stockState->getStockQty($product->getId());
                            if ($stockQty > 0 && $stockQty <= 10) {
                                $activity['stock_qty'] = (int)$stockQty;
                            }
                        } catch (\Exception $e) {
                        }
                    }
                    break;
            }

            $activities[] = $activity;
        }

        return $activities;
    }

    private function getEnabledActivityTypes(): array
    {
        $types = [];

        if ((bool)$this->config->getConfig(Config::XML_PATH_SHOW_PURCHASES)) {
            $types[] = Activity::TYPE_PURCHASE;
        }
        if ((bool)$this->config->getConfig(Config::XML_PATH_SHOW_CART_ADDS)) {
            $types[] = Activity::TYPE_CART_ADD;
        }
        if ((bool)$this->config->getConfig(Config::XML_PATH_SHOW_WISHLIST)) {
            $types[] = Activity::TYPE_WISHLIST_ADD;
        }
        if ((bool)$this->config->getConfig(Config::XML_PATH_SHOW_VIEWERS)) {
            $types[] = Activity::TYPE_LIVE_VIEWERS;
        }
        if ((bool)$this->config->getConfig(Config::XML_PATH_SHOW_TRENDING)) {
            $types[] = Activity::TYPE_TRENDING;
        }
        if ((bool)$this->config->getConfig(Config::XML_PATH_SHOW_LOW_STOCK)) {
            $types[] = Activity::TYPE_LOW_STOCK;
        }

        return $types;
    }

    private function formatActivity($activity): array
    {
        $createdAt = strtotime($activity->getCreatedAt());

        $data = [
            'type' => $activity->getActivityType(),
            'product_name' => $activity->getProductName(),
            'customer_name' => $activity->getCustomerName(),
            'customer_location' => $activity->getCustomerLocation(),
            'time_ago' => $this->getTimeAgo((time() - $createdAt) / 60),
            'timestamp' => $createdAt,
            'is_real' => (bool)$activity->getIsReal()
        ];

        if ($activity->getProductId()) {
            try {
                $product = $this->productRepository->getById($activity->getProductId());
                $data['product_id'] = $product->getId();
                $data['product_url'] = $product->getProductUrl();

                $data['product_image'] = $this->getProductImage($product);
            } catch (\Exception $e) {
            }
        }

        return $data;
    }

    private function getProductImage($product): string
    {
        $imageFile = null;
        foreach (['image', 'small_image', 'thumbnail'] as $attr) {
            $val = $product->getData($attr);
            if ($val && $val !== 'no_selection') {
                $imageFile = $val;
                break;
            }
        }
        if (!$imageFile) {
            return '';
        }
        try {
            $url = $this->imageHelper->init($product, 'product_small_image')
                ->setImageFile($imageFile)
                ->resize(90, 90)
                ->getUrl();
            return (strpos($url, 'placeholder') === false) ? $url : '';
        } catch (\Exception $e) {
            return '';
        }
    }

    private function getTimeAgo($minutes): string
    {
        $minutes = (int)$minutes;
        if ($minutes < 1) {
            return 'just now';
        } elseif ($minutes < 60) {
            return $minutes . ' minute' . ($minutes != 1 ? 's' : '') . ' ago';
        } else {
            $hours = (int)floor($minutes / 60);
            return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
        }
    }

    private function generateName(): string
    {
        $names = $this->config->getEnabledFakeNames();

        if (empty($names)) {
            $names = [
                'James D.', 'John M.', 'Mary K.', 'Jennifer L.',
                'Alex K.', 'Jordan M.', 'Michael S.', 'Sarah J.',
                'Taylor S.', 'Morgan R.'
            ];
        }

        return $names[array_rand($names)];
    }

    private function generateLocation(): string
    {
        $locations = $this->config->getFakeLocations();
        return $locations[array_rand($locations)];
    }

    private function getRandomProducts(int $limit = 10): array
    {
        if ($this->cachedProducts !== null) {
            shuffle($this->cachedProducts);
            return array_slice($this->cachedProducts, 0, $limit);
        }

        $featuredIds = $this->config->getFeaturedProductIds();

        $excludedCategoryIds = $this->config->getExcludedCategoryIds();

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'url_key', 'url_path'])
            ->addStoreFilter($this->storeManager->getStore()->getId())
            ->addAttributeToFilter('status', 1)
            ->setVisibility($this->productVisibility->getVisibleInSiteIds());

        if (!empty($excludedCategoryIds)) {
            $collection->addCategoriesFilter(['nin' => $excludedCategoryIds]);
        }

        if (!empty($featuredIds)) {
            $collection->addAttributeToFilter('entity_id', ['in' => $featuredIds]);
        } else {
            $collection->setPageSize(50);
        }

        $products = $collection->getItems();

        $this->cachedProducts = $products;

        shuffle($products);
        return array_slice($products, 0, $limit);
    }

    public function getViewerStats(int $productId): array
    {
        return [
            'current_viewers' => rand(3, 25),
            'views_today' => rand(50, 200),
            'cart_adds_today' => rand(5, 30),
            'purchases_today' => rand(1, 10)
        ];
    }
}
