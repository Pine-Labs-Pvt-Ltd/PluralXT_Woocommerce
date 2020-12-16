<?php
/*
Plugin Name: Plural XT by Pine Labs for WooCommerce
Plugin URI: https://www.pinelabs.com/
Description: Plural XT by Pine Labs for WooCommerce.
Version: 1.0
Author: Pine Labs
Author URI: https://www.pinelabs.com/
Copyright: © 2020 Pine Labs. All rights reserved.
*/

add_action('plugins_loaded', 'woocommerce_pluralxtpinelabs_init', 0);

function woocommerce_pluralxtpinelabs_init()
{
	if (!class_exists('WC_Payment_Gateway'))
	{
		return;
	}  

	/**
	* Localisation
	*/
	load_plugin_textdomain('wc-pluralxtpinelabs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
	
	/**
	* Gateway class
	*/
	class WC_PluralXTPinelabs extends WC_Payment_Gateway
	{		
		public function __construct()
		{
			$this->id = 'pluralxtpinelabs';
			$this->method_title = __('Plural XT by Pine Labs', 'pluralxtpinelabs');
			$this->method_description = __('Pay securely via Plural XT by Pine Labs.', 'pluralxtpinelabs');
			$this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/pluralxtpinelabs.png';
			$this->has_fields = false;
			$this->init_form_fields();
			$this->init_settings();
			$this->title = 'Plural XT by Pine Labs';
			$this->description = $this->settings['description'];
			$this->gateway_module = $this->settings['gateway_module'];
			$this->redirect_page_id = $this->settings['redirect_page_id'];
			$this->secret_key = $this->settings['secret_key'];
			$this->preferred_gateway = $this->settings['preferred_gateway'];
			$this->msg['message'] = "";
			$this->msg['class'] = "";

			add_action('init', array(&$this, 'check_pluralxtpinelabs_response'));
		
			//update for woocommerce >2.0
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_pluralxtpinelabs_response' ) );

			add_action('valid-pluralxtpinelabs-request', array(&$this, 'SUCCESS'));

			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) 
			{
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			} 
			else 
			{
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}

			add_action('woocommerce_receipt_pluralxtpinelabs', array(&$this, 'receipt_page'));

			add_action('woocommerce_thankyou_pluralxtpinelabs',array(&$this, 'thankyou_page'));
		}
    
	    function init_form_fields()
	    {
	    	$this->form_fields = array(
					'enabled' => array(
						'title' => __('Enable/Disable', 'pluralxtpinelabs'),
						'type' => 'checkbox',
						'label' => __('Enable Plural XT', 'pluralxtpinelabs'),
						'default' => 'no'
					),
					'description' => array(
						'title' => __('Description:', 'pluralxtpinelabs'),
						'type' => 'textarea',
						'description' => __('This controls the description which the user sees during checkout.', 'pluralxtpinelabs'),
						'default' => __('Pay securely via Plural XT by Pine Labs.', 'pluralxtpinelabs')
					),
					'gateway_module' => array(
						'title' => __('Gateway Mode', 'pluralxtpinelabs'),
						'type' => 'select',
						'options' => array("0"=>"Select","sandbox"=>"Sandbox","production"=>"Production"),
						'description' => __('Mode of gateway subscription','pluralxtpinelabs')
					),
					'ppc_MerchantID' => array(
						'title' => __('Merchant ID', 'pluralxtpinelabs'),
						'type' => 'text',
						'description' =>  __('Merchant ID', 'pluralxtpinelabs')
					),
					'secret_key' => array(
						'title' => __('Secret Key', 'pluralxtpinelabs'),
						'type' => 'text',
						'description' =>  __('Secret Key', 'pluralxtpinelabs')
					),
					'ppc_PayModeOnLandingPage' => array(
						'title' => __('Payment Modes', 'pluralxtpinelabs'),
						'type' => 'text',
						'description' =>  __('Payment Modes as Comma Separated Values', 'pluralxtpinelabs')
					),
					'ppc_MerchantAccessCode' => array(
						'title' => __('Merchant Access Code', 'pluralxtpinelabs'),
						'type' => 'text',
						'description' =>  __('Merchant Access Code', 'pluralxtpinelabs')
					),
					'redirect_page_id' => array(
						'title' => __('Return Page'),
						'type' => 'select',
						'options' => $this->get_pages('Select Page'),
						'description' => "Page to redirect to after transaction from Payment Gateway"
					),
					'preferred_gateway' => array(
						'title' => __('Preferred Payment Gateway', 'pluralxtpinelabs'),
						'type' => 'select',
						'options' => array(
											"NONE"                  => "Select",
											"AMEX"                  => "AMEX",
											"AMEX_ENHANCED"         => "AMEX_ENHANCED",
											"AXIS"                  => "AXIS",
											"AXISB24"               => "AXISB24",
											"BANKTEK"               => "BANKTEK",
											"BFL"                   => "BFL",
											"BHARATQR_HDFC"         => "BHARATQR_HDFC",
											"BILLDESK"              => "BILLDESK",
											"BOB"                   => "BOB",
											"CCAVENUE_NET_BANKING"  => "CCAVENUE_NET_BANKING",
											"CITI"                  => "CITI",
											"CITRUS_NET_BANKING"    => "CITRUS_NET_BANKING",
											"CORP"                  => "CORP",
											"DEBIT_PIN_FSS"         => "DEBIT_PIN_FSS",
											"EBS_NETBANKING"        => "EBS_NETBANKING",
											"EDGE"                  => "EDGE",
											"FEDERAL"               => "FEDERAL",
											"FSS_NETBANKING"        => "FSS_NETBANKING",
											"HDFC"                  => "HDFC",
											"HDFC_DEBIT_EMI"        => "HDFC_DEBIT_EMI",
											"HDFC_PRIZM"            => "HDFC_PRIZM",
											"HSBC"                  => "HSBC",
											"ICICI"                 => "ICICI",
											"ICICI_SHAKTI"          => "ICICI_SHAKTI",
											"IDBI"                  => "IDBI",
											"LVB"                   => "LVB",
											"MASHREQ"               => "MASHREQ",
											"OPUS"                  => "OPUS",
											"PAYTM"                 => "PAYTM",
											"PayU"                  => "PayU",
											"RAZOR_PAY"             => "RAZOR_PAY",
											"SBI"                   => "SBI",
											"SBI87"                 => "SBI87",
											"SI_HDFC"               => "SI_HDFC",
											"SI_PAYNIMO"            => "SI_PAYNIMO",
											"UBI"                   => "UBI",
											"UPI_AXIS"              => "UPI_AXIS",
											"UPI_HDFC"              => "UPI_HDFC",
											"WALLET_PAYZAPP"        => "WALLET_PAYZAPP",
											"WALLET_PHONEPE"        => "WALLET_PHONEPE",
											"YES"                   => "YES",
											"ZEST_MONEY"            => "ZEST_MONEY"
						),
						'description' => __('Preferred Payment Gateway','pluralxtpinelabs')
					)
	          	);
	    }
    
		/**
		* Admin Panel Options
		**/
	    public function admin_options()
	    {
			echo '<h3>'.__('Plural XT by Pine Labs', 'pluralxtpinelabs').'</h3>';
			echo '<p>'.__('Pine Labs is one of the leading payments solutions companies in India.').'</p>';
			echo '<table class="form-table">';
		
			$this->generate_settings_html();
		
			echo '</table>';
	    }
		
		/**
		*  If There are no payment fields show the description if set.
		**/
	    function payment_fields()
	    {
			if($this->description) echo wpautop(wptexturize($this->description));
	    }
		
		/**
		* Receipt Page
		**/
	    function receipt_page($order)
	    {
			$this->generate_pluralxtpinelabs_form($order);
	    }
    
		/**
		* Process the payment and return the result
		**/   
	    function process_payment($order_id)
	    {
            $order = new WC_Order($order_id);

            if ( version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                return array(
                    'result' => 'success',
                    'redirect' => add_query_arg('order', $order->get_id(),
                        add_query_arg('key', $order->get_order_key(), $order->get_checkout_payment_url(true)))
                );
            }
            else 
            {
                return array(
                    'result' => 'success',
                    'redirect' => add_query_arg('order', $order->get_id(),
                        add_query_arg('key', $order->get_order_key(), get_permalink(get_option('woocommerce_pay_page_id'))))
                );
            }
        }

        /**
		* Handle response from pg.
		**/
	    function check_pluralxtpinelabs_response()
	    {
			global $woocommerce;

			$order_id = '';

			// if (isset($_POST['unique_merchant_txn_id']))
			// {
			// 	$merchantTxnID = $_POST['unique_merchant_txn_id'];
			if (isset($_POST['order_id']))
			{
				$merchantTxnID = $_POST['order_id'];
				$order_id = explode('_', $merchantTxnID);
				$order_id = (int)$order_id[0];    //get rid of time part
			}

			if ($order_id != '')
			{
				$order = new WC_Order($order_id);

				ksort($_POST);		

				$strPostData = "";

				foreach ($_POST as $key => $value)
				{	
					if ($key != "dia_secret" && $key!= "dia_secret_type")
					{
						$strPostData .= $key . "=" . $value . "&";
					}
				}

				$strPostData = substr($strPostData, 0, -1);
				
				$responseHash = strtoupper(hash_hmac("sha256", $strPostData, $this->Hex2String($this->settings['secret_key'])));

				if ($responseHash == $_POST['dia_secret'])
				{
					// if (isset($_POST['pine_pg_txn_status']) && $_POST['pine_pg_txn_status'] == '4'
					// 	&& isset($_POST['txn_response_code']) && $_POST['txn_response_code'] == '1')
					if (isset($_POST['payment_status']) && $_POST['payment_status'] == 'CAPTURED'
						&& isset($_POST['payment_response_code']) && $_POST['payment_response_code'] == '1')
					{					
						$amount = floatval($_POST['amount_in_paisa']) / 100.0;
						
						$this->msg['class'] = 'success';
						$this->msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful with the following order details: 
								<br> 
									Order Id: $order_id <br/>
									Amount: ‎₹ $amount 
								<br />		
							We will process and ship your order soon.";

						if ($order->status == 'processing' || $order->status == 'completed')
						{
							//do nothing
						}
						else
						{
							//complete the order
							$order->payment_complete();
							$order->add_order_note('Payment via Plural XT Successful. Transaction Id: '. $merchantTxnID);
							$order->add_order_note($this->msg['message']);
							$woocommerce->cart->empty_cart();
						}
					}
					else
					{
			    		$this->msg['class'] = 'error';
						$this->msg['message'] = "Thank you for shopping with us. However, the payment failed.";
						$order->update_status('failed');
						$order->add_order_note('Failed');
						$order->add_order_note($this->msg['message']);
						//Here you need to put in the routines for a failed
						//transaction such as sending an email to customer
						//setting database status etc etc			
					}
				}
				else
				{
					//tampered
					$this->msg['class'] = 'error';
					$this->msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
					$order->update_status('failed');
					$order->add_order_note('Data tampered.');
					$order->add_order_note($this->msg['message']);						
				}
			}
			else
			{
				$this->msg['class'] = 'error';
				
				if ($this->msg['message'] == "")
				{
					$this->msg['message'] = "Error processing payment.";
				}
			}

			//manage messages
			if (function_exists('wc_add_notice'))
			{
				wc_add_notice($this->msg['message'], $this->msg['class']);
			}
			else
			{
				if ($this->msg['class'] == 'success')
				{
					$woocommerce->add_message($this->msg['message']);
				}
				else
				{
					$woocommerce->add_error($this->msg['message']);
				}

				$woocommerce->set_messages();
			}
				
			$redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);

			wp_redirect($redirect_url);
			
			exit;			
	    }
        
	    public function generate_pluralxtpinelabs_form($order_id)
	    {	
			$return_url = get_site_url();

			$return_url .= '/wc-api/' . get_class( $this );	      	//for callback

			// //For wooCoomerce 2.0
			// $return_url = add_query_arg( 'wc-api', get_class( $this ), $return_url );
			
			$order = new WC_Order($order_id);	
			
			$order_id = $order_id . '_' . date("ymdHis");
	      
			$ppc_MerchantProductInfo = '';

	      	$items = $order->get_items();

			foreach ($items as $item) 
			{
				$ppc_MerchantProductInfo .= $item->get_name() . '|';
			}

			$ppc_MerchantProductInfo = substr($ppc_MerchantProductInfo, 0, -1);

			$product_info_data = new \stdClass();

			$i = 0;
			
			foreach ($items as $item) 
			{
		    	$product_id = $item->get_product_id();
		    	$product_variation_id = $item->get_variation_id();

				// Check if product has variation.
				if ($product_variation_id)
				{ 
					$product = wc_get_product($product_variation_id);
				}
				else
				{
					$product = wc_get_product($product_id);
				}

				$product_details = new \stdClass();
				// Set Product Code as SKU 
				$product_details->product_code = $product->get_sku();
				$product_details->product_amount = intval(floatval($product->get_price()) * 100);

				$product_info_data->product_details[$i] = $product_details;

				$i++;
			}
			
			$ppc_Amount = intval(floatval($order->order_total) * 100);
			
			$ppc_CustomerFirstName 	= '';
			$ppc_CustomerState 		= '';
			$ppc_CustomerCountry 	= '';
			$ppc_CustomerCity 		= '';
			$ppc_CustomerLastName 	= '';
			$ppc_CustomerAddress1 	= '';
			$ppc_CustomerAddress2 	= '';
			$ppc_CustomerAddressPIN = '';
			$ppc_CustomerEmail 		= '';
			$ppc_CustomerMobile 	= '';

			if ($order->has_billing_address())
			{
				$ppc_CustomerEmail 		= $order->get_billing_email();
				$ppc_CustomerFirstName 	= $order->get_billing_first_name();
				$ppc_CustomerLastName 	= $order->get_billing_last_name();
				$ppc_CustomerMobile 	= $order->get_billing_phone();
				$ppc_CustomerAddress1 	= $order->get_billing_address_1();
				$ppc_CustomerAddress2 	= $order->get_billing_address_2();
				$ppc_CustomerCity 		= $order->get_billing_city();
	        	$ppc_CustomerState 		= WC()->countries->states[$order->get_billing_country()][$order->get_billing_state()];
	        	$ppc_CustomerCountry 	= WC()->countries->countries[$order->get_billing_country()];
				$ppc_CustomerAddressPIN = $order->get_billing_postcode();	        	
			}

			$ppc_ShippingFirstName 	 = '';
			$ppc_ShippingLastName 	 = '';
			$ppc_ShippingAddress1 	 = '';
			$ppc_ShippingAddress2 	 = '';
			$ppc_ShippingCity 		 = '';
			$ppc_ShippingState 		 = '';
			$ppc_ShippingCountry 	 = '';
			$ppc_ShippingZipCode 	 = '';

			if ($order->has_shipping_address())
			{
				$ppc_ShippingFirstName 	 = $order->get_shipping_first_name();
				$ppc_ShippingLastName 	 = $order->get_shipping_last_name();
				$ppc_ShippingAddress1 	 = $order->get_shipping_address_1();
				$ppc_ShippingAddress2 	 = $order->get_shipping_address_2();			
	        	$ppc_ShippingCity 		 = $order->get_shipping_city();
	        	$ppc_ShippingState 		 = WC()->countries->states[$order->get_shipping_country()][$order->get_shipping_state()];
	        	$ppc_ShippingCountry 	 = WC()->countries->countries[$order->get_shipping_country()];
	        	$ppc_ShippingZipCode 	 = $order->get_shipping_postcode();
			}			

			$ppc_UniqueMerchantTxnID	= $order_id;
			$ppc_MerchantID 			= $this->settings['ppc_MerchantID'];
			$ppc_MerchantAccessCode 	= $this->settings['ppc_MerchantAccessCode'];
			$ppc_MerchantReturnURL 		= $return_url;
			$ppc_PayModeOnLandingPage	= $this->settings['ppc_PayModeOnLandingPage'];
			// $ppc_UdfField1 				= "WooCommerce_v_3.6.3";

			$merchant_data = new \stdClass();		
			$merchant_data->merchant_return_url = $ppc_MerchantReturnURL;
			$merchant_data->merchant_access_code = $ppc_MerchantAccessCode;
			$merchant_data->order_id = $ppc_UniqueMerchantTxnID;
			$merchant_data->merchant_id = $ppc_MerchantID;

			$payment_info_data = new \stdClass();
			$payment_info_data->amount = $ppc_Amount;
			$payment_info_data->currency_code = "INR";
			$payment_info_data->preferred_gateway = $this->preferred_gateway;
			$payment_info_data->order_desc = $ppc_MerchantProductInfo;

			$customer_data = new \stdClass();
			// $customer_data->customer_id = $order->get_user_id();
			$customer_data->customer_ref_no = $order->get_user_id();
			// $customer_data->mobile_no = $ppc_CustomerMobile;
			$customer_data->mobile_number = $ppc_CustomerMobile;
			$customer_data->email_id = $ppc_CustomerEmail;
			$customer_data->first_name = $ppc_CustomerFirstName;
			$customer_data->last_name = $ppc_CustomerLastName;
			$customer_data->country_code = "91";

			$billing_address_data = new \stdClass();
			$billing_address_data->first_name = $ppc_CustomerFirstName;
			$billing_address_data->last_name = $ppc_CustomerLastName;
			$billing_address_data->address1 = $ppc_CustomerAddress1;
			$billing_address_data->address2 = $ppc_CustomerAddress2;
			$billing_address_data->address3 = "";
			$billing_address_data->pincode = $ppc_CustomerAddressPIN;
			$billing_address_data->city = $ppc_CustomerCity;
			$billing_address_data->state = $ppc_CustomerState;
			$billing_address_data->country = $ppc_CustomerCountry;

			$shipping_address_data = new \stdClass();
			$shipping_address_data->first_name = $ppc_ShippingFirstName;
			$shipping_address_data->last_name = $ppc_ShippingLastName;
			$shipping_address_data->address1 = $ppc_ShippingAddress1;
			$shipping_address_data->address2 = $ppc_ShippingAddress2;
			$shipping_address_data->address3 = "";
			$shipping_address_data->pincode = $ppc_ShippingZipCode;
			$shipping_address_data->city = $ppc_ShippingCity;
			$shipping_address_data->state = $ppc_ShippingState;
			$shipping_address_data->country = $ppc_ShippingCountry;

			$additional_info_data = new \stdClass();
			$additional_info_data->rfu1 = '';//$ppc_UdfField1;      

			$orderData = new \stdClass();

			$orderData->merchant_data = $merchant_data;
			$orderData->payment_info_data = $payment_info_data;
			$orderData->customer_data = $customer_data;
			$orderData->billing_address_data = $billing_address_data;
			$orderData->shipping_address_data = $shipping_address_data;
			$orderData->product_info_data = $product_info_data;
			$orderData->additional_info_data = $additional_info_data;

			$orderData = json_encode($orderData);

			$requestData = new \stdClass();
			$requestData->request = base64_encode($orderData);
			
			$x_verify = strtoupper(hash_hmac("sha256", $requestData->request, $this->Hex2String($this->settings['secret_key'])));
			
			$requestData = json_encode($requestData);
			
			$pluralxtHostUrl = 'https://paymentoptimizer.pinepg.in';
			
			if ($this->gateway_module == 'sandbox')
			{
				$pluralxtHostUrl = 'https://paymentoptimizertest.pinepg.in';
			}

			$orderCreationUrl = $pluralxtHostUrl . '/api/v2/order/create';
			$order_creation = $this->callOrderCreationAPI($orderCreationUrl, $requestData, $x_verify);

			$response = json_decode($order_creation, true);
			$response_code = $response['response_code'];
			$token = $response['token'];

			if ($response_code != 1 || empty($token))
			{
	    		$this->msg['class'] = 'error';
				$this->msg['message'] = "Error processing payment.";
				$order->update_status('failed');
				$order->add_order_note('Order creation from pg failed');
				$order->add_order_note($this->msg['message']);

				if (function_exists('wc_add_notice'))
				{
					wc_add_notice($this->msg['message'], $this->msg['class']);
				}
				else
				{
					if ($this->msg['class'] == 'success')
					{
						$woocommerce->add_message($this->msg['message']);
					}
					else
					{
						$woocommerce->add_error($this->msg['message']);
					}

					$woocommerce->set_messages();
				}
					
				$redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);

				wp_redirect($redirect_url);
				
				exit;
			}
			
			$payment_redirect_url = $pluralxtHostUrl . '/pinepg/v2/process/payment/redirect?orderToken=' . $token . '&paymentmodecsv=' . $ppc_PayModeOnLandingPage;
			
			wp_redirect($payment_redirect_url);

			exit;

	    }
    
	    function get_pages($title = false, $indent = true) 
	    {
			$wp_pages = get_pages('sort_column=menu_order');
			$page_list = array();
			
			if ($title) 
			{
				$page_list[] = $title;
			}

			foreach ($wp_pages as $page)
			{
				$prefix = '';
				// show indented child pages?
				if ($indent) 
				{
					$has_parent = $page->post_parent;

					while ($has_parent) 
					{
						$prefix .=  ' - ';
						$next_page = get_page($has_parent);
						$has_parent = $next_page->post_parent;
					}
				}
				// add to page list array array
				$page_list[$page->ID] = $prefix . $page->post_title;
			}
			
			return $page_list;
	    }

		function Hex2String($hex)
		{
		    $string = '';

		    for ($i = 0; $i < strlen($hex) - 1; $i += 2) 
		    {
		        $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
		    }
		    
		    return $string;
		}

		function callOrderCreationAPI($url, $data, $x_verify)
		{
		   	$curl = curl_init();
			
			curl_setopt($curl, CURLOPT_POST, 1);
			
			if ($data)
			{
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			}
			// OPTIONS:
			curl_setopt($curl, CURLOPT_URL, $url);

			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			  'X-VERIFY: ' . $x_verify,
			  'Content-Type: application/json',
			));

			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

			// EXECUTE:
			$result = curl_exec($curl);

			if (!$result) {
				die("Connection Failure");
			}

			curl_close($curl);

			return $result;
		}
	}


	/**
	* Add the Gateway to WooCommerce
	**/
	function woocommerce_add_pluralxtpinelabs_gateway($methods)
	{
		$methods[] = 'WC_PluralXTPinelabs';

		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'woocommerce_add_pluralxtpinelabs_gateway' );
}

?>
