<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
namespace Digitalriver\DrPay\Controller\Paypal;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;

/**
 * Class Success
 */
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
        /**
         * @var \Magento\Quote\Model\QuoteFactory
         */
    protected $quoteFactory;
        /**
         * @var \Magento\Directory\Model\Region
         */
    protected $regionModel;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session       $customerSession
     * \Magento\Sales\Model\Order $order
     * \Magento\Checkout\Model\Session $checkoutSession
     * \Digitalriver\DrPay\Helper\Data $helper
     * \Magento\Directory\Model\Region $regionModel
     * \Magento\Quote\Model\QuoteFactory $quoteFactory
     */

    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order $order,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Digitalriver\DrPay\Helper\Data $helper,
        \Magento\Directory\Model\Region $regionModel,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
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
     * Paypal Success response
     *
     * @return mixed|null
     */
    public function execute()
    {
        $orderId = $this->checkoutSession->getLastOrderId();
        $order = $this->order->load($orderId);
        /**
		 * @var \Magento\Framework\Controller\Result\Redirect $resultRedirect
		 */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($this->getRequest()->getParam('sourceId')) {
            $quote = $this->quoteFactory->create()->load($order->getQuoteId());
            $source_id = $this->getRequest()->getParam('sourceId');
            $accessToken = $this->checkoutSession->getDrAccessToken();
            $paymentResult = $this->helper->applyQuotePayment($source_id);
            $result = $this->helper->createOrderInDr($accessToken);
            if ($result && isset($result["errors"])) {
                $this->messageManager->addError(__('Unable to Place Order!! Payment has been failed'));
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
                    return $resultRedirect->setPath('checkout/cart');
            } else {
                if (isset($result["submitCart"]["order"]["id"])) {
                    $orderId = $result["submitCart"]["order"]["id"];
                    $order->setDrOrderId($orderId);
                    $amount = $quote->getDrTax();
                    $order->setDrTax($amount);
                }
                $order->setState("pending_payment");
                $order->setStatus("pending_payment");
				if($result["submitCart"]["order"]["orderState"]){
					$order->setDrOrderState($result["submitCart"]["order"]["orderState"]);
				}
                if($result["submitCart"]["order"]["orderState"] === "Submitted"){
                    $order->setState("processing");
                    $order->setStatus("processing");
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
