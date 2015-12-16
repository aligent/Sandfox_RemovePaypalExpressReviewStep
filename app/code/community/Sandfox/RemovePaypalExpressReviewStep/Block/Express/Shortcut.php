<?php

/**
 * Paypal expess checkout shortcut link
 */
class Sandfox_RemovePaypalExpressReviewStep_Block_Express_Shortcut extends Mage_Paypal_Block_Express_Shortcut
{
    /**
     * Start express action
     *
     * @var string
     */
    protected $_startAction = 'paypal/express/start/button/1';
}
