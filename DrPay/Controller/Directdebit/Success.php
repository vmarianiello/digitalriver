<?php

namespace Digitalriver\DrPay\Controller\Directdebit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use \Magento\Sales\Model\Order;
use \Magento\Quote\Model\QuoteFactory;

class Success extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var Order
     */
    protected $order;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    protected $quoteFactory;
    protected $regionModel;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     */

    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order $order,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Digitalriver\DrPay\Helper\Data $helper,
        \Magento\Directory\Model\Region $regionModel,
        QuoteFactory $quoteFactory
    ) {
        $this->customerSession = $customerSession;
        $this->order = $order;
        $this->helper =  $helper;
        $this->checkoutSession = $checkoutSession;
        $this->quoteFactory = $quoteFactory;
         $this->regionModel = $regionModel;
        return parent::__construct($context);
    }
    
    /**
     * Returned From Payment Gateway
     *
     * @return void
     */
    public function execute()
    {
        $orderId = $this->checkoutSession->getLastOrderId();
        $order = $this->order->load($orderId);
        if ($this->getRequest()->getParam('sourceId')) {
            $quote = $this->quoteFactory->create()->load($order->getQuoteId());
            $source_id = $this->getRequest()->getParam('sourceId');
            $accessToken = $this->checkoutSession->getDrAccessToken();
            $paymentResult = $this->helper->applyQuotePayment($source_id);
            $result = $this->helper->createOrderInDr($accessToken);
            if ($result && isset($result["errors"])) {
                $this->messageManager->addError(__('Unable to Place Order!! Payment has been failed'));
                if ($order && $order->getId()) {
                    $order->cancel()->save();
                    /* @var $cart \Magento\Checkout\Model\Cart */
                    $cart = $this->_objectManager->get(\Magento\Checkout\Model\Cart::class);
                    $items = $order->getItemsCollection();
                    foreach ($items as $item) {
                        try {
                            $cart->addOrderItem($item);
                        } catch (\Magento\Framework\Exception\LocalizedException $e) {
                            if ($this->_objectManager->get(\Magento\Checkout\Model\Session::class)->getUseNotice(true)) {
                                $this->messageManager->addNoticeMessage($e->getMessage());
                            } else {
                                $this->messageManager->addErrorMessage($e->getMessage());
                            }
                            return $resultRedirect->setPath('checkout/cart');
                        } catch (\Exception $e) {
                            $this->messageManager->addExceptionMessage(
                                $e,
                                __('We can\'t add this item to your shopping cart right now.')
                            );
                            return $resultRedirect->setPath('checkout/cart');
                        }
                    }
                    $cart->save();
                }
            } else {
                if (isset($result["submitCart"]["order"]["id"])) {
                    $orderId = $result["submitCart"]["order"]["id"];
                    $order->setDrOrderId($orderId);
                    $amount = $quote->getDrTax();
                    $order->setDrTax($amount);
                    $order->setState("pending_payment");
                    $order->setStatus("pending_payment");  
					if($result["submitCart"]["order"]["orderState"]){
						$order->setDrOrderState($result["submitCart"]["order"]["orderState"]);
					}                      
                    if($result["submitCart"]["order"]["orderState"] === "Submitted"){
                        $order->setState("processing");
                        $order->setStatus("processing");
                    }
                }
                $order->save();
                $this->_redirect('checkout/onepage/success', ['_secure'=>true]);
                return;
            }
        }
        $this->_redirect('checkout/cart');
        return;
    }
}
