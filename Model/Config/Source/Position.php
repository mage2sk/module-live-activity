<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Position implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'bottom-left', 'label' => __('Bottom Left')],
            ['value' => 'bottom-right', 'label' => __('Bottom Right')],
            ['value' => 'top-left', 'label' => __('Top Left')],
            ['value' => 'top-right', 'label' => __('Top Right')],
        ];
    }
}
