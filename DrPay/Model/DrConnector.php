<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 * @Module: Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Model;

class DrConnector extends \Magento\Framework\Model\AbstractModel {

    protected function _construct() {
        $this->_init('Digitalriver\DrPay\Model\ResourceModel\DrConnector');
    }

}
