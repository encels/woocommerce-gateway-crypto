<?php
/*
 * Plugin Name: WooCommerce Cryptopagos Payment Gateway
 * Plugin URI: https://cryptopagos-payment.dreamlopers.com
 * Description: Plataforma de Pagos a través de Criptopagos
 * Author: Dreamlopers
 * Author URI: http://dreamlopers.com
 * Version: 0.1
 *
 */
 
/* Load main class to plugin setup */
add_action( 'plugins_loaded', 'cryptopagos_init_gateway_class' );

function cryptopagos_init_gateway_class() {
	
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	
	/* Add Woocommerce Custom Gateway Class */
	include_once( 'gateway-criptopagos.php' );
	
	/* Register new gateway to WC */
	add_filter( 'woocommerce_payment_gateways', 'cryptopagos_add_gateway_class' );

	function cryptopagos_add_gateway_class( $gateways ) {
		
		$gateways[] = 'WC_Cryptopagos_Gateway'; 
		return $gateways;

	}
	
}