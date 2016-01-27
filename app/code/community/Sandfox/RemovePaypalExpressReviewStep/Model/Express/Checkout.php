<?php

class Sandfox_RemovePaypalExpressReviewStep_Model_Express_Checkout extends Mage_Paypal_Model_Express_Checkout
{
	/**
	 * Altered to save exported shipping method and address here instead of the review step
	 *
	 *
	 * @param string $token
	 */
	public function returnFromPaypal($token)
	{
        $this->_getApi();
        $this->_api->setToken($token)
            ->callGetExpressCheckoutDetails();
        $quote = $this->_quote;
        $this->_quote->getShippingAddress()->setShouldIgnoreValidation(true);

        // import billing address
        $billingAddress = $quote->getBillingAddress();
        $exportedBillingAddress = $this->_api->getExportedBillingAddress();
        $quote->setCustomerEmail($billingAddress->getEmail());
        $quote->setCustomerPrefix($billingAddress->getPrefix());
        $quote->setCustomerFirstname($billingAddress->getFirstname());
        $quote->setCustomerMiddlename($billingAddress->getMiddlename());
        $quote->setCustomerLastname($billingAddress->getLastname());
        $quote->setCustomerSuffix($billingAddress->getSuffix());
        $quote->setCustomerNote($exportedBillingAddress->getData('note'));

        $this->_setExportedAddressData($billingAddress, $exportedBillingAddress);

        // import shipping address
        $exportedShippingAddress = $this->_api->getExportedShippingAddress();

        //Paypal returns the full ship-to name and puts it in the first name field.
        //here, we attempt to split it back into it's correct format. If this can't be done,
        //the Magento shipment last name will be the last name on the Paypal account.
        $shipFullName = $exportedShippingAddress->getFirstname();
        $nameParts = explode(" ", $shipFullName);
        if(count($nameParts) > 1){
            $exportedShippingAddress["firstname"] = $nameParts[0];
            $exportedShippingAddress["lastname"] = end($nameParts);
        }

        if (!$quote->getIsVirtual()) {
            $shippingAddress = $quote->getShippingAddress();
            if ($shippingAddress) {
                if ($exportedShippingAddress) {
                    $this->_setExportedAddressData($shippingAddress, $exportedShippingAddress);
                    //Overwrite the Paypal shipping address name with the customer name if configured.
                    if(Mage::helper("sandfox_removepaypalexpressreviewstep/data")->forceCustomerName() &&
                       $quote->getCustomer()->getEntityId()){
                        $shippingAddress->setFirstname($quote->getCustomer()->getFirstname());
                        $shippingAddress->setLastname($quote->getCustomer()->getLastname());
                        $shippingAddress->setMiddlename($quote->getCustomer()->getMiddlename());
                    }
                    //Copy Paypal shipping address info into billing address if required and configured
                    //If a billing address exists, then no overwriting takes place.
                    if(Mage::helper("sandfox_removepaypalexpressreviewstep/data")->useShippingAsBilling() &&
                        !$billingAddress->getData("street")){
                        $billingAddress->setFirstname($shippingAddress->getFirstname());
                        $billingAddress->setLastname($shippingAddress->getLastname());
                        $billingAddress->setStreet($shippingAddress->getStreet());
                        $billingAddress->setPostcode($shippingAddress->getPostcode());
                        $billingAddress->setRegion($shippingAddress->getRegion());
                        $billingAddress->setRegionId($shippingAddress->getRegionId());
                        $billingAddress->setCountryId($shippingAddress->getCountryId());
                        $billingAddress->setCity($shippingAddress->getCity());

                    }
                    $shippingAddress->setCollectShippingRates(true);
                    $shippingAddress->setSameAsBilling(0);
                }

                // import shipping method
                $code = '';
                if ($this->_api->getShippingRateCode()) {

                    // We collect totals inc. the imported shipping method/rate now so we can validate and set it below.
                    $quote->collectTotals();
                    if ($code = $this->_matchShippingMethodCode($shippingAddress, $this->_api->getShippingRateCode())) {
                        // possible bug of double collecting rates :-/
                        $shippingAddress->setShippingMethod($code)->setCollectShippingRates(true);
                    }
                }
                $quote->getPayment()->setAdditionalInformation(
                    self::PAYMENT_INFO_TRANSPORT_SHIPPING_METHOD,
                    $code
                );
            }
        }

        // import payment info
        $payment = $quote->getPayment();
        $payment->setMethod($this->_methodType);
        Mage::getSingleton('paypal/info')->importToPayment($this->_api, $payment);
        $payment->setAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_PAYER_ID, $this->_api->getPayerId())
            ->setAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_TOKEN, $token)
        ;
        $quote->collectTotals()->save();
	}

    /** The problem is that Paypal doesn't display the Shipping name that's sent across to it, it's displaying the shipping code.
     *  So to get the shipping options looking pretty in Paypal you just switch around the name and code values
     */
    protected function _prepareShippingOptions(
        Mage_Sales_Model_Quote_Address $address,
        $mayReturnEmpty = false, $calculateTax = false
    ) {
        $options = parent::_prepareShippingOptions($address, $mayReturnEmpty, $calculateTax);
        foreach ($options as &$option) {
            $tmp = $option->name;
            $option->name = $option->code;
            $option->code = $tmp;
        }
        return $options;
    }

    /** Paypal has passed back the same `$option['code']` and `$option['name']` we sent over to it,
     *  as we switched them round before we passed them we therefore just return the name rather than the code from this method.
     */
    protected function _matchShippingMethodCode(Mage_Sales_Model_Quote_Address $address, $selectedCode)
    {
        $options = $this->_prepareShippingOptions($address, false);
        foreach ($options as $option) {
            if ($selectedCode === $option['code'] // the proper case as outlined in documentation
                || $selectedCode === $option['name'] // workaround: PayPal may return name instead of the code
                // workaround: PayPal may concatenate code and name, and return it instead of the code:
                || $selectedCode === "{$option['code']} {$option['name']}"
            ) {
                // Return name here instead of code
                return $option['name'];
            }
        }
        return '';
    }
}
