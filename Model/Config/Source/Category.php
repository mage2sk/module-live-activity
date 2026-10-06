<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Category as CategoryModel;

class Category implements OptionSourceInterface
{
    private $categoryCollectionFactory;

    private $storeManager;

    public function __construct(
        CollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
    }

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

            $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();

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

            foreach ($categoryById as $categoryData) {
                if ($categoryData['level'] <= 1) {
                    continue;
                }

                $indent = str_repeat('--', max(0, $categoryData['level'] - 2));
                $label = $indent ? $indent . ' ' . $categoryData['name'] : $categoryData['name'];

                $options[] = [
                    'value' => $categoryData['id'],
                    'label' => $label
                ];
            }
        } catch (\Exception $e) {
            return [];
        }

        return $options;
    }
}
