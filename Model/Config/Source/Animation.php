<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Animation implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'slide', 'label' => __('Slide In')],
            ['value' => 'fade', 'label' => __('Fade In')],
            ['value' => 'bounce', 'label' => __('Bounce In')],
            ['value' => 'scale', 'label' => __('Scale Up')],
        ];
    }
}
