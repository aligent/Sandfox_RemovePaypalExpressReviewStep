<?php

class Sandfox_RemovePaypalExpressReviewStep_Model_Config extends Mage_Paypal_Model_Config
{
	/**
	 * Add useraction => commit to change "continue" button to "pay now"
	 * @param string $token
	 * @return string
	 */
	public function getExpressCheckoutStartUrl($token)
	{
		return $this->getPaypalUrl(array(
			'cmd'           => '_express-checkout',
			'useraction'    => 'commit',
			'token'         => $token
		));
	}
}
