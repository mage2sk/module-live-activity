<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Panth\LiveActivity\Helper\Config;
use PHPUnit\Framework\TestCase;

class ConfigPagesTest extends TestCase
{
    private function config(?string $pages): Config
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn(string $path) => $path === Config::XML_PATH_SHOW_ON_PAGES ? $pages : null
        );
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);
        return new Config($context);
    }

    public function testAllowedPageTypesParseCommaList(): void
    {
        $this->assertSame(['product', 'category'], $this->config('product, category,')->getAllowedPageTypes());
    }

    public function testAllowedPageTypesEmptyWhenUnsetOrBlank(): void
    {
        $this->assertSame([], $this->config(null)->getAllowedPageTypes());
        $this->assertSame([], $this->config('  ')->getAllowedPageTypes());
    }

    public function testProductOnlyAllowsProductPage(): void
    {
        $config = $this->config('product');
        $this->assertTrue($config->isPageAllowed('catalog_product_view'));
        $this->assertFalse($config->isPageAllowed('catalog_category_view'));
        $this->assertFalse($config->isPageAllowed('cms_index_index'));
    }

    public function testCartCheckoutAndAccountNeverAllowed(): void
    {
        $config = $this->config('product,category,home,search,cms,cart,checkout,account');
        $this->assertFalse($config->isPageAllowed('checkout_cart_index'));
        $this->assertFalse($config->isPageAllowed('checkout_index_index'));
        $this->assertFalse($config->isPageAllowed('customer_account_index'));
        $this->assertFalse($config->isPageAllowed('sales_order_view'));
        $this->assertFalse($config->isPageAllowed(''));
        $this->assertTrue($config->isPageAllowed('cms_page_view'));
    }

    public function testNothingAllowedWhenNoPagesSelected(): void
    {
        $this->assertFalse($this->config('')->isPageAllowed('catalog_product_view'));
    }
}
