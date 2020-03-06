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
class UpdateOrderStatus implements ObserverInterface
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
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->helper =  $helper;
        $this->session = $session;
		$this->order = $order;
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
		$orderId = $observer->getEvent()->getOrderIds();
        $order = $this->order->load($orderId);
        if($order->getDrOrderId()){
            if($order->getDrOrderState() == "Submitted"){ 
                $order->setState(Order::STATE_PROCESSING); 
                $order->setStatus(Order::STATE_PROCESSING);
            }else if($order->getDrOrderState() == "Source Pending Funds"){ 
                $order->setState(Order::STATE_PENDING_PAYMENT); 
                $order->setStatus(Order::STATE_PENDING_PAYMENT);
            }else{ 
                $order->setState(Order::STATE_PAYMENT_REVIEW); 
                $order->setStatus(Order::STATE_PAYMENT_REVIEW);
            }
            $order->save();
        }
    }
}