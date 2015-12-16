<?php

class Sandfox_RemovePaypalExpressReviewStep_Model_Api_Nvp extends Mage_Paypal_Model_Api_Nvp
{
    /**
     * Prepare response for shipping options callback
     * Add CALLBACKVERSION if no valid options
     * This will allow paypal to prevent submission without a valid address
     *
     * @return string
     */
    public function formatShippingOptionsCallback()
    {
        $response = array();
        if (!$this->_exportShippingOptions($response)) {
            $response['NO_SHIPPING_OPTION_DETAILS'] = '1';
            $response['CALLBACKVERSION'] = '61.0';
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
        if ($this->getAddress() && !$expressCheckoutTrue) {
            $request = $this->_importAddresses($request);
            $request['ADDROVERRIDE'] = 1;
        } elseif ($options && (count($options) <= 10)) { // doesn't support more than 10 shipping options
            $request['CALLBACK'] = $this->getShippingOptionsCallbackUrl();
            $request['CALLBACKTIMEOUT'] = 6; // max value
            $request['MAXAMT'] = $request['AMT'] + 999.00; // it is impossible to calculate max amount
            $this->_exportShippingOptions($request);
        }

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
