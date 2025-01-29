<?php

namespace WC_KORAPAY;

defined( 'ABSPATH' ) || exit;

/**
 * Kora Pay Payment Gateway class.
 *
 * @class    WC_Gateway_Korapay
 * @extends  WC_Payment_Gateway
 * @version  1.0.0
 * @package  WC_Korapay
 * @category Payment
 */
class WC_Gateway_Korapay extends \WC_Payment_Gateway {
    
	/**
	 * Is test mode active?
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Korapay test public key.
	 *
	 * @var string
	 */
	public $test_public_key;

	/**
	 * Korapay test secret key.
	 *
	 * @var string
	 */
	public $test_secret_key;

	/**
	 * Korapay live public key.
	 *
	 * @var string
	 */
	public $live_public_key;

	/**
	 * Korapay live secret key.
	 *
	 * @var string
	 */
	public $live_secret_key;

	/**
	 * Should orders be marked as complete after payment?
	 * 
	 * @var bool
	 */
	public $autocomplete_order;

	/**
	 * Korapay payment page type.
	 *
	 * @var string
	 */
	public $payment_page_type;

	/**
	 * Should we save customer cards?
	 * 
	 * @TODO: Nope.
	 *
	 * @var bool
	 */
	public $saved_cards;

	/**
	 * Should the cancel & remove order button be removed on the pay for order page.
	 *
	 * @var bool
	 */
	public $remove_cancel_order_button;

	/**
	 * Who bears Korapay charges?
	 *
	 * @var string
	 */
	public $charges_account;

	/**
	 * A flat fee to charge the sub account for each transaction.
	 *
	 * @var string
	 */
	public $transaction_charges;

	/**
	 * Active public key
	 *
	 * @var string
	 */
	public $active_public_key;

	/**
	 * Active secret key
	 *
	 * @var string
	 */
	public $active_secret_key;

	/**
	 * Gateway disabled message
	 *
	 * @var string
	 */
	public $msg;

	/**
	 * Constructor.
	 */
    public function __construct() {
        $this->id                 = 'korapay';
        $this->icon               = ''; // URL to the icon that will be displayed on checkout. TODO
        $this->method_title       = __( 'Kora', 'woo-korapay' );
        $this->method_description = sprintf( __( 'Accept online payments from local and international customers using Mastercard, Visa, Verve Cards and Bank Accounts. <a href="%1$s" target="_blank">Sign up</a> for a Kora account, and <a href="%2$s" target="_blank">get your API keys</a>.', 'woo-korapay' ), 'https://korahq.com', 'https://merchant.korapay.com/dashboard/settings/api-integrations' );
        
        $this->payment_page_type = $this->get_option( 'payment_page_type' );

        $this->supported_features();

        // Get our settings field.
        $this->form_fields = WC_Korapay_Settings::get_settings_form_fields();

        // Settings loader.
        $this->init_settings();
        $this->load_settings_data();
            
		// Hooks law :).
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );

		// Payment listener.
		add_action( 'woocommerce_api_wc_gateway_korapay', array( $this, 'handle_transaction_verifaction' ) );

		// Webhook listener/API hook.
		add_action( 'woocommerce_api_wc_korapay_webhook', array( $this, 'process_webhook' ) );

