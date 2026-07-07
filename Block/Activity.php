<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Panth\LiveActivity\Helper\Config;

class Activity extends Template
{
    private Config $config;

    private Json $jsonSerializer;

    private RequestInterface $request;

    private ProductRepositoryInterface $productRepository;

    public function __construct(
        Template\Context $context,
        Config $config,
        Json $jsonSerializer,
        RequestInterface $request,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->jsonSerializer = $jsonSerializer;
        $this->request = $request;
        $this->productRepository = $productRepository;
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function getConfigJson(): string
    {
        return $this->jsonSerializer->serialize($this->config->getFrontendConfig());
    }

    public function getAjaxUrl(): string
    {
        return $this->getUrl('liveactivity/ajax/getactivity');
    }

    public function getCurrentProductId(): ?int
    {
        $productId = (int)$this->request->getParam('id');
        if ($productId) {
            try {
                $this->productRepository->getById($productId);
                return $productId;
            } catch (NoSuchEntityException $e) {
                return null;
            }
        }
        return null;
    }

    public function getCustomCss(): string
    {
        return $this->config->getCustomCss();
    }
}
