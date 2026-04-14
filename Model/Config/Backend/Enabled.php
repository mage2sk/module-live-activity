<?php
/**
 * Backend Model for LiveActivity Enabled Field
 * License validation removed - standard Value backend
 *
 * @category  Panth
 * @package   Panth_LiveActivity
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

class Enabled extends Value
{
    /**
     * @return $this
     */
    public function beforeSave()
    {
        return parent::beforeSave();
    }
}
