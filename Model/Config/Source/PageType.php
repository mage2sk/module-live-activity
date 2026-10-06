<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PageType implements OptionSourceInterface
{
    public const PRODUCT = 'product';
    public const CATEGORY = 'category';
    public const HOME = 'home';
    public const SEARCH = 'search';
    public const CMS = 'cms';

    private const ACTION_MAP = [
        'catalog_product_view' => self::PRODUCT,
        'catalog_category_view' => self::CATEGORY,
        'cms_index_index' => self::HOME,
        'catalogsearch_result_index' => self::SEARCH,
        'catalogsearch_advanced_result' => self::SEARCH,
        'cms_page_view' => self::CMS,
    ];

    public function toOptionArray(): array
    {
        return [
            ['value' => self::PRODUCT, 'label' => __('Product pages')],
            ['value' => self::CATEGORY, 'label' => __('Category pages')],
            ['value' => self::HOME, 'label' => __('Home page')],
            ['value' => self::SEARCH, 'label' => __('Search results')],
            ['value' => self::CMS, 'label' => __('CMS pages')],
        ];
    }

    public static function fromFullActionName(string $fullActionName): string
    {
        return self::ACTION_MAP[strtolower($fullActionName)] ?? '';
    }
}
