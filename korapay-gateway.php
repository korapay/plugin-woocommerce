<?php

/*
 * Plugin Name: Korapay Payments Gateway
 * Plugin URI: https://korapay.com
 * Description: Accept and make payments from your store with Korapay
 * Author: Divine Olokor
 * Author URI: https://github.com/divee789
 * Version: 1.0.1
 *
 /*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */

 
if (!defined('ABSPATH')) {
  exit;
}
add_filter('woocommerce_payment_gateways', 'korapay_add_gateway_class');

/*
 *This action hook enables the settings link in the settings page
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'korapay_plugin_action_links');
function korapay_plugin_action_links($links)
{
  
  $settings_link = array(
    'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=korapay') . '" title="' . 'View Korapay WooCommerce Settings' . '">' . 'Settings' . '</a>'
  );
  
  return array_merge($settings_link, $links);
  
}


//Allow users to access order pay page without login
// add_filter( 'user_has_cap', 'korapay_order_pay_without_login', 9999, 3 );
 
// function korapay_order_pay_without_login( $allcaps, $caps, $args ) {
//    if ( isset( $caps[0], $_GET['key'] ) ) {
//       if ( $caps[0] == 'pay_for_order' ) {
//          $order_id = isset( $args[2] ) ? $args[2] : null;
//          $order = wc_get_order( $order_id );
//          if ( $order ) {
//             $allcaps['pay_for_order'] = true;
//          }
//       }
//    }
//    return $allcaps;
// }

function korapay_add_gateway_class($gateways)
{
  $gateways[] = 'WC_Korapay_Gateway'; // your class name is here
  return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'korapay_init_gateway_class');




function korapay_init_gateway_class()
{
  
  
  class WC_Korapay_Gateway extends WC_Payment_Gateway
  {
    
    /**
     * Class constructor
     */
    public function __construct()
    {
      
      $this->id                 = 'korapay'; // payment gateway plugin ID
      $this->icon               = 'https://www.google.com/url?sa=i&source=images&cd=&ved=2ahUKEwilm-Dr85bnAhVIxoUKHaUAB0AQjRx6BAgBEAQ&url=https%3A%2F%2Fkorapay.com%2F&psig=AOvVaw3oycoej5UrPNROiVfuuqlh&ust=1579772094359006'; // URL of the icon that will be displayed on checkout page near your gateway name
      $this->has_fields         = true; // in case you need a custom credit card form
      $this->method_title       = 'Korapay Gateway';
      $this->method_description = sprintf('Korapay provides a payment platform that enables local and global businesses accept and disburse payments quickly and seamlessly while saving time and money using either bank transfers or credit card payments, <a href="%1$s" target="_blank">Sign up on korapay.com</a>  to  <a href="%2$s" target="_blank">get your API keys</a>','https://korapay.com','https://business.koraapi.com/dashboard/settings/api-integrations'); // will be displayed on the options page
      
      // gateways can support subscriptions, refunds, saved payment methods,
      $this->supports = array(
        'products'
      );
      
      // Method with all the options fields
      $this->init_form_fields();
      
      // Load the settings.
      $this->init_settings();
      
      //Get settings values
      $this->title       = $this->get_option('title');
      $this->description = $this->get_option('description');
      $this->enabled     = $this->get_option('enabled');
      $this->testmode    = 'yes' === $this->get_option('testmode');
      $this->secret_key  = $this->testmode ? $this->get_option('test_secret_key') : $this->get_option('live_secret_key');
      $this->public_key  = $this->testmode ? $this->get_option('test_public_key') : $this->get_option('live_public_key');
      
      
      //===HOOKS=====
      
      // This action hook saves the settings
      add_action('wp_enqueue_scripts', array(
        $this,
        'payment_scripts'
      ));
      
      add_action('admin_enqueue_scripts', array(
        $this,
        'admin_scripts'
      ));
      
      add_action( 'admin_notices', array( $this, 'admin_notices' ) );
      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
        $this,
        'process_admin_options'
      ));
      
      add_action('woocommerce_receipt_' . $this->id, array(
        $this,
        'receipt_page'
      ));
      // Payment listener/API hook.
		add_action( 'woocommerce_api_wc_korapay_gateway', array( $this, 'verify_korapay_transaction' ) );
      
      
      // You can also register a webhook here
      // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
    }
    


    	/**
	 * Check if this gateway is enabled and available in the user's country.
	 */
	public function is_valid_for_use() {

		if ( ! in_array( get_woocommerce_currency(), array( 'NGN', 'USD') ) ) {

			$this->msg = sprintf(  'Korapay does not support your store currency. Kindly set it to either NGN (&#8358), USD (&#36;)<a href="%s">here</a>', admin_url( 'admin.php?page=wc-settings&tab=general' ));

			return false;

		}

		return true;

  }
  
  	public function admin_notices() {

		if ( $this->enabled == 'no' ) {
			return;
		}

		// Check required fields.
		if ( ! ( $this->public_key && $this->secret_key ) ) {
			echo '<div class="error"><p>' . sprintf( 'Please enter your Korapay merchant details <a href="%s">here</a> to be able to use the Korapay WooCommerce plugin.', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=korapay' ) ) . '</p></div>';
			return;
		}

	}
    
    
    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
      
      $this->form_fields = array(
        'enabled' => array(
          'title' => 'Enable/Disable',
          'label' => 'Enable Korapay Gateway',
          'type' => 'checkbox',
          'description' => 'Enable Korapay as a payment option on the checkout page',
          'default' => 'no',
          'desc_tip' => true
        ),
        'title' => array(
          'title' => 'Title',
          'type' => 'text',
          'description' => 'This controls the payment method title which the user sees during checkout.',
          'default' => 'Debit/Credit Cards',
          'desc_tip' => true
        ),
        'description' => array(
          'title' => 'Description',
          'type' => 'textarea',
          'description' => 'This controls the payment method description which the user sees during checkout.',
          'default' => 'Initialize payment with your credit or debit cards'
        ),
        'testmode' => array(
          'title' => 'Test mode',
          'label' => 'Enable Test Mode',
          'type' => 'checkbox',
          'description' => 'Test mode enables you to test payments before going live',
          'default' => 'yes',
          'desc_tip' => true
        ),
        'test_secret_key' => array(
          'title' => 'Test Secret Key',
          'description' => 'Enter your test secret key here',
          'type' => 'text'
        ),
        'test_public_key' => array(
          'title' => 'Test Public Key',
          'type' => 'text',
          'description' => 'Enter your test public key here'
        ),
        'live_secret_key' => array(
          'title' => 'Live Secret Key',
          'type' => 'text',
          'description' => 'Enter your live secret key here'
        ),
        'live_public_key' => array(
          'title' => 'Live Public Key',
          'type' => 'text',
          'description' => 'Enter your live public key here'
        )
      );
      
      
    }
    
    /** 
    * There are no payment fields for korapay, but we want to show the description if set. 
    **/ 
    public function payment_fields()
    {
      if ( $this->description ) {
			echo wpautop( wptexturize( $this->description ) );
		}
      
    }
    
    /*
     * Outputs scripts used for korapay payment
     */
    public function payment_scripts()
    {
      
      if (!is_checkout_pay_page()) {
        return;
      }
      
      
      // if our payment gateway is disabled, we do not have to enqueue JS too
      if ('no' === $this->enabled) {
        return;
      }
      
      // no reason to enqueue JavaScript if API keys are not set
      if (empty($this->secret_key) || empty($this->public_key)) {
        return;
      }
      

      
      //This is our order details
      $order_key = urldecode($_GET['key']);
      $order_id  = absint(get_query_var('order-pay'));
      $order     = wc_get_order($order_id);

      
      //Load Jquery
      wp_enqueue_script('jquery');
      
      //Load Korapay Modal url
      wp_enqueue_script('korapay', 'https://korablobstorage.blob.core.windows.net/modal-bucket/korapay-collections.min.js');
      
      //Load The  file
      wp_enqueue_script('wc_korapay', plugins_url('assets/js/init.js', __FILE__), array(
        'jquery',
        'korapay'
      ));
      
      $korapay_params = array(
        'Key' => $this->public_key
      );

      if (is_checkout_pay_page() && get_query_var('order-pay')) {
        $email  = method_exists($order, 'get_billing_email') ? $order->get_billing_email() : $order->billing_email;
        $amount = $order->get_total();
        
        $first_name = method_exists($order, 'get_billing_first_name') ? $order->get_billing_first_name() : $order->billing_first_name;
        $last_name  = method_exists($order, 'get_billing_last_name') ? $order->get_billing_last_name() : $order->billing_last_name;
        
        $korapay_params['email']  = $email;
        $korapay_params['amount'] = $amount;
        $korapay_params['name']   = $first_name . ' ' . $last_name;
        $korapay_params['orderId']=$order_id;
        $korapay_params['reference']=$order_id.'_'.time();
      }
      
      wp_localize_script('wc_korapay', 'korapay_params', $korapay_params);
      
      // wp_enqueue_script('wc_korapay');
    }
    
    /*
     * Fields validation
     */
    public function validate_fields()
    {
      
      
      
      
    }
    
    	public function admin_scripts() {

		if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
			return;
		}


		$korapay_admin_params = array(
			'plugin_url' => WC_KORAPAY_URL,
		);

		wp_enqueue_script( 'wc_korapay_admin',  plugins_url('assets/js/admin.js', __FILE__), array());

		wp_localize_script( 'wc_korapay_admin', 'wc_korapay_admin_params', $korapay_admin_params );

	}
    /*
     * We're processing the payments here
     */
    public function process_payment($order_id)
    {
      
      global $woocommerce;
      
      
      $order = new WC_Order($order_id);
      
      // we received the order
      // Redirect to the payment page
      return array(
        'result' => 'success',
        'redirect' => $order->get_checkout_payment_url(true)
      );
    }
    
    
    public function receipt_page($order_id)
    {
      
      $order = wc_get_order($order_id);
      $url = WC()->api_request_url('WC_KORAPAY_GATEWAY');
      
      echo '<p>' . 'Thank you for your order, please click the button below to pay with Korapay.' . '</p>';
      
      echo '<div id="korapay_form"><form id="order_review" method="post" action="' . WC()->api_request_url( 'WC_Korapay_Gateway' ) . '"></form><button class="button alt" id="korapay-payment-button">' . 'Pay Now' . '</button> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . 'Cancel order &amp; restore cart' . '</a></div>';
      
    }
    /**
	 * Verify Korapay payment.
	 */
    public function verify_korapay_transaction(){

    @ob_clean();
      
      if ( isset( $_REQUEST['korapay_referenceKey'] ) ) {
        if( isset( $_REQUEST['modal_failure'] )){
           $order_id = $_REQUEST['korapay_referenceKey'];
             $order = wc_get_order( $order_id );
          
          print_r($order);

          $order->update_status( 'failed', 'The Korapay gateway has been temporarily taken down for maintenance');
        }else{
           if( isset( $_REQUEST['trans_failure'] )){

	         $order_details = explode( '_', $_REQUEST['korapay_referenceKey'] );

					$order_id = (int) $order_details[0];

          $order = wc_get_order( $order_id );

          $order->update_status( 'failed', 'Payment was declined by Korapay');
          
        } else {
       
        $korapay_ref = sanitize_text_field( $_REQUEST['korapay_referenceKey'] );
        
        $order_details = explode('_',$korapay_ref);
       
        $order_id = (int) $order_details[0];
       
        $order = wc_get_order($order_id);

        	if ( in_array( $order->get_status(), array( 'processing', 'completed', 'on-hold' ) ) ) {

						wp_redirect( $this->get_return_url( $order ) );

						exit;

					}

        $order->payment_complete( $korapay_ref );

				$order->add_order_note( 'Payment via Korapay successful (Transaction Reference:'.' '. $korapay_ref );

      // Empty cart
      wc_empty_cart();

      //Redirect to success url
      wp_redirect( $this->get_return_url( $order ) );
			exit;
      }
        }
       
    }
       wc_add_notice('Payment Failed , Try Again');
      	wp_redirect( wc_get_page_permalink( 'cart' ) );

		exit;
    }
    /*
     * In case you need a webhook, like PayPal IPN etc
     */
    public function webhook()
    {
      
      
      
    }
  }
}






