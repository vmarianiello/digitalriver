<?php

/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 * @Module: Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Api;

/**
 * @api
 * @since 100.0.2
 */
interface DrConnectorRepositoryInterface {

    /**
     * Save the FullFillmentRequest
     *
     * @param mixed
     * @return string[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function SaveFullFillment($OrderLevelElectronicFulfillmentRequest);
}
