<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Controller\Ajax;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Panth\LiveActivity\Model\ActivityProvider;
use Panth\LiveActivity\Helper\Config;

class GetActivity implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ActivityProvider
     */
    private $activityProvider;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param JsonFactory $jsonFactory
     * @param RequestInterface $request
     * @param ActivityProvider $activityProvider
     * @param Config $config
     */
    public function __construct(
        JsonFactory $jsonFactory,
        RequestInterface $request,
        ActivityProvider $activityProvider,
        Config $config
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->activityProvider = $activityProvider;
        $this->config = $config;
    }

    /**
     * Get activity data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->config->isEnabled()) {
            return $result->setData([
                'success' => false,
                'message' => 'Live Activity is disabled'
            ]);
        }

        $productId = $this->request->getParam('product_id');

        $data = [
            'success' => true,
            'activities' => $this->activityProvider->getRecentActivity($productId ? (int)$productId : null),
            'config' => $this->config->getFrontendConfig()
        ];

        if ($productId) {
            $data['stats'] = $this->activityProvider->getViewerStats((int)$productId);
        }

        return $result->setData($data);
    }
}
