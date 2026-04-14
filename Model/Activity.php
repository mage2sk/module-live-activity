<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Model;

use Magento\Framework\Model\AbstractModel;

class Activity extends AbstractModel
{
    const TYPE_PURCHASE = 'purchase';
    const TYPE_CART_ADD = 'cart_add';
    const TYPE_WISHLIST_ADD = 'wishlist_add';
    const TYPE_VIEW = 'view';
    const TYPE_LIVE_VIEWERS = 'live_viewers';
    const TYPE_TRENDING = 'trending';
    const TYPE_LOW_STOCK = 'low_stock';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Panth\LiveActivity\Model\ResourceModel\Activity::class);
    }
}
