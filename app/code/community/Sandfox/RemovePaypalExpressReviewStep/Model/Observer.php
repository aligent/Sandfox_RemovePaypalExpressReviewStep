<?php

class Sandfox_RemovePaypalExpressReviewStep_Model_Observer
{
	public function controllerActionPredispatchPaypalExpressReview(Varien_Event_Observer $observer)
	{
		$quote = Mage::getModel('checkout/cart')->getQuote();
		$shippingAddress = $quote->getShippingAddress();
		$helper = Mage::helper("sandfox_removepaypalexpressreviewstep/data");

		if(!$helper->skipReview()){
			return $this;
		}

		// Only redirect if a shipping method has been set otherwise continue to review
		// Otherwise magento will redirect back to review causing an endless loop
		if ($shippingAddress->getShippingMethod() || $quote->getIsVirtual()) {
			Mage::app()->getResponse()->setRedirect(Mage::getUrl('*/*/placeOrder'));
		}
	}

	public function controllerActionPredispatchPaypalExpressPlaceOrder(Varien_Event_Observer $observer)
	{
		if(Mage::helper("sandfox_removepaypalexpressreviewstep/data")->skipReview()){
			$requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
			$postedAgreements = array_fill_keys($requiredAgreements, 1);
			Mage::app()->getRequest()->setPost('agreement', $postedAgreements);
		}
	}

	public function controllerActionPredispatchPaypalExpressStart(Varien_Event_Observer $observer)
	{
		// Remove any existing shipping methods from the quote if we are using express checkout.
		// The shipping address, method and rate will be set from the paypal site.
		$expressCheckoutTrue = (bool)Mage::app()->getRequest()->getParam('button');
		if ($expressCheckoutTrue && Mage::helper("sandfox_removepaypalexpressreviewstep/data")->skipReview()) {
			$quote = Mage::getModel('checkout/cart')->getQuote();
			$shippingAddress = $quote->getShippingAddress();
			$shippingAddress->setShippingMethod('');
			$quote->collectTotals()->save();
		}
	}
}
