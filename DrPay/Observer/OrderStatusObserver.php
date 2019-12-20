<?php

/**
 * @category Digitalriver
 * @package: Digitalriver_DrPay
 *
 */

namespace Digitalriver\DrPay\Observer;

use Magento\Sales\Model\Order;
use Magento\Framework\Event\ObserverInterface;

class OrderStatusObserver implements ObserverInterface
{

    /**
     *
     * @param \Digitalriver\DrPay\Helper\Data $drHelper
     */
    public function __construct(
        \Digitalriver\DrPay\Helper\Data $drHelper
    ) {
        $this->drHelper = $drHelper;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order instanceof \Magento\Framework\Model\AbstractModel) {

            switch ($order->getStatus()) {
                case Order::STATE_COMPLETE:
                    $postDrRequest =$this->drHelper->postDrRequest($order);
                    break;
                case Order::STATE_CANCELED:
                    $postDrRequest = $this->drHelper->postDrRequest($order);
                    break;
            }
        }
        return $this;
    }
}