        // Our scripts.
       // add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
    }

    /**
     * Load Settings data.
     */
    public function load_settings_data() {
        $this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->enabled            = $this->get_option( 'enabled' );
		$this->testmode           = $this->get_option( 'testmode' ) === 'yes' ? true : false;
		$this->autocomplete_order = $this->get_option( 'autocomplete_order' ) === 'yes' ? true : false;

		$this->test_public_key = $this->get_option( 'test_public_key' );
		$this->test_secret_key = $this->get_option( 'test_secret_key' );

		$this->live_public_key = $this->get_option( 'live_public_key' );
		$this->live_secret_key = $this->get_option( 'live_secret_key' );

		$this->saved_cards = $this->get_option( 'saved_cards' ) === 'yes' ? true : false;

        // Custom metadata.
		$this->custom_metadata = $this->get_option( 'custom_metadata' ) === 'yes' ? true : false;

		$this->meta_order_id         = $this->get_option( 'meta_order_id' ) === 'yes' ? true : false;
		$this->meta_name             = $this->get_option( 'meta_name' ) === 'yes' ? true : false;
		$this->meta_email            = $this->get_option( 'meta_email' ) === 'yes' ? true : false;
		$this->meta_phone            = $this->get_option( 'meta_phone' ) === 'yes' ? true : false;
		$this->meta_billing_address  = $this->get_option( 'meta_billing_address' ) === 'yes' ? true : false;
		$this->meta_shipping_address = $this->get_option( 'meta_shipping_address' ) === 'yes' ? true : false;
		$this->meta_products         = $this->get_option( 'meta_products' ) === 'yes' ? true : false;

        // Let's set the api keys we will be using.
		$this->active_public_key = $this->testmode ? $this->test_public_key : $this->live_public_key;
		$this->active_secret_key = $this->testmode ? $this->test_secret_key : $this->live_secret_key;

		// Check if the gateway can be used.
		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = false;
		}
    }

    /**
     * Support features.
     */
    public function supported_features() {
        $this->supports = array(
			'products',
			// 'refunds', // TODO
			// 'tokenization',
		);
    }

    
	/**
	 * Check if Merchant details is filled.
	 */
	public function admin_notices() {
		if ( 'no' === $this->enabled ) {
			return;
		}

		// Check required fields.
		if ( ! ( $this->active_public_key && $this->active_secret_key ) ) {
			echo '<div class="error"><p>' . sprintf( __( 'Please enter your Kora merchant details <a href="%s">here</a> to be able to use the Kora Gateway For WooCommerce plugin.', 'woo-korapay' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=korapay' ) ) . '</p></div>';
			return;
		}
	}

	/**
	 * Display Korapay payment icon.
	 */
	public function get_icon() {
        $icon = '<img src="' . $this->get_logo_url() . '" alt="' . apply_filters( 'wc_korapay_icon_alt_txt', __( 'Kora Payment Options', 'woo-korapay' ) ) . '" width="100px">';
		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}


    /**
	 * Get payment icon URL.
     * 
     * Inspiration from Tubiz :).
	 */
	public function get_logo_url() {
		$base_location = wc_get_base_location();
		$url           = \WC_HTTPS::force_https_url( plugins_url( 'assets/images/korapay-' . strtolower( $base_location['country'] ) . '.png', WC_KORAPAY_PLUGIN_FILE ) );

		return apply_filters( 'wc_gateway_korapay_icon_url', $url, $this->id );
	}

    /**
	 * Admin Panel Options.
	 */
	public function admin_options() {
		?>
		<h2><?php _e( 'Kora', 'woo-korapay' ); ?>
		<?php
		if ( function_exists( 'wc_back_link' ) ) {
			wc_back_link( __( 'Return to payments', 'woo-korapay' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
		}
		?>
		</h2>
		<h4>
			<strong><?php printf( __( 'Optional: To avoid stories that touch in situations where bad network makes it impossible to verify transactions, set your webhook URL <a href="%1$s" target="_blank" rel="noopener noreferrer">here</a> to the URL below<span style="color: red"><pre><code>%2$s</code></pre></span>', 'woo-korapay' ), 'https://merchant.korapay.com/dashboard/settings/api-integrations', WC()->api_request_url( 'wc_korapay_webhook' ) ); ?></strong>
		</h4>


		<?php
		if ( $this->is_valid_for_use() ) {
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		} else {
			?>
			<div class="inline error"><p><strong><?php _e( 'Kora Payment Gateway Disabled', 'woo-korapay' ); ?></strong>: <?php echo $this->msg; ?></p></div>
			<?php
		}
	}

    /**
	 * Displays the payment side.
	 *
	 * @param $order_id
	 */
	public function receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );

		echo '<div id="wc-korapay-form">';

		echo '<p>' . __( 'Thank you for your order, please click the button below to pay with Kora.', 'woo-korapay' ) . '</p>';

		echo '<div id="wc_korapay_form"><form id="order_review" method="post" action="' . WC()->api_request_url( 'wc_gateway_korapay' ) . '"></form><button class="button" id="wc-korapay-payment-btn">' . apply_filters( 'wc_korapay_payment_btn_txt', __( 'Pay Now', 'woo-korapay' ), $order_id ) . '</button>';

		if ( ! $this->remove_cancel_order_button ) {
			echo '  <a class="button cancel" id="wc-korapay-cancel-payment-btn" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . apply_filters( 'wc_korapay_cancel_payment_btn_txt', __( 'Cancel order &amp; restore your cart', 'woo-korapay' ), $order_id ) . '</a></div>';
		}

		echo '</div>';
	}


    /**
	 * Process the payment.
	 *
	 * @param int $order_id
	 *
	 * @return array|void
	 */
	public function process_payment( $order_id ) {
		if ( 'redirect' === $this->payment_page_type ) { // It will always be redirect, for now.
			return $this->process_redirect_payment( $order_id );
		}
	}

	/**
	 * Process a redirect payment.
	 *
	 * @param int $order_id
	 * @return array|void
	 */
	public function process_redirect_payment( $order_id ) {
		$order        = wc_get_order( $order_id );
		$amount       = $order->get_total();
		$txn_ref      = 'kp_' . $order_id . '_' . time();
		$webhook_url  = WC()->api_request_url( 'wc_korapay_webhook' );
		$redirect_url = WC()->api_request_url( 'wc_gateway_korapay' );

		// TODO: Set a setting field to allow non-technical users change this(not necessary).
		
		/**
		 * Filters allowed payment channels
		 * 
		 * @param int $order_id
		 * @return array
		 */		
		$_channels = apply_filters( 'wc_korapay_allowed_payment_channels', array( 'card', 'bank_transfer' ), $order_id );
		
		/**
		 * Filters default payment channel
		 * 
		 * @param int $order_id
		 * @return string
		 */		
		$_default_channel = apply_filters( 'wc_korapay_default_payment_channels', 'card', $order_id );

		$korapay_params = array(
            'amount'             => absint( $amount ),
            'currency'           => $order->get_currency(),
            'reference'          => $txn_ref,
            'redirect_url'       => $redirect_url,
            'notification_url'   => $webhook_url,
            'narration'          => sprintf( apply_filters( 'wc_korapay_order_narration_text', __( 'Payment for Order #%s', 'wc-korapay' ) ), $order->get_order_number() ),
            'channels'           => $_channels,
            'default_channel'    => $_default_channel,
            'customer'           => array(
                'email' => $order->get_billing_email(),
                'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            ),
			/*'metadata'           => array( // Once length exceeds 50 Chars, causes issues.
                'meta_order_id'      => $order_id,
                'meta_customer_id'   => $order->get_user_id(),
                'meta_cancel_action' => wc_get_cart_url(),
            ),*/
            // 'merchant_bears_cost' => true, // TODO
        );

		// $korapay_params['metadata']['custom_fields'] = $this->get_custom_fields( $order_id );

		$order->update_meta_data( '_korapay_txn_ref', $txn_ref );
		$order->save();

		$response = WC_Korapay_API::send_request( 'charges/initialize', $korapay_params );

		if ( is_wp_error( $response ) ) {

            do_action( 'wc_korapay_redirect_payment_error', $response, $korapay_params, $order_id );

            wc_add_notice( apply_filters( 'wc_korapay_redirect_payment_error_msg', __( 'Unable to process payment at this time, try again later.', 'woo-korapay' ), $response, $order_id ) , 'error' );

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
        }

        // All good! Empty cart and proceed.
        WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $response['data']['checkout_url'],
		);
    }

	/**
	 * Process a token payment.
	 *
     * @TODO - not necessary.
     * 
	 * @param $token
	 * @param $order_id
	 *
	 * @return bool
	 */
	public function _process_token_payment( $token, $order_id ) {

	}

    /**
     * Verify our payment transaction
     * 
     * Verify Payment Transactiona and handle order.
	 * Let's avoid stories that touch abeg.
     */
    public function handle_transaction_verifaction() {
		if ( isset( $_REQUEST['korapay_txn_ref'] ) ) {
			$txn_ref = sanitize_text_field( $_REQUEST['korapay_txn_ref'] );
		} elseif ( isset( $_REQUEST['reference'] ) ) {
			$txn_ref = sanitize_text_field( $_REQUEST['reference'] );
		} else {
			$txn_ref = false;
		}

		@ob_clean(); // Inspo from Tubiz :).

        if ( ! $txn_ref ) { // No transaction reference.
            wp_redirect( wc_get_page_permalink( 'cart' ) );
            exit;
        }

        // Let's proceed, first get our order details.
        $order_details = explode( '_', $txn_ref );
        $order_id      = (int) $order_details[1];
        $order         = wc_get_order( $order_id );

        $response = WC_Korapay_API::verify_transaction( $txn_ref );
		
        if ( is_wp_error( $response ) || false === $response['status'] ) { // An issue occured.
			$order->update_status( 'failed', __( 'An error occurred while verifying payment on Kora.', 'woo-korapay' ) );
            wp_redirect( $this->get_return_url( $order ) );
			exit;
        }

        if ( 'success' !== $response['data']['status'] ) {
            // Note: gives an overview status of the transaction ie. was the payment made successfully or not. It can be success, pending, processing, expired or failed.
            $order->update_status( 'failed', __( 'Payment was declined by Kora.', 'woo-korapay' ) );
        } else {
            if ( in_array( $order->get_status(), array( 'processing', 'completed', 'on-hold' ) ) ) {
                wp_redirect( $this->get_return_url( $order ) );
                exit;
            }

            $order_total      = $order->get_total();
            $order_currency   = $order->get_currency();
            $currency_symbol  = get_woocommerce_currency_symbol( $order_currency );
            $amount_paid      = $response['data']['amount'];
            $korapay_ref      = $response['data']['reference'];
            $payment_currency = strtoupper( $response['data']['currency'] );
            $gateway_symbol   = get_woocommerce_currency_symbol( $payment_currency );

			if ( $amount_paid < absint( $order_total ) ) {

                $order->update_status( 'on-hold', '' );

                $order->add_meta_data( '_transaction_id', $korapay_ref, true );

                // TODO: Make it a filter.
                $notice      = sprintf( __( 'Thank you for shopping with us.%1$sYour payment transaction was successful, but the amount paid is not the same as the total order amount.%2$sYour order is currently on hold.%3$sKindly contact us for more information regarding your order and payment status.', 'woo-korapay' ), '<br />', '<br />', '<br />' );
                $notice_type = 'notice';

                // Customer Order Note.
                $order->add_order_note( $notice, 1 );

                // Admin Order Note.
                // TODO: make it a filter.
                $admin_order_note = sprintf( __( '<strong>Issue! Look into this order</strong>%1$sThis order is currently on hold.%2$sReason: Amount paid is less than the total order amount.%3$sAmount Paid was <strong>%4$s (%5$s)</strong> while the total order amount is <strong>%6$s (%7$s)</strong>%8$s<strong>Kora Transaction Reference:</strong> %9$s', 'woo-korapay' ), '<br />', '<br />', '<br />', $currency_symbol, $amount_paid, $currency_symbol, $order_total, '<br />', $korapay_ref );
                $order->add_order_note( $admin_order_note );

                function_exists( 'wc_reduce_stock_levels' ) ? wc_reduce_stock_levels( $order_id ) : $order->reduce_order_stock();

                wc_add_notice( $notice, $notice_type );

            } else {

                if ( $payment_currency !== $order_currency ) { // How? anyways, Wahala!

                    $order->update_status( 'on-hold', '' );

                    $order->update_meta_data( '_transaction_id', $korapay_ref );

                    $notice      = sprintf( __( 'Thank you for shopping with us.%1$sYour payment was successful, but the payment currency is different from the order currency.%2$sYour order is currently on-hold.%3$sKindly contact us for more information regarding your order and payment status.', 'woo-korapay' ), '<br />', '<br />', '<br />' );
                    $notice_type = 'notice';

                    // Add Customer Order Note
                    $order->add_order_note( $notice, 1 );

                    // Add Admin Order Note
                    // TODO: make this a filter.
                    $admin_order_note = sprintf( __( '<strong>Issue! Look into this order</strong>%1$sThis order is currently on hold.%2$sReason: Order currency is different from the payment currency.%3$sOrder Currency is <strong>%4$s (%5$s)</strong> while the payment currency is <strong>%6$s (%7$s)</strong>%8$s<strong>Kora Transaction Reference:</strong> %9$s', 'woo-korapay' ), '<br />', '<br />', '<br />', $order_currency, $currency_symbol, $payment_currency, $gateway_symbol, '<br />', $korapay_ref );
                    $order->add_order_note( $admin_order_note );

                    function_exists( 'wc_reduce_stock_levels' ) ? wc_reduce_stock_levels( $order_id ) : $order->reduce_order_stock();

                    wc_add_notice( $notice, $notice_type );

                } elseif ( $korapay_ref !== $order->get_meta( '_korapay_txn_ref' ) ) { // If this isn't same, something was tampered with.

                    $order->update_status( 'on-hold', '' );

                    $order->update_meta_data( '_transaction_id', $korapay_ref );

                    $notice      = sprintf( __( 'Thank you for shopping with us.%1$sYour payment was successful, but transaction reference comparison seems differnet.%2$sYour order is currently on-hold.%3$sKindly contact us for more information regarding your order and payment status.', 'woo-korapay' ), '<br />', '<br />', '<br />' );
                    $notice_type = 'notice';

                    // Add Customer Order Note.
                    $order->add_order_note( $notice, 1 );

                    // Add Admin Order Note.
                    $admin_order_note = sprintf( __( '<strong>Issue! Look into this order</strong>%1$sThis order is currently on hold.%2$sReason: Transaction reference comparison failed.%3$sOrder Transaction reference is <strong>%4$s</strong> while the transaction reference from Kora is <strong>%5$s</strong>%6$s<strong>Kora Transaction Reference:</strong> %7$s', 'woo-korapay' ), '<br />', '<br />', '<br />', $order->get_meta( '_korapay_txn_ref' ), $korapay_ref, '<br />', $korapay_ref );
                    $order->add_order_note( $admin_order_note );

                    function_exists( 'wc_reduce_stock_levels' ) ? wc_reduce_stock_levels( $order_id ) : $order->reduce_order_stock();

                    wc_add_notice( $notice, $notice_type );

				} else {

                    $order->payment_complete( $korapay_ref );
                    $order->add_order_note( sprintf( __( 'Payment via Kora successful! (Transaction Reference: %s)', 'woo-korapay' ), $korapay_ref ) );

                    if ( $this->is_autocomplete_order_enabled( $order ) ) {
                        $order->update_status( 'completed' );
                    }
                }
            }

            // Save and empty cart!
            $order->save();
            WC()->cart->empty_cart();
        }

        wp_redirect( $this->get_return_url( $order ) );
        exit;
    }

/**
	 * Process a refund request from the Order details screen.
     * 
     * Kora doesn't have a refund endpoint, so this is dormant for now.
	 *
	 * @param int $order_id WC Order ID.
	 * @param float|null $amount Refund Amount.
	 * @param string $reason Refund Reason
	 *
	 * @return bool|WP_Error
	 */
	public function _process_refund( $order_id, $amount = null, $reason = '' ) {
        return false;

		if ( ! ( $this->active_public_key && $this->active_secret_key ) ) {
			return false;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		$currency = $order->get_currency();
		$txn_id   = $order->get_transaction_id(); // TODO: Confirm what this produces.

        // Get transaction details.
        $response = WC_Korapay_API::verify_transaction( $transaction_id );

		if ( false == $response ) {
            return false;
        }

		if ( 'success' === $response['data']['status'] ) {

			$merchant_note = sprintf( __( 'Refund for Order ID: #%1$s on %2$s', 'woo-korapay' ), $order_id, get_site_url() );

			$body = array(
				'transaction'   => $transaction_id,
				'amount'        => $amount * 100,
				'currency'      => $currency,
				'customer_note' => $reason,
				'merchant_note' => $merchant_note,
			);

			$headers = array(
				'Authorization' => 'Bearer ' . $this->active_secret_key,
			);

			$args = array(
				'headers' => $headers,
				'timeout' => 60,
				'body'    => $body,
			);

			$refund_endpoint = '/refund';
			$refund_response  = WC_Korapay_API::send_request( $refund_endpoint, $args );

			if ( ! is_wp_error( $refund_response ) ) {

				if ( $refund_response['status'] ) {
					$amount         = wc_price( $amount, array( 'currency' => $currency ) );
					$refund_id      = $refund_response['data']['id'];
					$refund_message = sprintf( __( 'Refunded %1$s. Refund ID: %2$s. Reason: %3$s', 'woo-korapay' ), $amount, $refund_id, $reason );
					$order->add_order_note( $refund_message );

					return true;
				}

			} else {
				if ( isset( $refund_response->message ) ) {
					return new WP_Error( 'error', $refund_response['message'] );
				} else {
					return new WP_Error( 'error', __( 'Can&#39;t process refund at the moment. Try again later.', 'woo-korapay' ) );
				}
			}

		}

	}

	
	/**
	 * Process Webhook.
	 * 
	 * @TODO: STILL TEST AGAIN.
	 */
	public function process_webhook() {

		if ( ! array_key_exists( 'HTTP_X_KORAPAY_SIGNATURE', $_SERVER ) || ( strtoupper( $_SERVER['REQUEST_METHOD'] ) !== 'POST' ) ) {
			exit;
		}

		$json = file_get_contents( 'php://input' );

		// Validate event do all at once to avoid timing attack, inspo from Tubiz :).
		if ( $_SERVER['HTTP_X_KORAPAY_SIGNATURE'] !== hash_hmac( 'sha512', $json, $this->secret_key ) ) {
			exit;
		}

		$event = json_decode( $json, true );

		if ( 'charge.success' !== strtolower( $event['event'] ) ) {
			return;
		}

		sleep( 10 );

		$korapay_response = WC_Korapay_API::verify_transaction( $event['data']['reference'] );

		if ( false === $korapay_response ) {
			return;
		}

		$order_details = explode( '_', $korapay_response['data']['reference'] );

		$order_id = (int) $order_details[1];

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$korapay_txn_ref = $order->get_meta( '_korapay_txn_ref' );

		if ( $korapay_response['data']['reference'] != $korapay_txn_ref ) {
			exit;
		}

		http_response_code( 200 );

		if ( in_array( strtolower( $order->get_status() ), array( 'processing', 'completed', 'on-hold' ), true ) ) {
			exit;
		}

		$order_currency = $order->get_currency();

		$currency_symbol = get_woocommerce_currency_symbol( $order_currency );

		$order_total = $order->get_total();

		$amount_paid = $korapay_response['data']['amount'] / 100;

		$korapay_ref = $korapay_response['data']['reference'];

		$payment_currency = strtoupper( $korapay_response['data']['currency'] );

		$gateway_symbol = get_woocommerce_currency_symbol( $payment_currency );

		// check if the amount paid is equal to the order amount.
		if ( $amount_paid < absint( $order_total ) ) {

			$order->update_status( 'on-hold', '' );

			$order->add_meta_data( '_transaction_id', $korapay_ref, true );

			$notice      = sprintf( __( 'Thank you for shopping with us.%1$sYour payment transaction was successful, but the amount paid is not the same as the total order amount.%2$sYour order is currently on hold.%3$sKindly contact us for more information regarding your order and payment status.', 'woo-korapay' ), '<br />', '<br />', '<br />' );
			$notice_type = 'notice';

			// Add Customer Order Note.
			$order->add_order_note( $notice, 1 );

			// Add Admin Order Note.
			$admin_order_note = sprintf( __( '<strong>Issue! Look into this order</strong>%1$sThis order is currently on hold.%2$sReason: Amount paid is less than the total order amount.%3$sAmount Paid was <strong>%4$s (%5$s)</strong> while the total order amount is <strong>%6$s (%7$s)</strong>%8$s<strong>Kora Transaction Reference:</strong> %9$s', 'woo-korapay' ), '<br />', '<br />', '<br />', $currency_symbol, $amount_paid, $currency_symbol, $order_total, '<br />', $korapay_ref );
			$order->add_order_note( $admin_order_note );

			function_exists( 'wc_reduce_stock_levels' ) ? wc_reduce_stock_levels( $order_id ) : $order->reduce_order_stock();

			wc_add_notice( $notice, $notice_type );

			WC()->cart->empty_cart();

		} else {

			if ( $payment_currency !== $order_currency ) {

				$order->update_status( 'on-hold', '' );

				$order->update_meta_data( '_transaction_id', $korapay_ref );

				$notice      = sprintf( __( 'Thank you for shopping with us.%1$sYour payment was successful, but the payment currency is different from the order currency.%2$sYour order is currently on-hold.%3$sKindly contact us for more information regarding your order and payment status.', 'woo-korapay' ), '<br />', '<br />', '<br />' );
				$notice_type = 'notice';

				// Add Customer Order Note.
				$order->add_order_note( $notice, 1 );

				// Add Admin Order Note.
				$admin_order_note = sprintf( __( '<strong>Issue! Look into this order</strong>%1$sThis order is currently on hold.%2$sReason: Order currency is different from the payment currency.%3$sOrder Currency is <strong>%4$s (%5$s)</strong> while the payment currency is <strong>%6$s (%7$s)</strong>%8$s<strong>Kora Transaction Reference:</strong> %9$s', 'woo-korapay' ), '<br />', '<br />', '<br />', $order_currency, $currency_symbol, $payment_currency, $gateway_symbol, '<br />', $korapay_ref );
				$order->add_order_note( $admin_order_note );

				function_exists( 'wc_reduce_stock_levels' ) ? wc_reduce_stock_levels( $order_id ) : $order->reduce_order_stock();

				wc_add_notice( $notice, $notice_type );

			} else {

				$order->payment_complete( $korapay_ref );

				$order->add_order_note( sprintf( __( 'Payment via Kora successful (Transaction Reference: %s)', 'woo-korapay' ), $korapay_ref ) );

				WC()->cart->empty_cart();

				if ( $this->is_autocomplete_order_enabled( $order ) ) {
					$order->update_status( 'completed' );
				}
			}
		}

		$order->save();
		exit;
	}

    // HELPER FUNCTIONS.

	/**
	 * Check if this gateway is enabled and available in the user's country.
	 */
	public function is_valid_for_use() {
		if ( ! in_array( get_woocommerce_currency(), apply_filters( 'wc_korapay_supported_currencies', array( 'NGN', 'USD', 'GHS', 'KES' ) ) ) ) {
			$this->msg = sprintf( __( 'Sorry, Kora does not support your store currency. Kindly set it to either NGN (&#8358), GHS (&#x20b5;), USD (&#36;), or KES (KSh) <a href="%s">here</a>', 'woo-korapay' ), admin_url( 'admin.php?page=wc-settings&tab=general' ) );
			return false;
		}

		return true;
	}


    /**
	 * Checks if autocomplete order is enabled for the payment method.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	protected function is_autocomplete_order_enabled( $order ) {
		$payment_method = $order->get_payment_method();
		$settings       = get_option( 'woocommerce_' . $payment_method . '_settings' );

		if ( isset( $settings['autocomplete_order'] ) && 'yes' === $settings['autocomplete_order'] ) {
			return true;
		}

		return false;
	}

}
