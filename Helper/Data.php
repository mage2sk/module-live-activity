<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public function isEnabled($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            'live_activity/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
