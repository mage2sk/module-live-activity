<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Controller\Adminhtml\Product;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Shared doubles for the admin product picker endpoints.
 */
abstract class ProductControllerTestCase extends TestCase
{
    protected ?array $data = null;
    protected array $calls = [];
    protected array $logged = [];
    protected int $collectionsCreated = 0;

    protected function context(array $params): Context
    {
        $request = $this->createStub(HttpRequest::class);
        $request->method('getParam')->willReturnCallback(
            static fn($key, $default = null) => array_key_exists($key, $params) ? $params[$key] : $default
        );
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        return $context;
    }

    protected function jsonFactory(): JsonFactory
    {
        $json = $this->createStub(Json::class);
        $json->method('setData')->willReturnCallback(function ($data) use ($json) {
            $this->data = $data;
            return $json;
        });
        $factory = $this->createStub(JsonFactory::class);
        $factory->method('create')->willReturn($json);
        return $factory;
    }

    /**
     * @param Product[] $products
     */
    protected function collectionFactory(array $products, int $size = 0, ?\Exception $error = null): CollectionFactory
    {
        $collection = $this->createStub(Collection::class);
        foreach (['addAttributeToSelect', 'addFieldToFilter', 'addStoreFilter', 'addAttributeToFilter',
                     'setPageSize', 'setCurPage'] as $method) {
            $collection->method($method)->willReturnCallback(
                function (...$args) use ($collection, $method, $error) {
                    if ($error) {
                        throw $error;
                    }
                    $this->calls[] = [$method, $args];
                    return $collection;
                }
            );
        }
        $collection->method('getSize')->willReturn($size);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($products));

        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturnCallback(function () use ($collection) {
            $this->collectionsCreated++;
            return $collection;
        });
        return $factory;
    }

    protected function storeManager(): StoreManagerInterface
    {
        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(2);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        return $storeManager;
    }

    protected function logger(): LoggerInterface
    {
        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('error')->willReturnCallback(function ($message) {
            $this->logged[] = $message;
        });
        return $logger;
    }

    protected function product(string $id, string $name, string $sku, $price): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn($id);
        $product->method('getName')->willReturn($name);
        $product->method('getSku')->willReturn($sku);
        $product->method('getPrice')->willReturn($price);
        return $product;
    }

    protected function callsTo(string $method): array
    {
        return array_values(array_map(
            static fn(array $c) => $c[1],
            array_filter($this->calls, static fn(array $c) => $c[0] === $method)
        ));
    }
}
