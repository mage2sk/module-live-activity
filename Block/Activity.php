<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
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
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Json
     */
    private Json $jsonSerializer;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @param Template\Context $context
     * @param Config $config
     * @param Json $jsonSerializer
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     * @param array $data
     */
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

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * Get configuration as JSON
     *
     * @return string
     */
    public function getConfigJson(): string
    {
        return $this->jsonSerializer->serialize($this->config->getFrontendConfig());
    }

    /**
     * Get AJAX URL
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('liveactivity/ajax/getactivity');
    }

    /**
     * Get current product ID if on product page
     *
     * @return int|null
     */
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

    /**
     * Get custom CSS from configuration
     *
     * @return string
     */
    public function getCustomCss(): string
    {
        return $this->config->getCustomCss();
    }
}
