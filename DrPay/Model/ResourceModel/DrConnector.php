<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 * @Module:  Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Model\ResourceModel;

class DrConnector extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb {

    public function __construct(
    \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct() {
        $this->_init('electronic_fulfillment', 'entity_id');
    }

}
