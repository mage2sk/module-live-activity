<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Fake Names Configuration Field
 */
declare(strict_types=1);

namespace Panth\LiveActivity\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class FakeNames extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Panth_LiveActivity::system/config/fake_names.phtml';

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
     * Get default fake names JSON
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return json_encode($this->getDefaultNames(), JSON_PRETTY_PRINT);
    }

    /**
     * Get default names array
     * Returns simple array of name strings in "FirstName L." format
     *
     * @return array
     */
    public function getDefaultNames()
    {
        return [
            'James D.', 'John M.', 'Robert K.', 'Michael S.', 'William T.',
            'David R.', 'Sarah J.', 'Emily W.', 'Emma A.', 'Olivia E.',
            'Alex K.', 'Jordan M.', 'Taylor S.', 'Morgan R.', 'Riley B.',
        ];
    }
}
