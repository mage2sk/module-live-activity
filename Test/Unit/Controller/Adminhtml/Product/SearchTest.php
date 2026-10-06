<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Controller\Adminhtml\Product;

use Magento\Catalog\Model\Product\Visibility;
use Panth\LiveActivity\Controller\Adminhtml\Product\Search;

class SearchTest extends ProductControllerTestCase
{
    private function dispatch(array $params, array $products = [], int $size = 0, ?\Exception $error = null): void
    {
        $controller = new Search(
            $this->context($params),
            $this->jsonFactory(),
            $this->collectionFactory($products, $size, $error),
            $this->storeManager(),
            $this->logger()
        );
        $controller->execute();
    }

    public function testShortQueryReturnsEmptyPageWithoutQuerying(): void
    {
        $this->dispatch(['query' => 'a', 'page' => '3', 'limit' => '8']);

        $this->assertSame([
            'success' => true,
            'products' => [],
            'total' => 0,
            'page' => 3,
            'limit' => 8,
            'has_more' => false,
        ], $this->data);
        $this->assertSame(0, $this->collectionsCreated);
    }

    public function testMissingQueryUsesDefaultPaging(): void
    {
        $this->dispatch([]);

        $this->assertSame(1, $this->data['page']);
        $this->assertSame(5, $this->data['limit']);
        $this->assertSame([], $this->data['products']);
    }

    public function testResultsAreMappedAndPaged(): void
    {
        $this->dispatch(
            ['query' => 'tee', 'page' => '2', 'limit' => '2'],
            [$this->product('11', 'Blue Tee', 'TEE-B', '19.5'), $this->product('12', 'Red Tee', 'TEE-R', null)],
            7
        );

        $this->assertTrue($this->data['success']);
        $this->assertSame([
            ['id' => 11, 'name' => 'Blue Tee', 'sku' => 'TEE-B', 'price' => '19.50'],
            ['id' => 12, 'name' => 'Red Tee', 'sku' => 'TEE-R', 'price' => '0.00'],
        ], $this->data['products']);
        $this->assertSame(7, $this->data['total']);
        $this->assertTrue($this->data['has_more']);
        $this->assertSame([[2]], $this->callsTo('setPageSize'));
        $this->assertSame([[2]], $this->callsTo('setCurPage'));
        $this->assertSame([[2]], $this->callsTo('addStoreFilter'));
    }

    public function testLastPageHasNoMore(): void
    {
        $this->dispatch(['query' => 'tee', 'page' => '2', 'limit' => '5'], [], 10);

        $this->assertFalse($this->data['has_more']);
    }

    public function testFiltersOnVisibilityAndIdSkuOrName(): void
    {
        $this->dispatch(['query' => 'shoe'], [], 0);

        $filters = $this->callsTo('addAttributeToFilter');
        $this->assertSame([
            'visibility',
            ['in' => [
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_BOTH,
            ]],
        ], array_slice($filters[0], 0, 2));
        $this->assertSame([[
            ['attribute' => 'entity_id', 'eq' => 'shoe'],
            ['attribute' => 'sku', 'like' => '%shoe%'],
            ['attribute' => 'name', 'like' => '%shoe%'],
        ]], array_slice($filters[1], 0, 1));
    }

    public function testArrayQueryIsTreatedAsEmptyInsteadOfFailing(): void
    {
        $this->dispatch(['query' => ['tee', 'shirt'], 'page' => ['2'], 'limit' => ['9']]);

        $this->assertTrue($this->data['success']);
        $this->assertSame([], $this->data['products']);
        $this->assertSame(1, $this->data['page']);
        $this->assertSame(5, $this->data['limit']);
        $this->assertSame(0, $this->collectionsCreated);
    }

    public function testLimitAndPageAreClamped(): void
    {
        $this->dispatch(['query' => 'tee', 'page' => '-3', 'limit' => '100000'], [], 0);

        $this->assertSame(Search::MAX_LIMIT, $this->data['limit']);
        $this->assertSame(1, $this->data['page']);
        $this->assertSame([[Search::MAX_LIMIT]], $this->callsTo('setPageSize'));
        $this->assertSame([[1]], $this->callsTo('setCurPage'));
    }

    public function testErrorsAreLoggedAndReported(): void
    {
        $this->dispatch(['query' => 'tee'], [], 0, new \RuntimeException('index missing'));

        $this->assertFalse($this->data['success']);
        $this->assertTrue($this->data['error']);
        $this->assertSame('An error occurred while searching products: index missing', (string)$this->data['message']);
        $this->assertSame(['Product search error: index missing'], $this->logged);
    }
}
