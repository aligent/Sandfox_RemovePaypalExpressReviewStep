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
}
