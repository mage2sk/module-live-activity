<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Controller\Adminhtml\Product;

use Panth\LiveActivity\Controller\Adminhtml\Product\GetByIds;

class GetByIdsTest extends ProductControllerTestCase
{
    private function dispatch(array $params, array $products = [], ?\Exception $error = null): void
    {
        $controller = new GetByIds(
            $this->context($params),
            $this->jsonFactory(),
            $this->collectionFactory($products, 0, $error),
            $this->storeManager(),
            $this->logger()
        );
        $controller->execute();
    }

    public function testMissingIdsReturnsEmptyList(): void
    {
        $this->dispatch([]);

        $this->assertSame(['success' => true, 'products' => []], $this->data);
        $this->assertSame(0, $this->collectionsCreated);
    }

    public function testOnlySeparatorsReturnsEmptyList(): void
    {
        $this->dispatch(['ids' => ',,']);

        $this->assertSame(['success' => true, 'products' => []], $this->data);
        $this->assertSame(0, $this->collectionsCreated);
    }

    public function testIdsAreCastAndProductsMapped(): void
    {
        $this->dispatch(
            ['ids' => '3,,8'],
            [$this->product('3', 'Mug', 'MUG', 4), $this->product('8', 'Pen', 'PEN', '1234.567')]
        );

        $this->assertSame([
            'success' => true,
            'products' => [
                ['id' => 3, 'name' => 'Mug', 'sku' => 'MUG', 'price' => '4.00'],
                ['id' => 8, 'name' => 'Pen', 'sku' => 'PEN', 'price' => '1234.57'],
            ],
        ], $this->data);
        $this->assertSame([['entity_id', ['in' => [0 => 3, 2 => 8]]]], $this->callsTo('addFieldToFilter'));
        $this->assertSame([[2]], $this->callsTo('addStoreFilter'));
    }

    public function testErrorsAreLoggedAndReported(): void
    {
        $this->dispatch(['ids' => '1'], [], new \RuntimeException('boom'));

        $this->assertFalse($this->data['success']);
        $this->assertTrue($this->data['error']);
        $this->assertSame('An error occurred while fetching products: boom', (string)$this->data['message']);
        $this->assertSame(['Product GetByIds error: boom'], $this->logged);
    }
}
