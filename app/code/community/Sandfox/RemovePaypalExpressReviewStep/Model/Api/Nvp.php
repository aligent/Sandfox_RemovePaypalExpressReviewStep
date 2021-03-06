<?php

class Sandfox_RemovePaypalExpressReviewStep_Model_Api_Nvp extends Mage_Paypal_Model_Api_Nvp
{
    /**
     * Prepare response for shipping options callback
     * Include CALLBACKVERSION if no valid options
     * This is required to allow paypal to prevent submission without a valid address
     * @link https://developer.paypal.com/docs/classic/express-checkout/integration-guide/ECInstantUpdateAPI/
     *
     * @return string
     */
    public function formatShippingOptionsCallback()
    {
        $response = array();
        if (!$this->_exportShippingOptions($response)) {
            $response['NO_SHIPPING_OPTION_DETAILS'] = '1';
            $response['CALLBACKVERSION'] = $this->getVersion();
        }
        $response = $this->_addMethodToRequest(self::CALLBACK_RESPONSE, $response);
        return $this->_buildQuery($response);
    }

    /**
     * SetExpressCheckout call
     * @link https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_SetExpressCheckout
     * TODO: put together style and giropay settings
     * Only override shipping if we have come from full checkout.
     */
    public function callSetExpressCheckout()
    {
        $this->_prepareExpressCheckoutCallRequest($this->_setExpressCheckoutRequest);
        $request = $this->_exportToRequest($this->_setExpressCheckoutRequest);
        $this->_exportLineItems($request);

        // import/suppress shipping address, if any
        $options = $this->getShippingOptions();

        // Check if we are using express checkout.
        $expressCheckoutTrue = (bool)Mage::app()->getRequest()->getParam('button');

        // Don't override shipping if we came from express checkout
        if ($this->getAddress() && !$expressCheckoutTrue) {
            $request = $this->_importAddresses($request);
            $request['ADDROVERRIDE'] = 1;
        } elseif ($options && (count($options) <= 10)) { // doesn't support more than 10 shipping options
            $request['CALLBACK'] = $this->getShippingOptionsCallbackUrl();
            $request['CALLBACKTIMEOUT'] = 6; // max value
            $request['MAXAMT'] = $request['AMT'] + 999.00; // it is impossible to calculate max amount
            $this->_exportShippingOptions($request);
        }

        // Disable the note to seller as it does not get passed back to magento
        $request['ALLOWNOTE'] = 0;

        // add recurring profiles information
        $i = 0;
        foreach ($this->_recurringPaymentProfiles as $profile) {
            $request["L_BILLINGTYPE{$i}"] = 'RecurringPayments';
            $request["L_BILLINGAGREEMENTDESCRIPTION{$i}"] = $profile->getScheduleDescription();
            $i++;
        }

        $response = $this->call(self::SET_EXPRESS_CHECKOUT, $request);
        $this->_importFromResponse($this->_setExpressCheckoutResponse, $response);
    }
}
