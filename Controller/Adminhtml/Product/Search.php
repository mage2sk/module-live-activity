<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Product search for admin configuration
 */
class Search extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Panth_LiveActivity::config';

    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $productCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Execute action
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        try {
            $query = $this->getRequest()->getParam('query', '');
            $page = (int)$this->getRequest()->getParam('page', 1);
            $limit = (int)$this->getRequest()->getParam('limit', 5);

            if (strlen($query) < 2) {
                return $result->setData([
                    'success' => true,
                    'products' => [],
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'has_more' => false
                ]);
            }

            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'sku', 'price', 'type_id', 'status']);
            $collection->addStoreFilter($this->storeManager->getStore()->getId());

            $collection->addAttributeToFilter('visibility', [
                'in' => [
                    Visibility::VISIBILITY_IN_CATALOG,
                    Visibility::VISIBILITY_IN_SEARCH,
                    Visibility::VISIBILITY_BOTH
                ]
            ]);

            $collection->addAttributeToFilter([
                ['attribute' => 'entity_id', 'eq' => $query],
                ['attribute' => 'sku', 'like' => '%' . $query . '%'],
                ['attribute' => 'name', 'like' => '%' . $query . '%']
            ]);

            $totalCount = $collection->getSize();

            $collection->setPageSize($limit);
            $collection->setCurPage($page);

            $products = [];
            foreach ($collection as $product) {
                $products[] = [
                    'id' => (int)$product->getId(),
                    'name' => $product->getName(),
                    'sku' => $product->getSku(),
                    'price' => number_format((float)$product->getPrice(), 2, '.', '')
                ];
            }

            $hasMore = ($page * $limit) < $totalCount;

            return $result->setData([
                'success' => true,
                'products' => $products,
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'has_more' => $hasMore
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Product search error: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'error' => true,
                'message' => __('An error occurred while searching products: %1', $e->getMessage())
            ]);
        }
    }
}
