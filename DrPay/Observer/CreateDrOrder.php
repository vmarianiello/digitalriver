<?php
/**
 * DrPay Observer
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
 
namespace Digitalriver\DrPay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 *  CreateDrOrder
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
class CreateDrOrder implements ObserverInterface
{
        /**
         * @param \Digitalriver\DrPay\Helper\Data            $helper
         * @param \Magento\Checkout\Model\Session            $session
         * @param \Magento\Store\Model\StoreManagerInterface $storeManager
         */
    public function __construct(
        \Digitalriver\DrPay\Helper\Data $helper,
        \Magento\Checkout\Model\Session $session,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->helper =  $helper;
        $this->session = $session;
        $this->_storeManager = $storeManager;
    }

    /**
     * Create order
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer['order'];
        $quote = $observer['quote'];
        if ($quote->getPayment()->getMethod() == \Digitalriver\DrPay\Model\CreditCard::PAYMENT_METHOD_CREDITCARD_CODE) {
            $result = $this->helper->createFullCartInDr($quote, true);
            $accessToken = $this->session->getDrAccessToken();
            if ($this->session->getDrQuoteError()) {
                throw new CouldNotSaveException(__('Unable to Place Order'));
            } else {
                $totals = $result["cart"]["pricing"]["orderTotal"];
                $drCurrency = $totals["currency"];
                $dr_grand_total = (int)round($totals["value"]);
                $currency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
                $grand_total = (int)round($quote->getGrandTotal());
                //if($currency == $drCurrency && $grand_total == $dr_grand_total){
                    $result = $this->helper->createOrderInDr($accessToken);
                if ($result && isset($result["errors"])) {
                    throw new CouldNotSaveException(__('Unable to Place Order'));
                } else {
                    if (isset($result["submitCart"]["order"]["id"])) {
                        $order->setState("processing");
                        $order->setStatus("processing");
                        //Store the drOrderid in database
                        $orderId = $result["submitCart"]["order"]["id"];
                        $order->setDrOrderId($orderId);
                    }
                }
            }
        }
    }
}
