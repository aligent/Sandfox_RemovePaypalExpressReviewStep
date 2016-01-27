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
		$urlParams = array(
			'cmd' => '_express-checkout',
			'token' => $token
		);
		if(Mage::helper("sandfox_removepaypalexpressreviewstep/data")->skipReview()){
			$urlParams['useraction'] = 'commit';
		}
		return $this->getPaypalUrl($urlParams);
	}
}
