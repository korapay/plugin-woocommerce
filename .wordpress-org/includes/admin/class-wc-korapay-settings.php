<?php

namespace WC_KORAPAY;

defined( 'ABSPATH' ) || exit;

/**
 * Kora Pay Settings class helper.
 *
 * @class    WC_Korapay_Settings
 * @version  1.0.0
 * @package  WC_Korapay
 * @category Payment
 */
class WC_Korapay_Settings {
    
	/**
	 * Is test mode active?
	 *
	 * @var bool
	 */
	protected $settings_form_fields;

    /**
     * Retrieve the API key based on the environment and key type.
     *
     * @param string $key_type Either 'public' or 'secret'.
     * @return string The corresponding API key.
    */
    public static function get_active_key( $key_type ) {
        $settings = get_option( 'woocommerce_korapay_settings' );

        // Check if test mode is enabled.
        $test_mode = ( isset( $settings['testmode'] ) && 'yes' === $settings['testmode'] );
        
        // Determine the appropriate key to return based on the key type and environment.
        if ( 'public' === $key_type ) {
            return $test_mode ? $settings['test_public_key'] : $settings['live_public_key'];
        } elseif ( 'secret' === $key_type ) {
            return $test_mode ? $settings['test_secret_key'] : $settings['live_secret_key'];
        }

        // Return an empty string if the key type is not recognized.
        return '';
    }
    /**
     * Settings Form Field.
     */
    public static function get_settings_form_fields() {

        $settings_form_fields = array(
            'enabled'                          => array(
                'title'       => __( 'Enable/Disable', 'woo-korapay' ),
                'label'       => __( 'Enable Kora', 'woo-korapay' ),
                'type'        => 'checkbox',
                'description' => __( 'Enable Kora as a payment option on your website\'s checkout page.', 'woo-korapay' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'title'                            => array(
                'title'       => __( 'Title', 'woo-korapay' ),
                'type'        => 'text',
                'description' => __( 'This controls the payment method title which the user sees during checkout.', 'woo-korapay' ),
                'default'     => __( 'Debit/Credit Cards', 'woo-korapay' ),
                'desc_tip'    => true,
            ),
            'description'                      => array(
                'title'       => __( 'Description', 'woo-korapay' ),
                'type'        => 'textarea',
                'description' => __( 'This controls the payment method description which the user sees during checkout.', 'woo-korapay' ),
                'default'     => __( 'Make payment using your debit and credit cards', 'woo-korapay' ),
                'desc_tip'    => true,
            ),
            'testmode'                         => array(
                'title'       => __( 'Test mode', 'woo-korapay' ),
                'label'       => __( 'Enable Test Mode', 'woo-korapay' ),
                'type'        => 'checkbox',
                'description' => __( 'Test mode enables you to test payments before going live. <br />Once the LIVE MODE is enabled on your Kora account uncheck this.', 'woo-korapay' ),
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            'payment_page_type'                     => array(
                'title'       => __( 'Payment Option', 'woo-korapay' ),
                'type'        => 'select',
                //'description' => __( 'Popup shows the payment popup on the page while Redirect will redirect the customer to Kora to make payment.', 'woo-korapay' ),
                'default'     => '',
                'desc_tip'    => false,
                'options'     => array(
                    /*''          => __( 'Select One', 'woo-korapay' ),
                    'inline'    => __( 'Popup', 'woo-korapay' ),*/
                    'redirect'  => __( 'Redirect', 'woo-korapay' ),
                ),
            ),
            'test_secret_key'                  => array(
                'title'       => __( 'Test Secret Key', 'woo-korapay' ),
                'type'        => 'password',
                'description' => __( 'Enter your Test Secret Key here', 'woo-korapay' ),
                'default'     => '',
            ),
            'test_public_key'                  => array(
                'title'       => __( 'Test Public Key', 'woo-korapay' ),
                'type'        => 'text',
                'description' => __( 'Enter your Test Public Key here.', 'woo-korapay' ),
                'default'     => '',
            ),
            'live_secret_key'                  => array(
                'title'       => __( 'Live Secret Key', 'woo-korapay' ),
                'type'        => 'password',
                'description' => __( 'Enter your Live Secret Key here.', 'woo-korapay' ),
                'default'     => '',
            ),
            'live_public_key'                  => array(
                'title'       => __( 'Live Public Key', 'woo-korapay' ),
                'type'        => 'text',
                'description' => __( 'Enter your Live Public Key here.', 'woo-korapay' ),
                'default'     => '',
            ),
            'autocomplete_order'               => array(
                'title'       => __( 'Autocomplete Order After Payment', 'woo-korapay' ),
                'label'       => __( 'Autocomplete Order', 'woo-korapay' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-autocomplete-order',
                'description' => __( 'If enabled, the order will be marked as complete after successful payment', 'woo-korapay' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'remove_cancel_order_button'       => array(
                'title'       => __( 'Remove Cancel Order & Restore Cart Button', 'woo-korapay' ),
                'label'       => __( 'Remove the cancel order & restore cart button on the pay for order page', 'woo-korapay' ),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
            ),/*
            'saved_cards'                      => array(
                'title'       => __( 'Saved Cards', 'woo-korapay' ),
                'label'       => __( 'Enable Payment via Saved Cards', 'woo-korapay' ),
                'type'        => 'checkbox',
                'description' => __( 'If enabled, users will be able to pay with a saved card during checkout. Card details are saved on Korapay servers, not on your store.<br>Note that you need to have a valid SSL certificate installed.', 'woo-korapay' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'custom_metadata'                  => array(
                'title'       => __( 'Custom Metadata', 'woo-korapay' ),
                'label'       => __( 'Enable Custom Metadata', 'woo-korapay' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-metadata',
                'description' => __( 'If enabled, you will be able to send more information about the order to Korapay.', 'woo-korapay' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_order_id'                    => array(
                'title'       => __( 'Order ID', 'woo-korapay' ),
                'label'       => __( 'Send Order ID', 'woo-korapay' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-order-id',
                'description' => __( 'If checked, the Order ID will be sent to Korapay', 'woo-korapay' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_name'                        => array(
                'title'       => __( 'Customer Name', 'woo-korapay' ),
                'label'       => __( 'Send Customer Name', 'woo-korapay' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-name',
                'description' => __( 'If checked, the customer full name will be sent to Korapay', 'woo-korapay' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_email'                       => array(
                'title'       => __( 'Customer Email', 'woo-korapay' ),
                'label'       => __( 'Send Customer Email', 'woo-korapay' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-email',
                'description' => __( 'If checked, the customer email address will be sent to Korapay', 'woo-korapay' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_phone'                       => array(
                'title'       => __( 'Customer Phone', 'woo-korapay' ),
                'label'       => __( 'Send Customer Phone', 'woo-korapay' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-phone',
                'description' => __( 'If checked, the customer phone will be sent to Korapay', 'woo-korapay' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_billing_address'             => array(
                'title'       => __( 'Order Billing Address', 'woo-korapay' ),
                'label'       => __( 'Send Order Billing Address', 'woo-korapay' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-billing-address',
                'description' => __( 'If checked, the order billing address will be sent to Korapay', 'woo-korapay' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_shipping_address'            => array(
                'title'       => __( 'Order Shipping Address', 'woo-korapay' ),
                'label'       => __( 'Send Order Shipping Address', 'woo-korapay' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-shipping-address',
                'description' => __( 'If checked, the order shipping address will be sent to Korapay', 'woo-korapay' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'meta_products'                    => array(
                'title'       => __( 'Product(s) Purchased', 'woo-korapay' ),
                'label'       => __( 'Send Product(s) Purchased', 'woo-korapay' ),
                'type'        => 'checkbox',
                'class'       => 'wc-korapay-meta-products',
                'description' => __( 'If checked, the product(s) purchased will be sent to Korapay', 'woo-korapay' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),*/
        );
        
        return apply_filters( 'wc_korapay_settings', $settings_form_fields );
    }
}
