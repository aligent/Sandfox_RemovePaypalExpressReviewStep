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
        if (!$quote->getIsVirtual()) {
            $shippingAddress = $quote->getShippingAddress();
            if ($shippingAddress) {
                if ($exportedShippingAddress) {
                    $this->_setExportedAddressData($shippingAddress, $exportedShippingAddress);
                    $shippingAddress->setCollectShippingRates(true);
                    $shippingAddress->setSameAsBilling(0);
                }

                // import shipping method
                $code = '';
                if ($this->_api->getShippingRateCode()) {
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
        $options = array(); $i = 0; $iMin = false; $min = false;
        $userSelectedOption = null;

        foreach ($address->getGroupedAllShippingRates() as $group) {
            foreach ($group as $rate) {
                $amount = (float)$rate->getPrice();
                if ($rate->getErrorMessage()) {
                    continue;
                }
                $isDefault = $address->getShippingMethod() === $rate->getCode();
                $amountExclTax = Mage::helper('tax')->getShippingPrice($amount, false, $address);
                $amountInclTax = Mage::helper('tax')->getShippingPrice($amount, true, $address);

                $options[$i] = new Varien_Object(array(
                    'is_default' => $isDefault,
                    'name'       => $rate->getCode(),
                    'code'       => trim("{$rate->getCarrierTitle()} - {$rate->getMethodTitle()}", ' -'),
                    'amount'     => $amountExclTax,
                ));
                if ($calculateTax) {
                    $options[$i]->setTaxAmount(
                        $amountInclTax - $amountExclTax
                        + $address->getTaxAmount() - $address->getShippingTaxAmount()
                    );
                }
                if ($isDefault) {
                    $userSelectedOption = $options[$i];
                }
                if (false === $min || $amountInclTax < $min) {
                    $min = $amountInclTax;
                    $iMin = $i;
                }
                $i++;
            }
        }

        if ($mayReturnEmpty && is_null($userSelectedOption)) {
            $options[] = new Varien_Object(array(
                'is_default' => true,
                'name'       => Mage::helper('paypal')->__('N/A'),
                'code'       => 'no_rate',
                'amount'     => 0.00,
            ));
            if ($calculateTax) {
                $options[$i]->setTaxAmount($address->getTaxAmount());
            }
        } elseif (is_null($userSelectedOption) && isset($options[$iMin])) {
            $options[$iMin]->setIsDefault(true);
        }

        // Magento will transfer only first 10 cheapest shipping options if there are more than 10 available.
        if (count($options) > 10) {
            usort($options, array(get_class($this),'cmpShippingOptions'));
            array_splice($options, 10);
            // User selected option will be always included in options list
            if (!is_null($userSelectedOption) && !in_array($userSelectedOption, $options)) {
                $options[9] = $userSelectedOption;
            }
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
                return $option['name'];
            }
        }
        return '';
    }
}
