<?php
namespace WC_KORAPAY;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use \Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use \Automattic\WooCommerce\StoreApi\Payments\PaymentResult;

/**
 * Kora Pay Payment Gateway class.
 *
 * @class    WC_Gateway_Korapaya_Blocks_Support
 * @extends  AbstractPaymentMethodType
 * @version  1.0.0
 * @package  WC_Korapay
 * @category Payment
 */

final class WC_Gateway_Korapay_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'korapay';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_' . $this->name . '_settings', array() );

		add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'failed_payment_notice' ), 8, 2 );
	}

	/**
	 * Returns if this payment method should be active. If false, scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$payment_gateways_class = \WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();
		return $payment_gateways[ $this->name ]->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_asset_path = plugins_url( '/assets/js/build/blocks/frontend.asset.php', WC_KORAPAY_PLUGIN_FILE );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => WC_KORAPAY_VERSION,
			);

		$script_url = plugins_url( '/assets/js/build/blocks/frontend.js', WC_KORAPAY_PLUGIN_FILE );

		wp_register_script(
			'wc-korapay-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-korapay-blocks', 'woo-korapay', );
		}

		return array( 'wc-korapay-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$payment_gateways_class = \WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();
		$gateway                = $payment_gateways[ $this->name ];

		return array(
			'title'             => $this->get_setting( 'title' ),
			'description'       => $this->get_setting( 'description' ),
			'supports'          => array_filter( $gateway->supports, array( $gateway, 'supports' ) ),
			'logo_urls'         => array( $payment_gateways[ $this->name ]->get_logo_url() ),
		);
	}

	/**
	 * Add failed payment notice to the payment details.
	 *
	 * @param PaymentContext $context Holds context for the payment.
	 * @param PaymentResult  $result  Result object for the payment.
	 */
	public function failed_payment_notice( PaymentContext $context, PaymentResult &$result ) {
		if ( $this->name === $context->payment_method ) {
			add_action(
				'wc_gateway_korapay_process_payment_error',
				function( $failed_notice ) use ( &$result ) {
					$payment_details                 = $result->payment_details;
					$payment_details['errorMessage'] = wp_strip_all_tags( $failed_notice );
					$result->set_payment_details( $payment_details );
				}
			);
		}
	}
}