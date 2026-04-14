<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Get products by comma-separated IDs
 * Used for loading existing selected products when editing config
 */
class GetByIds extends Action
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
            $ids = $this->getRequest()->getParam('ids', '');

            if (empty($ids)) {
                return $result->setData([
                    'success' => true,
                    'products' => []
                ]);
            }

            $idArray = array_map('intval', array_filter(explode(',', $ids)));

            if (empty($idArray)) {
                return $result->setData([
                    'success' => true,
                    'products' => []
                ]);
            }

            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'sku', 'price', 'type_id', 'status']);
            $collection->addFieldToFilter('entity_id', ['in' => $idArray]);
            $collection->addStoreFilter($this->storeManager->getStore()->getId());

            $products = [];
            foreach ($collection as $product) {
                $products[] = [
                    'id' => (int)$product->getId(),
                    'name' => $product->getName(),
                    'sku' => $product->getSku(),
                    'price' => number_format((float)$product->getPrice(), 2, '.', '')
                ];
            }

            return $result->setData([
                'success' => true,
                'products' => $products
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Product GetByIds error: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'error' => true,
                'message' => __('An error occurred while fetching products: %1', $e->getMessage())
            ]);
        }
    }
}
