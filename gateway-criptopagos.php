<?php

class WC_Cryptopagos_Gateway extends WC_Payment_Gateway
{
    
    public function __construct()
    {
        
        $this->id                 = 'cryptopagos';
        $this->method_title       = 'Cryptopagos';
        $this->method_description = 'Plataforma para realizar pagos en Linea a través de Criptopagos';
        
        $this->gatewayURL     = 'https://cryptopagos-payment.dreamlopers.com/';
        $this->gatewayURLTest = "https://cryptopagos-payment-sandbox.dreamlopers.com/";
        
        $this->supports = array(
            'products'
        );
        
        $this->init_form_fields();
        
        $this->init_settings();
        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled     = $this->get_option('enabled');
        $this->testmode    = 'yes' === $this->get_option('testmode');
        $this->accountID   = $this->get_option('accountID');
        $this->apiKey      = $this->get_option('apiKey');
        $this->merchantID  = $this->get_option('merchantID');
        $this->callbackURL = $this->get_option('callbackURL');
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options'
        ));
        
        add_action('wp_enqueue_scripts', array(
            $this,
            'payment_scripts'
        ));
        
        add_action('woocommerce_api_cryptopagos', array(
            $this,
            'webhook'
        ));
        
    }
    
    /*Plugin options*/
    public function init_form_fields()
    {
        
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Habilitar/Deshabilitar',
                'label' => 'Habilita o deshabilita la plataforma de pagos Cryptopagos.',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => 'Título',
                'type' => 'text',
                'description' => 'Esto muestra el título al momento de elegir el método de pago.',
                'default' => 'Cryptopagos',
                'desc_tip' => true
            ),
            'description' => array(
                'title' => 'Descripción',
                'type' => 'textarea',
                'description' => 'Esto muestra la descripción al momento de elegir el método de pago.',
                'default' => 'Paga con tus criptomonedas a través de nuestra plataforma de pagos Criptopagos.'
            ),
            'testmode' => array(
                'title' => 'Modo de Pruebas',
                'label' => 'Habilita El modo de pruebas',
                'type' => 'checkbox',
                'description' => 'Coloca el modo de pruebas, haciendo uso de la URL de pruebas.',
                'default' => 'no',
                'desc_tip' => true
            ),
            'accountID' => array(
                'title' => 'ID de la Cuenta',
                'type' => 'text'
            ),
            'merchantID' => array(
                'title' => 'ID del Comercio',
                'type' => 'text'
            ),
            
            'apiKey' => array(
                'title' => 'API Key',
                'type' => 'text'
            ),
            'callbackURL' => array(
                'title' => 'Callback URL',
                'type' => 'text',
                'description' => 'Url de Retorno luego de procesado el pago.',
                'default' => get_site_url() . '/wc-api/cryptopagos/'
            )
        );
        
    }
    
    /* Add New Form */
    public function payment_fields()
    {
        
        if ($this->description) {
            
            if ($this->testmode) {
                
                $this->description .= ' <strong> Modo de Pruebas Habilitado.</strong>';
                $this->description = trim($this->description);
                
            }
            
            echo wpautop(wp_kses_post($this->description));
        }
?>

			<form method="post" action="<?= $this->testmode ? $this->gatewayURLTest : $this->gatewayURL ?>" id="formCrypto">
			  <input name="merchantID"	type="hidden"	value="<?= $this->merchantID ?>" 	id="mechantID" >
			  <input name="accountID"	type="hidden"	value="<?= $this->accountID ?>" 	id="accountID" >
			  <input name="apiKey"		type="hidden"	value="<?= $this->apiKey ?>" 		id="apiKey">
			  <input name="amount"		type="hidden"	value="<?= WC()->cart->total ?>" 	id="amount" >
			  <input name="orderID"		type="hidden"	value="<?= $_GET['order_id'] ?>" "id="orderID" >
			</form>

<?php
        
        if (!empty($_GET['order_id'])) { // post proccess payment
            
?>
				<script>

					function stateChange() {
		    			setTimeout(function () {
		        			document.getElementById("formCrypto").submit();
		    			},1000);
					}

					stateChange();

				</script>

<?php
            
        }
        
        
    }
    
    /* Validate fields */
    public function validate_fields()
    {
        
        return true;
        
    }
    
    /* Place Order action */
    public function process_payment($order_id)
    {
        
        global $woocommerce;
        
        
        $order = wc_get_order($order_id);
        
        $args = array();
        
        $response = wp_remote_post($this->testmode ? $this->gatewayURLTest : $this->gatewayURL, $args);
        
        
        if (!is_wp_error($response)) {
            
            return array(
                'result' => 'success',
                'redirect' => "?order_id=" . $order_id
            );
            
        } else {
            
            wc_add_notice('Error de conexión', 'error');
            return;
            
        }
        
    }
    
    public function webhook()
    {
        global $woocommerce;
        
        $order = wc_get_order($_GET['orderID']);
        $status= $_GET['status'];
        $apiKey= $_GET['apiKey'];

        if($status=="PAID" and $apiKey==$this->apiKey) {

       	    $order->payment_complete();
       	    $order->reduce_order_stock();
       	    $order->add_order_note( 'La orden fue pagada con CriptoPagos.', true );
       	    $order->update_status('processing', 'La orden se encuentra en proceso, esperando ser completada.');
			$woocommerce->cart->empty_cart();

        }
        else{
       	    $order->update_status('failed', 'La orden  no pudo ser procesada, verifique el pago.');
        }

        update_option('webhook_debug', $_GET);
        wp_redirect($this->get_return_url( $order ));
        exit;
        
    }
}
