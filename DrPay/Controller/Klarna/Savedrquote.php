<?php
/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Controller\Klarna;

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
            'success'        => false,
            'content'        => "Unable to process"
        ];
        $quote = $this->_checkoutSession->getQuote();
        $cartResult = $this->helper->createFullCartInDr($quote, 1);
        if ($cartResult) {
            $payload = [];
            $returnurl = $this->_url->getUrl('drpay/klarna/success');
            $cancelurl = $this->_url->getUrl('drpay/klarna/cancel');
            $itemsArr = [];
            $shipping = [];
            $itemPrice = 0;
            $taxAmnt = 0;
            $shipAmnt = 0;
            foreach ($quote->getAllVisibleItems() as $item) {
                $itemsArr = [
                    'name' => $item->getName(),
                    'quantity' => $item->getQty(),
                    'unitAmount' => $item->getPrice(),
                    'taxRate' => 0,
                ];
            }
            $address = $quote->getShippingAddress();
			$billingaddress = $quote->getBillingAddress();
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
						'email' => $billingaddress->getEmail(),
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
        
            //Prepare the payload and return in response for DRJS klarna payload
            $payload['payload'] = [
                'type' => 'klarnaCredit',
                'amount' => $quote->getGrandTotal(),
                'currency' => $quote->getQuoteCurrencyCode(),
				'owner' => [
					'firstName' => $address->getFirstname(),
					'lastName' => $address->getLastname(),
					'email' => $quote->getCustomerEmail(),
					'phoneNumber' => $address->getTelephone(),
					'address' =>  [
						'line1' => $street1,
						'city' => (null !== $address->getCity())?$address->getCity():'na',
						'state' => $state,
						'country' => $address->getCountryId(),
						'postalCode' => $address->getPostcode(),
					],
									],
                'klarnaCredit' =>  [
					"setPaidBefore" => true,
                    'returnUrl' => $returnurl,
                    'cancelUrl' => $cancelurl,
                    'items' => [$itemsArr],
                    'taxAmount' => $taxAmnt,
                    'shippingAmount' => $shipAmnt,
                    'requestShipping' => true,
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
