<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 * @category Digitalriver
 * @package: Digitalriver_DrPay
 * 
 */

namespace Digitalriver\DrPay\Observer;

use Magento\Sales\Model\Order;
use Magento\Framework\Event\ObserverInterface;

class OrderStatusObserver implements ObserverInterface {

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
    public function execute(\Magento\Framework\Event\Observer $observer) {

        $fulldir = explode('app/code', dirname(__FILE__));
        $logfilename = $fulldir[0] . 'var/log/dr-con-req.log';

        $order = $observer->getEvent()->getOrder();
        if ($order instanceof \Magento\Framework\Model\AbstractModel) {

            switch ($order->getStatus()) {
                case Order::STATE_COMPLETE :
					file_put_contents($logfilename, "Dr Connector Response: " . json_encode($this->drHelper->postDrRequest($order)) . "\r\n", FILE_APPEND);
                    break;
                case Order::STATE_CANCELED :
					file_put_contents($logfilename, "Dr Connector Response: " . json_encode($this->drHelper->postDrRequest($order)) . "\r\n", FILE_APPEND);
                    break;
            }
        }
        return $this;
    }

}
