<?php
/**
 * Copyright (c) Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            'live_activity/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
