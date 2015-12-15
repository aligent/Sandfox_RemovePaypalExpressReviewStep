<?php

class Sandfox_RemovePaypalExpressReviewStep_Model_Observer
{
	public function controllerActionPredispatchCheckoutOnepageIndex(Varien_Event_Observer $observer)
	{
		if ($checkoutNotificationMessage = Mage::getSingleton('core/session')->getCheckoutNotificationMessage()) {
			Mage::getSingleton('checkout/session')->addNotice($checkoutNotificationMessage);
		}
	}

	public function controllerActionPredispatchPaypalExpressReview(Varien_Event_Observer $observer)
	{

		$quote = Mage::getModel('checkout/cart')->getQuote();
		$shippingAddress = $quote->getShippingAddress();
		$payment = $quote->getPayment();

		if ($shippingAddress->getCountryId() === 'AU' || $payment->getAdditionalInformation('paypal_express_checkout_shipping_overriden')) {
			Mage::app()->getResponse()->setRedirect(Mage::getUrl('*/*/placeOrder'));
		} else {
			Mage::getSingleton('core/session')->setCheckoutNotificationMessage('Please proceed through full checkout for international orders');
			Mage::app()->getResponse()->setRedirect(Mage::getUrl('checkout/onepage'));
		}

	}

	public function controllerActionPredispatchPaypalExpressPlaceOrder(Varien_Event_Observer $observer)
	{
		$requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
		$postedAgreements = array_fill_keys($requiredAgreements, 1);
		Mage::app()->getRequest()->setPost('agreement', $postedAgreements);
	}
}
