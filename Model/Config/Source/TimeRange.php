<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TimeRange implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => __('Last 1 Hour')],
            ['value' => '6', 'label' => __('Last 6 Hours')],
            ['value' => '12', 'label' => __('Last 12 Hours')],
            ['value' => '24', 'label' => __('Last 24 Hours')],
            ['value' => '48', 'label' => __('Last 2 Days')],
            ['value' => '168', 'label' => __('Last 7 Days')],
        ];
    }
}
