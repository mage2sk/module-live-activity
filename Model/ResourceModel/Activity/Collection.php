<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Model\ResourceModel\Activity;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Panth\LiveActivity\Model\Activity::class,
            \Panth\LiveActivity\Model\ResourceModel\Activity::class
        );
    }
}
