<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Panth\LiveActivity\Model\ActivityTracker;
use Panth\LiveActivity\Model\Activity;

class TrackOrderPlacement implements ObserverInterface
{
    /**
     * @var ActivityTracker
     */
    private $activityTracker;

    /**
     * @param ActivityTracker $activityTracker
     */
    public function __construct(ActivityTracker $activityTracker)
    {
        $this->activityTracker = $activityTracker;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (!$order) {
            return;
        }

        // Track each product in the order
        foreach ($order->getAllVisibleItems() as $item) {
            $this->activityTracker->track(Activity::TYPE_PURCHASE, [
                'product_id' => $item->getProductId(),
                'product_name' => $item->getName()
            ]);
        }
    }
}
