<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Fake Locations Configuration Field
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class FakeLocations extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Panth_LiveActivity::system/config/fake_locations.phtml';

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->_toHtml();
    }

    /**
     * Get current value from element
     *
     * @return string
     */
    public function getValue()
    {
        return $this->getElement()->getValue() ?: $this->getDefaultValue();
    }

    /**
     * Get element name
     *
     * @return string
     */
    public function getElementName()
    {
        return $this->getElement()->getName();
    }

    /**
     * Get element ID
     *
     * @return string
     */
    public function getElementId()
    {
        return $this->getElement()->getHtmlId();
    }

    /**
     * Get default fake locations JSON
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return json_encode($this->getDefaultLocations(), JSON_PRETTY_PRINT);
    }

    /**
     * Get default locations array
     *
     * @return array
     */
    public function getDefaultLocations()
    {
        return [
            'New York', 'London', 'Dubai', 'Singapore', 'Sydney',
            'Toronto', 'Paris', 'Tokyo', 'Berlin', 'Amsterdam',
            'Mumbai', 'Delhi', 'Bangalore', 'Chennai', 'Pune',
        ];
    }
}
