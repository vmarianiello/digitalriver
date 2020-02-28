<?php

namespace Digitalriver\DrPay\Controller\Wiretransfer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\QuoteFactory;

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
		\Digitalriver\DrPay\Model\DrConnector $drconnector,
		\Magento\Framework\Json\Helper\Data $jsonHelper,
        QuoteFactory $quoteFactory
    ){
        $this->customerSession = $customerSession;
        $this->order = $order;
        $this->helper =  $helper;
        $this->checkoutSession = $checkoutSession;
        $this->quoteFactory = $quoteFactory;
 		$this->regionModel = $regionModel;    
        $this->drconnector = $drconnector;
		$this->jsonHelper = $jsonHelper;    
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
        $fulldir        = explode('app/code',dirname(__FILE__));
        $logfilename    = $fulldir[0] . 'var/log/drpay-wire.log';		
		if($orderId){
			$quote = $this->quoteFactory->create()->load($order->getQuoteId());
            $source_id = $this->getRequest()->getParam('sourceId');
            $accessToken = $this->checkoutSession->getDrAccessToken();
            $paymentResult = $this->helper->applyQuotePayment($source_id);
			$result = $this->helper->createOrderInDr($accessToken);
			file_put_contents($logfilename, "Source Id: ".$this->getRequest()->getParam('sourceId')." wire Order Failed "." OrderId ".$order->getId(). "\r\n"." -> OrderData".json_encode($result)."\r\n"." Payment Data: ->".json_encode($paymentResult)."\r\n", FILE_APPEND);

			if($result && isset($result["errors"])){
				file_put_contents($logfilename, "Source Id: ".$this->getRequest()->getParam('sourceId')." wire Order Failed "." OrderId ".$order->getId(). "\r\n"." -> OrderData".json_encode($result)."\r\n"." Payment Data: ->".json_encode($paymentResult)."\r\n", FILE_APPEND);
				$this->messageManager->addError(__('Unable to Place Order!! Payment has been failed'));
				$this->_redirect("sales/order/reorder", array("order_id" => $order->getId()));
				return;
			}else{ 
                $paymentData = $result["submitCart"]['paymentMethod']['wireTransfer'];
                // $paymentData = json_decode($paymentData, true);
				if(isset($result["submitCart"]["order"]["id"]) && is_array($paymentData)){
                    $order->getPayment()->setAdditionalInformation($paymentData);
					$orderId = $result["submitCart"]["order"]["id"];
					$order->setDrOrderId($orderId);
					$amount = $quote->getDrTax();
					$order->setDrTax($amount);  
					if($result["submitCart"]["order"]["orderState"]){
						$order->setDrOrderState($result["submitCart"]["order"]["orderState"]);
					}
					if(isset($result["submitCart"]['lineItems']['lineItem'])){
						$lineItems = $result["submitCart"]['lineItems']['lineItem'];
						$model = $this->drconnector->load($orderId, 'requisition_id');
						$model->setRequisitionId($orderId);
						$lineItemIds = array();
						foreach($lineItems as $item){
							$qty = $item['quantity'];
							$lineitemId = $item['id'];
							$lineItemIds[] = ['qty' => $qty,'lineitemid' => $lineitemId];
						}
						$model->setLineItemIds($this->jsonHelper->jsonEncode($lineItemIds));
						$model->save();
					}
				}
				$order->save();
				$this->_redirect('checkout/onepage/success', array('_secure'=>true));
				return;
			}
		}
		$this->_redirect('checkout/cart');
		return;
    }
}
