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

class TrackCartAdd implements ObserverInterface
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
        $product = $observer->getEvent()->getProduct();

        if (!$product) {
            return;
        }

        $this->activityTracker->track(Activity::TYPE_CART_ADD, [
            'product_id' => $product->getId(),
            'product_name' => $product->getName()
        ]);
    }
}
