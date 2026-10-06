<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\LiveActivity\Model\Config\Source\Animation;
use Panth\LiveActivity\Model\Config\Source\Category;
use Panth\LiveActivity\Model\Config\Source\Position;
use Panth\LiveActivity\Model\Config\Source\TimeRange;
use PHPUnit\Framework\TestCase;

class SourceModelsTest extends TestCase
{
    private static function values(array $options): array
    {
        return array_map(static fn(array $o) => $o['value'], $options);
    }

    public function testAnimationOptions(): void
    {
        $options = (new Animation())->toOptionArray();
        $this->assertSame(['slide', 'fade', 'bounce', 'scale'], self::values($options));
        $this->assertSame('Fade In', (string)$options[1]['label']);
    }

    public function testPositionOptions(): void
    {
        $options = (new Position())->toOptionArray();
        $this->assertSame(['bottom-left', 'bottom-right', 'top-left', 'top-right'], self::values($options));
        $this->assertSame('Top Right', (string)$options[3]['label']);
    }

    public function testTimeRangeOptionsAreHoursAscending(): void
    {
        $options = (new TimeRange())->toOptionArray();
        $values = array_map('intval', self::values($options));
        $this->assertSame([1, 6, 12, 24, 48, 168], $values);
        $this->assertSame('Last 7 Days', (string)$options[5]['label']);
    }

    private function categorySource(array $categories, ?string &$pathFilter = null, bool $throw = false): Category
    {
        $collection = $this->createStub(Collection::class);
        foreach (['addAttributeToSelect', 'addIsActiveFilter', 'addOrderField'] as $method) {
            $collection->method($method)->willReturnSelf();
        }
        $collection->method('addFieldToFilter')->willReturnCallback(
            function ($field, $condition) use ($collection, &$pathFilter) {
                $pathFilter = $field . ':' . $condition['like'];
                return $collection;
            }
        );
        $collection->method('getIterator')->willReturn(new \ArrayIterator($categories));

        $factory = $this->createStub(CollectionFactory::class);
        if ($throw) {
            $factory->method('create')->willThrowException(new \RuntimeException('db down'));
        } else {
            $factory->method('create')->willReturn($collection);
        }

        $store = $this->createStub(Store::class);
        $store->method('getRootCategoryId')->willReturn(2);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new Category($factory, $storeManager);
    }

    private static function cat(int $id, string $name, int $level): DataObject
    {
        return new DataObject(['id' => $id, 'name' => $name, 'level' => $level, 'path' => '1/2/' . $id]);
    }

    public function testCategoryOptionsSkipRootsAndIndentByLevel(): void
    {
        $pathFilter = null;
        $source = $this->categorySource([
            self::cat(2, 'Default Category', 1),
            self::cat(3, 'Women', 2),
            self::cat(4, 'Tops', 3),
            self::cat(5, 'Jackets', 4),
        ], $pathFilter);

        $this->assertSame([
            ['value' => 3, 'label' => 'Women'],
            ['value' => 4, 'label' => '-- Tops'],
            ['value' => 5, 'label' => '---- Jackets'],
        ], $source->toOptionArray());
        $this->assertSame('path:1/2%', $pathFilter);
    }

    public function testCategoryOptionsEmptyCollection(): void
    {
        $this->assertSame([], $this->categorySource([])->toOptionArray());
    }

    public function testCategoryOptionsReturnEmptyOnError(): void
    {
        $this->assertSame([], $this->categorySource([], $unused, true)->toOptionArray());
    }
}
