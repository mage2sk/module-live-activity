<?php
declare(strict_types=1);

namespace Panth\LiveActivity\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * Product Picker field for system configuration
 */
class ProductPicker extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Panth_LiveActivity::system/config/product_picker.phtml';

    /**
     * @param Context $context
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context $context,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
    }

    /**
     * Render element value
     *
     * @param AbstractElement $element
     * @return string
     */
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

    /**
     * Get element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->_toHtml();
    }

    /**
     * Get element value
     *
     * @return string
     */
    public function getElementValue()
    {
        return $this->getElement()->getValue() ?: '';
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
     * Get URL for product search
     *
     * @return string
     */
    public function getSearchUrl()
    {
        return $this->getUrl('liveactivity/product/search');
    }

    /**
     * Get URL for fetching products by IDs
     *
     * @return string
     */
    public function getGetByIdsUrl()
    {
        return $this->getUrl('liveactivity/product/getbyids');
    }

    /**
     * Get form key for AJAX requests
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Unset some non-related element parameters
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
