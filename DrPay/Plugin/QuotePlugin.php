<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Plugin;

class QuotePlugin
{

    protected $drHelper;
    
    protected $scopeConfig;
    
    const XML_PATH_ENABLE_DRPAY = 'dr_settings/config/active';
    
    public function __construct(
        \Digitalriver\DrPay\Helper\Data $drHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger
    ) {
         $this->drHelper= $drHelper;
         $this->scopeConfig = $scopeConfig;
         $this->_logger = $logger;
    }
    
    public function getDrPayEnable()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_ENABLE_DRPAY, $storeScope);
    }

    /**
     * Set shipping address
     *
     * @param  \Magento\Quote\Model\Quote               $subject
     * @param  \Magento\Quote\Api\Data\AddressInterface $address
     * @return $this
     */
    public function afterSetShippingAddress(
        \Magento\Quote\Model\Quote $subject,
        $result,
        $address
    ) {
        $enableDrPayValue = $this->getDrPayEnable();
        if ($enableDrPayValue) {
            $this->_logger->info("DrPay is enabled in shipping");
            if (!$subject->isVirtual()) {
                // Create Shopper and get Full access token
                $this->drHelper->convertTokenToFullAccessToken();
                //Create the cart in DR
                $this->drHelper->createFullCartInDr($subject);
            }
        } else {
            $this->_logger->info("DrPay is disabled in shipping");
        }
        return $result;
    }

    /**
     * Set billing address.
     *
     * @param  \Magento\Quote\Model\Quote               $subject
     * @param  \Magento\Quote\Api\Data\AddressInterface $address
     * @return $this
     */
    public function afterSetBillingAddress(
        \Magento\Quote\Model\Quote $subject,
        $result,
        $address
    ) {
        $enableDrPayValue = $this->getDrPayEnable();
        if ($enableDrPayValue) {
            $this->_logger->info("DrPay is enabled in billing");
            if ($subject->isVirtual()) {
                // Create Shopper and get Full access token
                $this->drHelper->convertTokenToFullAccessToken();
                //Create the cart in DR
                $this->drHelper->createFullCartInDr($subject);
            }
        } else {
            $this->_logger->info("DrPay is disabled in billing");
        }
        return $result;
    }
}
