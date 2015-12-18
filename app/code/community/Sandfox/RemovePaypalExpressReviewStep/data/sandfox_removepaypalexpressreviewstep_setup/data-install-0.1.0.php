<?php
/** @var Mage_Core_Model_Resource_Setup $this */

$this->startSetup();
$this->setConfigData('payment/paypal_express/transfer_shipping_options', '1');
$this->setConfigData('payment/paypal_express/line_items_enabled', '1');
$this->setConfigData('payment/settings_ec_advanced/transfer_shipping_options', '1');
$this->setConfigData('payment/settings_ec_advanced/line_items_enabled', '1');
Mage::getConfig()->loadDb();
$this->endSetup();
