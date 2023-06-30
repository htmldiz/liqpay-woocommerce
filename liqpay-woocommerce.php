<?php
/*
    Plugin Name: LiqPay Privat24 Woocommerce
    Plugin URI:
    description:
    Version: 1.2
    Author: {Marcus code}
    Author URI: https://github.com/htmldiz
    License: GPL3
*/
require_once __DIR__."/vendor/autoload.php";
class LiqPayWooc{
	public $gateway_method_name = 'WC_LiqPayPrivat24_Payment_Gateway';
	public function __construct(){
		add_action('plugins_loaded', array($this,'init_gateway'), 0);
		add_filter( 'woocommerce_payment_gateways', array($this,'woocommerce_payment_gateways'),10,1 );
	}
	public function init_gateway(){
		if(!class_exists('WC_Payment_Gateway')){
			return false;
		}
		require __DIR__."/".$this->gateway_method_name.".php";
	}
	public function woocommerce_payment_gateways($methods){
		$methods[] = $this->gateway_method_name;
		return $methods;
	}
}
new LiqPayWooc();
