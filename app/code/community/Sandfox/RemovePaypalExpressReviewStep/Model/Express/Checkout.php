<?php

class Sandfox_RemovePaypalExpressReviewStep_Model_Express_Checkout extends Mage_Paypal_Model_Express_Checkout
{
	/**
	 * Run parent function then validate and add shipping method and save quote
	 *
	 *
	 * @param string $token
	 */
	public function returnFromPaypal($token)
	{
		parent::returnFromPaypal($token);
		$quote = $this->_quote;
		if (!$quote->getIsVirtual()) {
			$shippingAddress = $quote->getShippingAddress();
			if ($shippingAddress) {
				// import shipping method
				$code = '';
				if ($this->_api->getShippingRateCode()) {
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
		$quote->collectTotals()->save();
	}
}
