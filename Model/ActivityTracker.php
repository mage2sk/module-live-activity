<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Model;

use Panth\LiveActivity\Model\ActivityFactory;
use Panth\LiveActivity\Model\ResourceModel\Activity as ActivityResource;
use Panth\LiveActivity\Helper\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

class ActivityTracker
{
    private $activityFactory;

    private $activityResource;

    private $config;

    private $storeManager;

    private $customerSession;

    private $logger;

    public function __construct(
        ActivityFactory $activityFactory,
        ActivityResource $activityResource,
        Config $config,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        LoggerInterface $logger
    ) {
        $this->activityFactory = $activityFactory;
        $this->activityResource = $activityResource;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    public function track(string $activityType, array $data): void
    {
        if (!$this->config->isEnabled() || !$this->config->getConfig(Config::XML_PATH_USE_REAL_DATA)) {
            return;
        }

        try {
            $activity = $this->activityFactory->create();
            $activity->setData([
                'activity_type' => $activityType,
                'product_id' => $data['product_id'] ?? null,
                'product_name' => $data['product_name'] ?? null,
                'customer_name' => $this->getCustomerName(),
                'customer_location' => $this->getCustomerLocation(),
                'store_id' => $this->storeManager->getStore()->getId(),
                'is_real' => 1
            ]);

            $this->activityResource->save($activity);
        } catch (\Exception $e) {
            $this->logger->error('Live Activity tracking error: ' . $e->getMessage());
        }
    }

    private function getCustomerName(): ?string
    {
        if (!$this->customerSession->isLoggedIn()) {
            return $this->generateAnonymousName();
        }

        $customer = $this->customerSession->getCustomer();
        $firstName = $customer->getFirstname();
        $lastName = $customer->getLastname();

        if ($this->config->getConfig(Config::XML_PATH_ANONYMIZE)) {
            return $firstName . ' ' . substr($lastName, 0, 1) . '.';
        }

        return $firstName . ' ' . $lastName;
    }

    private function generateAnonymousName(): string
    {
        $names = [
            'Alex', 'Jordan', 'Taylor', 'Morgan', 'Casey', 'Riley', 'Avery', 'Quinn',
            'Sam', 'Jamie', 'Dakota', 'Skylar', 'Parker', 'Rowan', 'Sage', 'Cameron'
        ];

        $initial = chr(rand(65, 90));
        return $names[array_rand($names)] . ' ' . $initial . '.';
    }

    private function getCustomerLocation(): ?string
    {
        $locations = $this->config->getFakeLocations();
        return $locations[array_rand($locations)];
    }
}
