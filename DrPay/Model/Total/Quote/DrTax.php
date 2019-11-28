<?php

namespace Digitalriver\DrPay\Model\Total\Quote;

use Magento\Framework\App\Area;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class DrTax extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Tax\Helper\Data $helperTax,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->setCode('dr_tax');
        $this->storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_sessionQuote = $sessionQuote;
        $this->_customerSession = $customerSession;
        $this->_helperTax = $helperTax;
        $this->priceCurrency = $priceCurrency;
        $this->objectManager = $objectManager;
    }

    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        $address = $shippingAssignment->getShipping()->getAddress();
		$billingaddress = $quote->getBillingAddress();
        $items = $shippingAssignment->getItems();
        if(!count($items)){
            return $this;
        }

        $drtax = $this->_checkoutSession->getDrTax();
		$magentoTax = $total->getTaxAmount();
        $quote->setDrTax($drtax);
        $total->setDrTax($drtax);
        $total->setTaxAmount($drtax);
        //$magentoTax = $this->_checkoutSession->getMagentoAppliedTax();
        $baseGrandTotal = ($total->getBaseGrandTotal())?$total->getBaseGrandTotal():0;
        $grandTotal = ($total->getGrandTotal())?$total->getGrandTotal():0;
        if($baseGrandTotal > 0 && $grandTotal > 0){
            $total->setBaseGrandTotal($total->getBaseGrandTotal() - $magentoTax + $drtax);
            $total->setGrandTotal($total->getGrandTotal() - $magentoTax + $drtax); 
        }        
        return $this;
    }

    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = null;
        $amount = $quote->getDrTax();
        if ($amount == 0) {
			$billingaddress = $quote->getBillingAddress();
			$amount = $billingaddress->getTaxAmount();
		}
		$result = [
			'code' => $this->getCode(),
			'title' => __('Tax'),
			'value' => $amount
		];
        
        return $result;
    }

}