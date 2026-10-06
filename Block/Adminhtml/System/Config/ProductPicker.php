<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class ProductPicker extends Field
{
    protected $_template = 'Panth_LiveActivity::system/config/product_picker.phtml';

    public function __construct(
        Context $context,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
    }

    protected function _renderValue(AbstractElement $element)
    {
        $html = '<td class="value">';
        $html .= $this->_getElementHtml($element);

        if ($element->getComment()) {
            $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
        }

        $html .= '</td>';
        return $html;
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->_toHtml();
    }

    public function getElementValue()
    {
        return $this->getElement()->getValue() ?: '';
    }

    public function getElementName()
    {
        return $this->getElement()->getName();
    }

    public function getElementId()
    {
        return $this->getElement()->getHtmlId();
    }

    public function getSearchUrl()
    {
        return $this->getUrl('liveactivity/product/search');
    }

    public function getGetByIdsUrl()
    {
        return $this->getUrl('liveactivity/product/getbyids');
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
