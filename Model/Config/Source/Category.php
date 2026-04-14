<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Custom Category Source Model - Shows all categories in hierarchical tree
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Category as CategoryModel;

class Category implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param CollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Return array of categories as value-label pairs
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];

        try {
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect('name')
                ->addAttributeToSelect('level')
                ->addAttributeToSelect('path')
                ->addIsActiveFilter()
                ->addOrderField('path');

            // Get current store's root category ID
            $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();

            // Filter to only include categories under the current store's root
            $collection->addFieldToFilter('path', ['like' => "1/{$rootCategoryId}%"]);

            $categoryById = [];
            foreach ($collection as $category) {
                $categoryById[$category->getId()] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'level' => $category->getLevel(),
                    'path' => $category->getPath()
                ];
            }

            // Build hierarchical options with indentation
            foreach ($categoryById as $categoryData) {
                // Skip root categories (level 0 and 1)
                if ($categoryData['level'] <= 1) {
                    continue;
                }

                // Calculate indentation based on level
                // Level 2 = no indent (store root), Level 3 = --, Level 4 = ----, etc.
                $indent = str_repeat('--', max(0, $categoryData['level'] - 2));
                $label = $indent ? $indent . ' ' . $categoryData['name'] : $categoryData['name'];

                $options[] = [
                    'value' => $categoryData['id'],
                    'label' => $label
                ];
            }
        } catch (\Exception $e) {
            // If there's an error, return empty array
            // In production, you might want to log this
            return [];
        }

        return $options;
    }
}
