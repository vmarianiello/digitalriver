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
use \Magento\Sales\Model\Order as Order;

/**
 *  CreateDrOrder
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */
class UpdateOrderDetails implements ObserverInterface
{
        /**
         * @param \Digitalriver\DrPay\Helper\Data            $helper
         * @param \Magento\Checkout\Model\Session            $session
         * @param \Magento\Store\Model\StoreManagerInterface $storeManager
         */
    public function __construct(
        \Digitalriver\DrPay\Helper\Data $helper,
        \Magento\Checkout\Model\Session $session,
		\Magento\Sales\Model\Order $order,
		\Digitalriver\DrPay\Model\DrConnector $drconnector,
		\Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory
    ) {
        $this->helper =  $helper;
        $this->session = $session;
		$this->order = $order;
		$this->drconnector = $drconnector;
		$this->jsonHelper = $jsonHelper;
        $this->_storeManager = $storeManager;
		$this->currencyFactory = $currencyFactory;
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
		$order = $observer->getEvent()->getOrder();
		$quote = $observer->getEvent()->getQuote();
		$result = $observer->getEvent()->getResult();
		$cartresult = $observer->getEvent()->getCartResult();
		//print_r($result);die;
		if(isset($result["submitCart"]["order"]["id"])){
			if(isset($result["submitCart"]['paymentMethod']['wireTransfer'])){
				$paymentData = $result["submitCart"]['paymentMethod']['wireTransfer'];
				$order->getPayment()->setAdditionalInformation($paymentData);
			}
			$orderId = $result["submitCart"]["order"]["id"];
			$order->setDrOrderId($orderId);
			$amount = $quote->getDrTax();
			$order->setDrTax($amount);
			$order->setTaxAmount($amount);
			$order->setBaseTaxAmount($this->convertToBaseCurrency($amount));
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
				foreach ($order->getAllVisibleItems() as $orderitem) {
					foreach($lineItems as $item){
						if($orderitem->getSku() == $item["product"]['id']){
							$orderitem->setDrOrderLineitemId($item['id']);
							$orderitem->save();
							break;
						}
					}
					$lineItems = $cartresult["cart"]['lineItems']['lineItem'];
					foreach($lineItems as $item){
						if($orderitem->getSku() == $item["product"]['id']){
							$qty = $item['quantity'];
							$listprice = $item["pricing"];
							if(isset($listprice["tax"]['value'])){
								$total_tax_amount = $listprice["tax"]['value'];
								$tax_amount = $total_tax_amount/$qty;
								$orderitem->setTaxAmount($total_tax_amount);
								$orderitem->setBaseTaxAmount($this->convertToBaseCurrency($total_tax_amount));
								if(isset($listprice["taxRate"])){
									$orderitem->setTaxPercent($listprice["taxRate"] * 100);
								}
								$orderitem->setPriceInclTax($orderitem->getPrice() + $tax_amount);
								$orderitem->setBasePriceInclTax($this->convertToBaseCurrency($orderitem->getPrice() + $tax_amount));
								$orderitem->setRowTotalInclTax($orderitem->getRowTotal() + $total_tax_amount);
								$orderitem->setBaseRowTotalInclTax($this->convertToBaseCurrency($orderitem->getRowTotal() + $total_tax_amount));
								$orderitem->save();
								break;
							}
						}
					}
				}
			}
			$order->save();
			$this->session->setDrAccessToken('');
		}
    }

	public function convertToBaseCurrency($price)
    {
        //you can also pass INR code here insted of below current store currency
        $currentCurrency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();

        $baseCurrency = $this->_storeManager->getStore()->getBaseCurrency()->getCode();

        $rate = $this->currencyFactory->create()->load($currentCurrency)->getAnyRate($baseCurrency);
        $returnValue = $price * $rate;

        return $returnValue;
    }
}