<?php
class WC_LiqPayPrivat24_Payment_Gateway extends WC_Payment_Gateway{
	public function __construct(){
		$this->id                 = 'liqpayprivat24';
		$this->has_fields         = false;
		$this->method_title       = 'LiqPay Privat24';
		$this->method_description = __( 'LiqPay Privat24', 'woocommerce_liqpayprivat24' );
		$this->liveurl            = 'https://api.privatbank.ua/p24api/ishop';
		$this->init_form_fields();
		$this->init_settings();
		$this->title              = $this->settings['title'];
		$this->description        = $this->settings['description'];
		$this->public_key         = $this->settings['public_key'];
		$this->private_key        = $this->settings['private_key'];
		$this->icon               = apply_filters('woocommerce_liqpayprivat24_icon', plugins_url('assets/images/logo_liqpay.png',__FILE__));
		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
//		add_action( 'woocommerce_thankyou_'. $this->id, array( $this, 'thankyou_page' ),10,1 );
		add_action( 'woocommerce_receipt_'. $this->id, array( $this, 'thankyou_page' ) );
		add_action('woocommerce_api_'. $this->id, array($this, 'check_ipn_response'));
	}
	public function init_form_fields(){
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'On/Off', 'woocommerce_liqpayprivat24' ),
				'type'    => 'checkbox',
				'label'   => __( 'On', 'woocommerce_liqpayprivat24' ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce_liqpayprivat24' ),
				'type'        => 'text',
				'description' => __( 'Title will display on checkout page', 'woocommerce_liqpayprivat24' ),
				'default'     => 'Privat24',
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce_liqpayprivat24' ),
				'type'        => 'textarea',
				'description' => __( 'Description for checkout page', 'woocommerce_liqpayprivat24' ),
				'default'     => __( 'Pay with LiqPay ( Privat24 )', 'woocommerce_liqpayprivat24' ),
			),
			'public_key' => array(
				'title'       => __( 'Public Key public_key', 'woocommerce_liqpayprivat24' ),
				'type'        => 'textarea',
				'description' => __( 'You can get public key on the <a href="https://www.liqpay.ua/uk/adminbusiness/" target="_blank">LiqPay admin panel</a>', 'woocommerce_liqpayprivat24' ),
				'default'     => "sandbox_***",
			),
			'private_key' => array(
				'title'       => __( 'private Key private_key', 'woocommerce_liqpayprivat24' ),
				'type'        => 'textarea',
				'description' => __( 'You can get private key on the <a href="https://www.liqpay.ua/uk/adminbusiness/" target="_blank">LiqPay admin panel</a>', 'woocommerce_liqpayprivat24' ),
				'default'     => "sandbox_***",
			)
		);
	}
	function check_ipn_response(){
//		$f = fopen(__DIR__.'/test.data.json','a+');
//		ob_start();
//		var_dump($_POST);
//	    $content= ob_get_contents();
//		ob_clean();
//		fwrite($f,$content);
//		fclose($f);
		$data = $_POST['data'];
		$signature = $_POST['signature'] ?? '';
		$data_come = json_decode(base64_decode($data),true);
		$sign = base64_encode( sha1( $this->private_key . $data . $this->private_key , 1 ));
		if (isset($data_come["order_id"]) && $sign === $signature){
			$status = $data_come["status"];
			$action = $data_come["action"];
			$order  = wc_get_order($data_come["order_id"]);
			if($status == 'success' && $action == 'pay' && !is_wp_error($order)){
				$order->update_status('processing', __('Successfully paid', 'woocommerce_liqpayprivat24'));
				$order_info  = sprintf(__('LiqPay order ID #%s', 'woocommerce_liqpayprivat24'),$data_come["liqpay_order_id"])."\n";
				$order_info .= sprintf(__('LiqPay transaction ID #%s', 'woocommerce_liqpayprivat24'),$data_come["transaction_id"])."\n";
				$order_info .= sprintf(__('LiqPay pay type ID #%s', 'woocommerce_liqpayprivat24'),$data_come["paytype"])."\n";
				$order_info .= sprintf(__('Card mask %s', 'woocommerce_liqpayprivat24'),$data_come["sender_card_mask2"])."\n";
				$order_info .= sprintf(__('Card bank %s', 'woocommerce_liqpayprivat24'),$data_come["sender_card_bank"])."\n";
				$order_info .= sprintf(__('Card type %s', 'woocommerce_liqpayprivat24'),$data_come["sender_card_type"])."\n";
				$order->add_order_note( $order_info );
				$order->add_order_note( __('Successfully paid', 'woocommerce_liqpayprivat24') );
				if(isset($data_come["shipping_address"])){
					$delivery_type = $data_come["shipping_address"]["delivery_type"] ?? '';
					$address       = $data_come["shipping_address"]["address"] ?? '';
					$city          = $data_come["shipping_address"]["city"] ?? '';
					$region        = $data_come["shipping_address"]["region"] ?? '';
					if($delivery_type == "novaposhta"){
						$shipping_info = __('Shipping address updated in liqpay', 'woocommerce_liqpayprivat24')."\n";
						if(!empty($address)){
							$shipping_info .= sprintf(__('Address %s', 'woocommerce_liqpayprivat24'),$address)."\n";
						}
						if(!empty($city)){
							$shipping_info .= sprintf(__('City %s', 'woocommerce_liqpayprivat24'),$city)."\n";
						}
						if(!empty($region)){
							$shipping_info .= sprintf(__('Region %s', 'woocommerce_liqpayprivat24'),$region)."\n";
						}
						$order->add_order_note( $shipping_info );
					}
				}
			}
		}else{
			wp_die('IPN Request Failure');
		}
	}
	public function thankyou_page( $order_id ){
		global $woocommerce;
		$order  = wc_get_order( $order_id );
		$liqpay = new WoocLiqPay\LiqPay( $this->public_key, $this->private_key );
		$html   = $liqpay->cnb_form(
			array(
				'action'         => 'pay',
				'amount'         => $order->get_total(),
				'currency'       => get_woocommerce_currency(),
				'description'    => sprintf(__('Order ID #%s', 'woocommerce_liqpayprivat24'),$order_id),
				'order_id'       => $order_id,
				'version'        => '3',
				'server_url'     => home_url().'/?wc-api=liqpayprivat24'
			)
		);
		$woocommerce->cart->empty_cart();
		echo $html;
	}
	function process_payment($order_id){
		$order = wc_get_order($order_id);
		return array(
			'result' => 'success',
			'redirect'  => add_query_arg('order-pay', $order_id, add_query_arg('key', $order->order_key, get_permalink(wc_get_page_id('pay'))))
		);
	}
}
