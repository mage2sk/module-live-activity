<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Panth\LiveActivity\Block\Adminhtml\System\Config\FakeLocations;
use Panth\LiveActivity\Block\Adminhtml\System\Config\FakeNames;
use Panth\LiveActivity\Block\Adminhtml\System\Config\ProductPicker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The backend Field constructor needs the static object manager, so the blocks
 * are created without it and only their own data methods are exercised.
 */
class ConfigFieldsTest extends TestCase
{
    private function newBlock(string $class, $value = null)
    {
        $block = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        $element = new DataObject([
            'value' => $value,
            'name' => 'groups[data_source][fields][x][value]',
            'html_id' => 'live_activity_x',
        ]);
        $block->setData('element', $element);
        return $block;
    }

    public static function listFields(): array
    {
        return [
            'names' => [FakeNames::class, 'getDefaultNames', 'James D.'],
            'locations' => [FakeLocations::class, 'getDefaultLocations', 'New York'],
        ];
    }

    #[DataProvider('listFields')]
    public function testDefaultValueIsPrettyJsonOfDefaults(string $class, string $defaultsMethod, string $first): void
    {
        $block = $this->newBlock($class);
        $defaults = $block->$defaultsMethod();

        $this->assertCount(15, $defaults);
        $this->assertSame($first, $defaults[0]);
        $this->assertSame(json_encode($defaults, JSON_PRETTY_PRINT), $block->getDefaultValue());
        $this->assertSame($defaults, json_decode($block->getValue(), true));
    }

    #[DataProvider('listFields')]
    public function testStoredValueWinsOverDefault(string $class, string $defaultsMethod, string $first): void
    {
        $block = $this->newBlock($class, '["Only One"]');

        $this->assertNotContains($first, json_decode($block->getValue(), true));
        $this->assertNotSame($block->getDefaultValue(), $block->getValue());
        $this->assertContains($first, $block->$defaultsMethod());

        $this->assertSame('["Only One"]', $block->getValue());
        $this->assertSame('groups[data_source][fields][x][value]', $block->getElementName());
        $this->assertSame('live_activity_x', $block->getElementId());
    }

    public function testProductPickerValueAndElementAccessors(): void
    {
        $this->assertSame('', $this->newBlock(ProductPicker::class)->getElementValue());

        $block = $this->newBlock(ProductPicker::class, '4,5');
        $this->assertSame('4,5', $block->getElementValue());
        $this->assertSame('groups[data_source][fields][x][value]', $block->getElementName());
        $this->assertSame('live_activity_x', $block->getElementId());
    }

    public function testProductPickerEndpointsAndFormKey(): void
    {
        $block = $this->newBlock(ProductPicker::class);

        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(static fn($route) => 'https://admin.test/' . $route);
        (new \ReflectionProperty($block, '_urlBuilder'))->setValue($block, $url);

        $formKey = $this->createStub(FormKey::class);
        $formKey->method('getFormKey')->willReturn('fk123');
        (new \ReflectionProperty($block, 'formKey'))->setValue($block, $formKey);

        $this->assertSame('https://admin.test/liveactivity/product/search', $block->getSearchUrl());
        $this->assertSame('https://admin.test/liveactivity/product/getbyids', $block->getGetByIdsUrl());
        $this->assertSame('fk123', $block->getFormKey());
    }
}
