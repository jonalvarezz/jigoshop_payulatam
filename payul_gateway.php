<?php
/*
Plugin Name: PayU Latam Jigoshop Gateway
Plugin URI: https://github.com/jonalvarezz/payu-latam-jigoshop-gateway
Description: PayU Latam Gateway for Jigoshop in Wordpress
Version: 1.0
Requires at least: 3.3
Tested up to: 3.8
Required Jigoshop Version: 1.3
Text Domain: payul-gateway
Domain Path: /languages/
Author: @jonalvarezz
Author URI: http://jonalvarezz.com
*/

add_action( 'plugins_loaded', 'init_payul_gateway' );
function init_payul_gateway() {

	// Sometimes Jigoshop is de-activated - do nothing without it
	if ( ! class_exists( 'jigoshop' )) return;

	// Add the gateway to JigoShop
	function add_payul_gateway( $methods ) {
		$methods[] = 'PayULatam_Gateway';
		return $methods;
	}
	add_filter( 'jigoshop_payment_gateways', 'add_payul_gateway', 3 );

	/**
	 * Main class definition
	 */
	class PayULatam_Gateway extends jigoshop_payment_gateway {

		public function __construct() {

			// load our text domains first for translations (constructor is called on the 'init' action hook)
			load_plugin_textdomain( 'payul_gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

			parent::__construct();

			$this->id			= 'payulatam';
			$this->icon 		= plugins_url( 'images/payu-latam-logo.png', __FILE__ );
			$this->liveurl 		= 'https://gateway.payulatam.com/ppp-web-gateway/';
			$this->testurl 		= 'https://stg.gateway.payulatam.com/ppp-web-gateway/';

			$this->enabled		= Jigoshop_Base::get_options()->get_option('payul_gateway_enabled');
			$this->testmode		= Jigoshop_Base::get_options()->get_option('payul_gateway_testmode');
			$this->title 		= Jigoshop_Base::get_options()->get_option('payul_gateway_title');
			$this->description 	= Jigoshop_Base::get_options()->get_option('payul_gateway_description');
	  		$this->userid		= Jigoshop_Base::get_options()->get_option('payul_gateway_userid');
	  		$this->key			= Jigoshop_Base::get_options()->get_option('payul_gateway_key');
	  		$this->account_id	= Jigoshop_Base::get_options()->get_option('payul_gateway_account_id');
	  		$this->tax_amount	= Jigoshop_Base::get_options()->get_option('payul_gateway_tax_amount');
	  		$this->lang			= Jigoshop_Base::get_options()->get_option('payul_gateway_lang');

	  		$this->responsepage	= Jigoshop_Base::get_options()->get_option('payul_gateway_response_page_id');

			// Actions
			// add_action('init', array(&$this, 'check_ipn_response') );
			// add_action('valid-po-ipn-request', array(&$this, 'successful_request') );
			add_action('receipt_payulatam', array(&$this, 'receipt_page'));

		}


		/**
		 * Default Option settings for WordPress Settings API using the Jigoshop_Options class
		 * Jigoshop will install, display, validate and save changes for our provided default options
		 *
		 * These will be installed on the Settings 'Payment Gateways' tab by the parent class
		 *
		 * See 'jigoshop/classes/jigoshop_options.class.php' for details on various option types
		 *
		 */
		protected function get_default_options() {

			$defaults = array();

			// Define the Section name for the Jigoshop_Options
			$defaults[] = array(
				'name' => __('PayU Latam', 'payul_gateway'),
				'type' => 'title',
				'desc' => __('This plugin sends the user to PayU Latam\'s web checkout, where the user could choice his payment method', 'payul_gateway')
			);

			// List each option in order of appearance with details
			$defaults[] = array(
				'name'		=> __('Enable PayU Latam Gateway','payul_gateway'),
				'id' 		=> 'payul_gateway_enabled',
				'std' 		=> 'no', /* newly added gateways to a site should be disabled by default */
				'type' 		=> 'checkbox',
				'choices'	=> array(
					'no'			=> __('No', 'payul_gateway'),
					'yes'			=> __('Yes', 'payul_gateway')
				)
			);

			$defaults[] = array(
				'name'		=> __('Method Title','payul_gateway'),
				'tip' 		=> __('This controls the title which the user sees during checkout.','payul_gateway'),
				'id' 		=> 'payul_gateway_title',
				'std' 		=> __('PayU Latam','payul_gateway'),
				'type' 		=> 'text'
			);

			$defaults[] = array(
				'name'		=> __('Description','payul_gateway'),
				'tip' 		=> __('This controls the description which the user sees during checkout.','payul_gateway'),
				'id' 		=> 'payul_gateway_description',
				'std' 		=> __('Pay with credit and debit card, and many other national options', 'payul_gateway'),
				'type' 		=> 'longtext'
			);

			$defaults[] = array(
				'name'		=> __('Merchant Id','payul_gateway'),
				'tip' 		=> __('You will find this information under PayU Latam > Configuration > Technical Information.','payul_gateway'),
				'id' 		=> 'payul_gateway_userid',
				'std' 		=> '500238',
				'type' 		=> 'text'
			);


			$defaults[] = array(
				'name'		=> __('API Key','payul_gateway'),
				'tip' 		=> __('You will find this information under PayU Latam > Configuration > Technical Information.','payul_gateway'),
				'id' 		=> 'payul_gateway_key',
				'std' 		=> '6u39nqhq8ftd0hlvnjfs66eh8c',
				'type' 		=> 'text'
			);


			$defaults[] = array(
				'name'		=> __('Account Id','payul_gateway'),
				'tip' 		=> __('It is your number of your Accound','payul_gateway'),
				'id' 		=> 'payul_gateway_account_id',
				'std' 		=> '500538',
				'type' 		=> 'text'
			);

			$defaults[] = array(
				'name'		=> __('Tax percent','payul_gateway'),
				'tip' 		=> __('Percentage tax amount to be included. Zero to none tax.','payul_gateway'),
				'id' 		=> 'payul_gateway_tax_amount',
				'std' 		=> 16,
				'type' 		=> 'natural'
			);

			$defaults[] = array(
				'name'		=> __('Responde Page','po_gateway'),
				'desc' 		=> __('Your customer will be returned to this page once the payment is done.', 'payul_gateway'),
				'id' 		=> 'payul_gateway_response_page_id',
				'type' 		=> 'single_select_page',
				'std' 		=> ''
			);

			$defaults[] = array(
				'name'		=> __('Display Language','payul_gateway'),
				'tip'		=> __('PayU Latam\s web checkout display langueage', 'payul_gateway'),
				'std' 		=> 'ES',
				'id' 		=> 'payul_gateway_lang',
				'type' 		=> 'select',
				'choices'	=> array(
					'EN'			=> __('English', 'payul_gateway'),
					'ES'			=> __('Spanish', 'payul_gateway'),
					'PT'			=> __('Portuguese', 'payul_gateway')
				)
			);

			$defaults[] = array(
				'name'		=> __('Test mode','payul_gateway'),
				'desc' 		=> __('Turn on to enable PayU Latam in test mode','payul_gateway'),
				'id' 		=> 'payul_gateway_testmode',
				'std' 		=> 'no',
				'type' 		=> 'checkbox',
				'choices'	=> array(
					'no'			=> __('No', 'payul_gateway'),
					'yes'			=> __('Yes', 'payul_gateway')
				)
			);

			return $defaults;

		}


		public function generate_po_form( $order_id ) {

			$order = new jigoshop_order( $order_id );

	        $gateway_url = ($this->testmode == 'yes') ? $this->testurl : $this->liveurl;

	        $refventa_aux = time();

	        //Tax handling .
	        $taxReturnBase = 0;
	        $tax = 0;

	        if( $this->tax_amount > 0 ) {
		        $taxReturnBase = $this->calc_taxReturnBase( $order->order_total, $this->tax_amount );
		        $tax = $order->order_total - $taxReturnBase;
	        }

			$po_args = array(

				// PayU Latam API
				'merchantId'			=> $this->userid,
				'signature'				=> $this->key,
				//'accountId'				=> $this->account_id, // Si se envia PayU muestra error de no corresponder la cuenta con comcercio
				'referenceCode'			=> "$order->id-$refventa_aux",
				'description'			=> $this->get_articles_detail($order),
				'amount'				=> $order->order_total,
				'tax'					=> number_format($tax, 2, '.', ''),
				'taxReturnBase'			=> number_format($taxReturnBase, 2, '.', ''),

				// Complementary info for PayU Latam
				'responseUrl' 			=> get_permalink($this->responsepage),
				'lng' 					=> $this->lang,
				'test'					=> ($this->testmode == 'yes') ? 1 : 0,
				'currency'				=> Jigoshop_Base::get_options()->get_option('jigoshop_currency'),
				'buyerFullName'			=> "$order->billing_first_name $order->billing_last_name",
				'buyerEmail'			=> $order->billing_email,
				'telephone'				=> $order->billing_phone,
				'billingAddress' 		=> $order->billing_address_1,
				'billingCity' 			=> $order->billing_city,
				'extra1'				=> "$order->billing_address_1, $order->billing_address_2, $order->billing_city"

			);

			$po_args['signature'] = $this->gen_digital_sign($po_args);
			$po_args_array = array();

			foreach ($po_args as $key => $value) {
				$po_args_array[] = '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'" />';
			}

			return '<form action="'. esc_url( $gateway_url ) .'" method="post" id="payl_payment_form">
					' . implode('', $po_args_array) . '
					<button type="submit" class="btn btn-large btn-success" id="submit_payment_form" name="submit" />
						'.__('Pagar via PayU Latam', 'payul_gateway').'
					</button>
					<a class="btn btn-large btn-warning" href="'.esc_url($order->get_cancel_order_url()).'">'.__('Cancel order &amp; restore cart', 'payul_gateway').'</a>

						<script type="text/javascript">
							jQuery(function(){
								jQuery("body").block(
									{
										message: "<img src=\"'.jigoshop::assets_url().'/assets/images/ajax-loader.gif\" alt=\"Redireccionando...\" />'.__('Gracias por su pedido. Ahora lo estamos redireccionando al sistema de pagos PayU Latam.', 'payul_gateway').'",
										overlayCSS:
										{
											background: "#000",
											opacity: 0.6
										},
										css: {
											padding:		20,
											textAlign:	  "center",
											color:		  "#555",
											border:		 "3px solid #aaa",
											backgroundColor:"#fff",
											cursor:		 "wait"
										}
									});
								jQuery("#submit_payment_form").click();
							});
						</script>
					</form>';
		}

		/**
		 * Process the payment and return the result
		 **/
		function process_payment( $order_id ) {

			$order = new jigoshop_order( $order_id );

			return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('pay'))))
			);

		}

		/**
		 * receipt_page
		 **/
		function receipt_page( $order ) {

			echo '<p>'.__('Gracias por tu orden, por favor de clic en el botón de abajo para pagar por medio de PayU Latam.', 'payul_gateway').'</p>';

			echo $this->generate_po_form( $order );

		}

		/**
		 * Successful Payment!
		 **/
		function successful_request( $posted ) {
			// TODO

		}

		/**
		 * There are no payment fields for paypal, but we want to show the description if set.
		 **/
		function payment_fields() {
			if ($this->description) echo wpautop(wptexturize($this->description));
		}

		/**
		 * Digital sign requeried for PayU Latam Api
		 **/
		function gen_digital_sign( $data ) {
			$uid = $data['merchantId'];
			$sign = $data['signature'];
			$rventa = $data['referenceCode'];
			$valor = $data['amount'];
			$mon = $data['currency'];

			$sign_s = "$sign~$uid~$rventa~$valor~$mon";

			return md5( $sign_s );
		}

		/**
		 * Calc the tax return base
		 **/
		function calc_taxReturnBase( $total, $tax_amount ) {
			$t = $total / (1 + $tax_amount/100);
			return $t;
		}


		function get_articles_detail($order) {
			$out  = '';
			if (sizeof($order->items)>0) : foreach ($order->items as $item) :

				$_product = $order->get_product_from_item( $item );

				if ($_product->exists() && $item['qty']) :

					$title = $_product->get_title();

					//if variation, insert variation details into product title
					if ($_product instanceof jigoshop_product_variation) {

						$title .= ' ('. jigoshop_get_formatted_variation($_product, $item['variation'], true) .')';

					}
					$amount = number_format( apply_filters( 'jigoshop_payu_adjust_item_price' ,$_product->get_price(), $item, 10, 2 ), 2);

					$out .= $item['qty'];
					$out .= " $title ($amount c/u); ";

				endif;
			endforeach; endif;

			$out .= ' - ' . get_bloginfo();

			if (strlen($out) > 250) {
				$out = sizeof($order->items) . ' productos - ' . get_bloginfo();
			}

			return $out;
		}

	}   /* End of Class definition for the Gateway */

}   /* End of init gateway function */
