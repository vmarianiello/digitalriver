<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
 
namespace Digitalriver\DrPay\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class TestApi
 */
class TestApi extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Digitalriver_DrPay::testapi.phtml';

    /**
     * @param Context                                          $context
     * @param ScopeConfigInterface                             $scopeConfig
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param array                                            $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        parent::__construct($context, $data);
    }

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
        return $this->_toHtml();
    }

    /**
     * Return ajax url for collect button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        $token = $this->getApiKey();
        if ($token) {
            $token = $this->encryptor->decrypt($token);
            $apiUrl = $this->getBaseUrl()."/v1/site.drivenjson?apiKey=".$token;
            return $apiUrl;
        }
        return false;
    }
    /**
     * @return string
     */
    public function getApiKey()
    {
        $api_key = 'dr_settings/config/dr_api_key';
        return $this->scopeConfig->getValue($api_key, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    /**
     * @return string
     */
    public function getBaseUrl()
    {
        $base_url = 'dr_settings/config/dr_url';
        return $this->scopeConfig->getValue($base_url, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Generate collect button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        );
        $button->setData(
            [
                'id' => 'test_api_button',
                'label' => __('Run Test'),
            ]
        );
        return $button->toHtml();
    }
}
