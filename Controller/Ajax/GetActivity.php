<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Controller\Ajax;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Panth\LiveActivity\Model\ActivityProvider;
use Panth\LiveActivity\Helper\Config;

class GetActivity implements HttpGetActionInterface
{
    private $jsonFactory;

    private $request;

    private $activityProvider;

    private $config;

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
