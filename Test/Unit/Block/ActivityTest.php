<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Block;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Panth\LiveActivity\Block\Activity;
use Panth\LiveActivity\Helper\Config;
use PHPUnit\Framework\TestCase;

class ActivityTest extends TestCase
{
    private array $lookups = [];

    private function block($idParam = null, array $existing = [], string $css = '', bool $enabled = true): Activity
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(static fn($route) => 'https://shop.test/' . $route);
        $context = $this->createStub(Context::class);
        $context->method('getUrlBuilder')->willReturn($url);

        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($enabled);
        $config->method('getFrontendConfig')->willReturn(['position' => 'top-left', 'interval' => 5000]);
        $config->method('getCustomCss')->willReturn($css);

        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(static fn($key) => $key === 'id' ? $idParam : null);

        $repository = $this->createStub(ProductRepositoryInterface::class);
        $repository->method('getById')->willReturnCallback(function ($id) use ($existing) {
            $this->lookups[] = $id;
            if (!in_array($id, $existing, true)) {
                throw new NoSuchEntityException(__('nope'));
            }
            return $this->createStub(ProductInterface::class);
        });

        return new Activity($context, $config, new Json(), $request, $repository);
    }

    public function testIsEnabledDelegatesToConfig(): void
    {
        $this->assertTrue($this->block()->isEnabled());
        $this->assertFalse($this->block(null, [], '', false)->isEnabled());
    }

    public function testConfigJsonSerializesFrontendConfig(): void
    {
        $this->assertSame('{"position":"top-left","interval":5000}', $this->block()->getConfigJson());
    }

    public function testAjaxUrlRoute(): void
    {
        $this->assertSame('https://shop.test/liveactivity/ajax/getactivity', $this->block()->getAjaxUrl());
    }

    public function testCurrentProductIdForExistingProduct(): void
    {
        $this->assertSame(17, $this->block('17', [17])->getCurrentProductId());
        $this->assertSame([17], $this->lookups);
    }

    public function testCurrentProductIdNullForUnknownProduct(): void
    {
        $this->assertNull($this->block('99', [17])->getCurrentProductId());
    }

    public function testCurrentProductIdNullWithoutParamSkipsLookup(): void
    {
        $this->assertNull($this->block(null)->getCurrentProductId());
        $this->assertNull($this->block('abc')->getCurrentProductId());
        $this->assertSame([], $this->lookups);
    }

    public function testCustomCssCannotCloseTheStyleTag(): void
    {
        $css = '.a{color:red}</style><script>alert(1)</script>';
        $result = $this->block(null, [], $css)->getCustomCss();

        $this->assertStringNotContainsString('<', $result);
        $this->assertSame('.a{color:red}\\3C /style>\\3C script>alert(1)\\3C /script>', $result);
    }

    public function testCustomCssPlainValueUnchanged(): void
    {
        $this->assertSame('.x{margin:0}', $this->block(null, [], '.x{margin:0}')->getCustomCss());
    }
}
