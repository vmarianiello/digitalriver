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

class OrderRefundObserver implements ObserverInterface {

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

        $creditmemo = $observer->getEvent()->getCreditmemo();
		$status = $this->drHelper->initiateRefundRequest($creditmemo);
		if(!$status){
			throw new \Exception('There is an issue with Refun at DR side');
		}
        return $this;
    }

}
