<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 * @Module:  Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Model\ResourceModel\DrConnector;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct() {
        $this->_init(\Digitalriver\DrPay\Model\DrConnector::class, \Digitalriver\DrPay\Model\ResourceModel\DrConnector::class);
    }

}
