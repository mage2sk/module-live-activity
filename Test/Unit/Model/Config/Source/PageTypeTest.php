<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Test\Unit\Model\Config\Source;

use Panth\LiveActivity\Model\Config\Source\PageType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PageTypeTest extends TestCase
{
    public function testOptionsListOnlyBrowsingPages(): void
    {
        $values = array_column((new PageType())->toOptionArray(), 'value');
        $this->assertSame(['product', 'category', 'home', 'search', 'cms'], $values);
    }

    public static function actionProvider(): array
    {
        return [
            'product' => ['catalog_product_view', 'product'],
            'category' => ['catalog_category_view', 'category'],
            'home' => ['cms_index_index', 'home'],
            'search' => ['catalogsearch_result_index', 'search'],
            'advanced search' => ['catalogsearch_advanced_result', 'search'],
            'cms' => ['cms_page_view', 'cms'],
            'upper case' => ['CATALOG_PRODUCT_VIEW', 'product'],
            'cart' => ['checkout_cart_index', ''],
            'checkout' => ['checkout_index_index', ''],
            'success' => ['checkout_onepage_success', ''],
            'account' => ['customer_account_index', ''],
            'login' => ['customer_account_login', ''],
            'orders' => ['sales_order_history', ''],
            'empty' => ['', ''],
        ];
    }

    #[DataProvider('actionProvider')]
    public function testFullActionNameMapsToPageType(string $action, string $expected): void
    {
        $this->assertSame($expected, PageType::fromFullActionName($action));
    }
}
