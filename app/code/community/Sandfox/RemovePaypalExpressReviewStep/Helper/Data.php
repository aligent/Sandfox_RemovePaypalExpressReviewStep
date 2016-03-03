<?php
/**
 * Paypal Express review skip helper.
 *
 * @package     Sandfox_RemovePaypalExpressReviewStep
 * @author      Matthew O'Loughlin (matthew.oloughlin@aligent.com.au)
 */
class Sandfox_RemovePaypalExpressReviewStep_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CONFIG_PATH_SKIP_REVIEW = 'payment/paypal_express/skip_order_review_step';
    const CONFIG_PATH_USE_CUSTOMER_NAME = 'payment/paypal_express/force_use_customer_name';
    const CONFIG_PATH_USE_SHIPPING_ADDRESS = 'payment/paypal_express/use_shipping_as_billing';
    /**
     * Magento 1.9 CE/1.14 EE has built-in support for skipping the review step.
     * The built-in functionality is limited to the checkout flow only, so this module
     * is still required for cases where the review step should be skipped when using
     * the express checkout button on the cart, but we will still honour the built-in
     * configuration value if it exists and the review step skip is disabled.
     *
     * On older versions, the config will be null and the review will always be skipped.
     * @return bool
     */
    public function skipReview()
    {
        return (bool)Mage::getStoreConfig(self::CONFIG_PATH_SKIP_REVIEW);
    }

    public function forceCustomerName(){
        return (bool)Mage::getStoreConfig(self::CONFIG_PATH_USE_CUSTOMER_NAME);
    }

    public function useShippingAsBilling(){
        return (bool)Mage::getStoreConfig(self::CONFIG_PATH_USE_SHIPPING_ADDRESS);
    }
}