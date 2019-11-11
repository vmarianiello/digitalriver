<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Controller\Paypal;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class Savedrquote
 */
class Savedrquote extends \Magento\Framework\App\Action\Action
{
        /**
         * @var \Magento\Directory\Model\Region
         */
    protected $regionModel;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session       $checkoutSession
     * @param \Magento\Directory\Model\Region       $regionModel
     * @param \Digitalriver\DrPay\Helper\Data       $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Directory\Model\Region $regionModel,
        \Digitalriver\DrPay\Helper\Data $helper
    ) {
        $this->helper =  $helper;
        $this->_checkoutSession = $checkoutSession;
        $this->regionModel = $regionModel;
        parent::__construct($context);
    }
    /**
     * @return mixed|null
     */
    public function execute()
    {
        $responseContent = [
            'success'        => false
        ];
        $quote = $this->_checkoutSession->getQuote();
        $cartResult = $this->helper->createFullCartInDr($quote, 1);
        if ($cartResult) {
            $payload = [];
            $returnurl = $this->_url->getUrl('drpay/paypal/success');
            $cancelurl = $this->_url->getUrl('drpay/paypal/cancel');
            $itemsArr = [];
            $shipping = [];
            $itemPrice = 0;
            $taxAmnt = 0;
            $shipAmnt = 0;
            foreach ($quote->getAllItems() as $item) {
                $type_code = \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
                if ($item->getProductType() == $type_code) {
                    $itemPrice = $item->getPrice();
                    continue;
                }
                $itemsArr = [
                    'name' => $item->getName(),
                    'quantity' => $item->getQty(),
                    'unitAmount' => ($itemPrice>0)?$itemPrice:$item->getPrice(),
                ];
            }
            $address = $quote->getShippingAddress();
            if ($quote->isVirtual()) {
                $address = $quote->getBillingAddress();
            }
            if ($address && $address->getId()) {
                $shipAmnt = $address->getShippingAmount();
                $taxAmnt = $address->getTaxAmount();
                $shipping =  [];
                $street = $address->getStreet();
                if (isset($street[0])) {
                    $street1 = $street[0];
                } else {
                    $street1 = "";
                }
                if (isset($street[1])) {
                    $street2 = $street[1];
                } else {
                    $street2 = "";
                }
                $state = 'na';
                $regionName = $address->getRegion();
                if ($regionName) {
                    $countryId = $address->getCountryId();
                    $region = $this->regionModel->loadByName($regionName, $countryId);
                    $state = $region->getCode();
                }

                $shipping =  [
                        'recipient' => $address->getFirstname()." ".$address->getLastname(),
                        'phoneNumber' => $address->getTelephone(),
                        'address' =>  [
                            'line1' => $street1,
                            'line2' => $street2,
                            'city' => (null !== $address->getCity())?$address->getCity():'na',
                            'state' => $state,
                            'country' => $address->getCountryId(),
                            'postalCode' => $address->getPostcode(),
                        ],
                    ];
            }


        
            //Prepare the payload and return in response for DRJS paypal payload
            $payload['payload'] = [
                'type' => 'payPal',
                'amount' => (int)round($quote->getGrandTotal()),
                'currency' => $quote->getQuoteCurrencyCode(),
                'payPal' =>  [
                    'returnUrl' => $returnurl,
                    'cancelUrl' => $cancelurl,
                    'items' => [$itemsArr],
                    'taxAmount' => $taxAmnt,
                    'shippingAmount' => $shipAmnt,
                    'amountsEstimated' => true,
                    'shipping' => $shipping,
                ],
            ];
            $responseContent = [
                'success'        => true,
                'content'        => $payload
            ];
        }
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($responseContent);

        return $response;
    }
}
