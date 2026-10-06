<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class FakeNames extends Field
{
    protected $_template = 'Panth_LiveActivity::system/config/fake_names.phtml';

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->_toHtml();
    }

    public function getValue()
    {
        return $this->getElement()->getValue() ?: $this->getDefaultValue();
    }

    public function getElementName()
    {
        return $this->getElement()->getName();
    }

    public function getElementId()
    {
        return $this->getElement()->getHtmlId();
    }

    public function getDefaultValue()
    {
        return json_encode($this->getDefaultNames(), JSON_PRETTY_PRINT);
    }

    public function getDefaultNames()
    {
        return [
            'James D.', 'John M.', 'Robert K.', 'Michael S.', 'William T.',
            'David R.', 'Sarah J.', 'Emily W.', 'Emma A.', 'Olivia E.',
            'Alex K.', 'Jordan M.', 'Taylor S.', 'Morgan R.', 'Riley B.',
        ];
    }
}
