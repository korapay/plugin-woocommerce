<?php
/*
 * Plugin Name: Kora WooCommerce Payment Gateway
 * Plugin URI: https://korahq.com
 * Description: A WooCommerce payment gateway for Kora.
 * Version: 1.1.1
 * Author: Kora
 * Author URI: https://korahq.com
 * Text Domain: woo-korapay
 * Domain Path: /languages/
 * WC requires at least: 7.0
 * WC tested up to: 9.3
 */

namespace WC_KORAPAY;

defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'WC_KORAPAY_VERSION', '1.0.0' );
define( 'WC_KORAPAY_PLUGIN_FILE', __FILE__ );
define( 'WC_KORAPAY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_KORAPAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


if ( ! function_exists( 'WC_KORAPAY\\load_plugin_textdomain' ) ) {
	/**
	 * Load Localisation files.
	 *
	 * @since  1.0.0
	 */
	function load_textdomain() {
		load_plugin_textdomain( 'wc-korapay', false, plugin_basename( dirname( WC_KORAPAY_PLUGIN_FILE ) ) . '/languages' );
	}
}
add_action( 'plugins_loaded', 'WC_KORAPAY\\load_textdomain' );


if ( ! function_exists( 'WC_KORAPAY\\wc_gateway_korapay_init' ) ) {
    /**
     * Initialize the Kora payment gateway.
     *
     * This function checks if WooCommerce is active, then loads the gateway class and settings.
     *
     * @return void
     */
    function wc_gateway_korapay_init() {
        // Ensure WooCommerce is active before proceeding.
        if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Payment_Gateway' ) ) {
			add_action( 'admin_notices', 'WC_KORAPAY\\missing_wc_notice' );
	        return;
        }

        // Include the necessary classes for the payment gateway.
        require_once WC_KORAPAY_PLUGIN_DIR . '/includes/class-wc-gateway-korapay.php';
        require_once WC_KORAPAY_PLUGIN_DIR . '/includes/class-wc-korapay-api.php';
        require_once WC_KORAPAY_PLUGIN_DIR . '/includes/admin/class-wc-korapay-settings.php';
        
        add_filter( 'woocommerce_payment_gateways', 'WC_KORAPAY\\add_gateway_class' );
    }
}
add_action( 'plugins_loaded', 'WC_KORAPAY\\wc_gateway_korapay_init' );


if ( ! function_exists( 'WC_KORAPAY\\add_gateway_class' ) ) {
    /**
     * Add the Kora gateway to WooCommerce's list of payment gateways.
     *
     * @param array $gateways Array of WooCommerce payment gateway classes.
     * @return array
     */
    function add_gateway_class( $gateways ) {
        $gateways[] = 'WC_KORAPAY\\WC_Gateway_Korapay';
        return $gateways;
    }
}

if ( ! function_exists( 'WC_KORAPAY\\gateway_action_links' ) ) {
    /**
     * Add custom action links to the plugin on the plugins page.
     *
     * @param array $links Array of existing action links.
     * @return array
     */
    function gateway_action_links( $links ) {
        // Define the settings link.
        $plugin_links = [
            '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=korapay' ) ) . '">' . __( 'Settings', 'woo-korapay' ) . '</a>',
        ];
        return array_merge( $plugin_links, $links );
    }
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'WC_KORAPAY\\gateway_action_links' );

if ( ! function_exists( 'WC_KORAPAY\\declare_hpos_compatibility' ) ) {

	/**
	 * Declare HPOS (Custom Order tables) compatibility.
	 *
	 * @since 1.0.0
	 */
	function declare_hpos_compatibility() {
		if ( ! class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			return;
		}

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', plugin_basename( WC_KORAPAY_PLUGIN_FILE ), true );
	}
}
add_action( 'before_woocommerce_init', 'WC_KORAPAY\\declare_hpos_compatibility' );


if ( ! function_exists( 'WC_KORAPAY\\korapay_wc_block_support' ) ) {
	/**
	 * Registers WooCommerce Blocks integration.
	 */
	function korapay_wc_block_support() {
		// if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) { }
		require_once WC_KORAPAY_PLUGIN_DIR . '/includes/class-wc-gateway-korapay-blocks-support.php';

		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			static function( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				$payment_method_registry->register( new WC_Gateway_Korapay_Blocks_Support() );
			}
		);
	}
}
add_action( 'woocommerce_blocks_loaded', 'WC_KORAPAY\\korapay_wc_block_support' );

if ( ! function_exists( 'WC_KORAPAY\\missing_wc_notice' ) ) {

	/**
	 * WooCommerce fallback notice.
	 *
	 * @since 1.0.0
	 */
	function missing_wc_notice() {
		$install_url = wp_nonce_url(
			add_query_arg(
				[
					'action' => 'install-plugin',
					'plugin' => 'woocommerce',
				],
				admin_url( 'update.php' )
			),
			'install-plugin_woocommerce'
		);

		$admin_notice_content = sprintf(
			// translators: 1$-2$: opening and closing <strong> tags, 3$-4$: link tags, takes to woocommerce plugin on wp.org, 5$-6$: opening and closing link tags, leads to plugins.php in admin
			esc_html__( '%1$sKora Gateway for WooCommerce is inactive.%2$s The %3$sWooCommerce plugin%4$s must be active for the Koraypay Gateway to work. Please %5$sinstall & activate WooCommerce &raquo;%6$s', 'woo-korapay' ),
			'<strong>',
			'</strong>',
			'<a href="http://wordpress.org/extend/plugins/woocommerce/">',
			'</a>',
			'<a href="' . esc_url( $install_url ) . '">',
			'</a>'
		);

		echo '<div class="error">';
		echo '<p>' . wp_kses_post( $admin_notice_content ) . '</p>';
		echo '</div>';
	}
}
