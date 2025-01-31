=== Kora WooCommerce Payment Gateway ===
Contributors: Kora  
Tags: payment gateway, WooCommerce, Kora, e-commerce, African payments  
Requires at least: 5.0  
Tested up to: 6.4  
WC requires at least: 7.0.0  
WC tested up to: 9.3.0  
Stable tag: 1.1.1
License: GPLv3 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

== Description ==

The **Kora WooCommerce Payment Gateway** allows you to accept payments directly on your WooCommerce store using Mastercard, Visa, Verve, Mobile Money, Bank Transfer, and more, making it perfect for businesses looking to reach customers across Africa.

Get up and running in minutes with Kora's easy integration. Whether it's secure card payments or mobile money transactions, your store can effortlessly manage them all!

= Plugin Features: =

* Accept payments via Mastercard, Visa, Verve, USSD, Mobile Money, Bank Transfer, EFT, and more.
* Multiple currency support, particularly for payments in Nigerian Naira (NGN), Ghanaian Cedi (GHS), and Kenyan Shilling (KES).
* Seamless integration with WooCommerce checkout — customers can pay right on your site.
* Test mode for easy testing before going live.

== Note ==

This plugin is designed for merchants operating in Ghana 🇬🇭, Kenya 🇰🇪, and Nigeria 🇳🇬.

== Installation ==

1. Go to **WordPress Admin** > **Plugins** > **Add New** from the left-hand menu.
2. In the search box, type **Kora WooCommerce Payment Gateway**.
3. Click **Install Now** when you find **Kora WooCommerce Payment Gateway**.
4. After installation, **activate** the plugin.

== Kora Setup and Configuration ==

1. Go to **WooCommerce > Settings** and click on the **Payments** tab.
2. You will see **Kora** listed with other payment methods. Click **Set Up** to configure the plugin.
3. On the configuration page, you'll find several options to adjust:

    1. **Enable/Disable**: Check this box to enable Kora on your store’s checkout page.
    2. **Title**: This title appears on the payment options during checkout (default: "Kora").
    3. **Description**: Add a custom description under the payment fields, guiding customers on the available payment methods.
    4. **Test Mode**: Toggle this to enable test mode, allowing you to use Kora's test API keys for payment simulation.
    5. **Payment Page Type**: Choose whether the customer stays on your site (Popup) or gets redirected for payment (Redirect).
    6. **API Keys**: Input your API keys—either live or test keys depending on your test mode selection. You can retrieve these from your Kora dashboard.
    7. **Additional Settings**: Optional settings such as adding custom metadata for transactions or configuring the Webhook URL for seamless order status updates.

4. After configuring your settings, click **Save Changes** to apply.

== Webhook Setup ==

To avoid network issues from affecting order updates, we recommend setting up a Webhook URL. This will ensure that your store is notified when payments are completed. You can copy the Webhook URL provided on the settings page and paste it into your Kora dashboard under **Settings > API Keys & Webhooks**.

== Frequently Asked Questions ==

= What currencies are supported? =

Kora supports several African currencies, including Nigerian Naira (NGN), Ghanaian Cedi (GHS), and Kenyan Shilling (KES).

= How do I test Kora before going live? =

Simply toggle **Test Mode** in the Kora settings, and use the test API keys provided by Kora to simulate transactions. This allows you to ensure everything works smoothly before accepting real payments.

= What do I need to use this plugin? =

* A Kora merchant account. [Sign up here](https://merchant.korapay.com/auth/signup) if you don’t have one.
* An active [WooCommerce installation](https://woocommerce.com/).
* A valid SSL certificate for secure payments.

= Why can’t I see Kora in the checkout? =

Please make sure that you’ve enabled Kora in the WooCommerce settings and correctly entered your API keys. Also, ensure that the settings have been saved.

== Screenshots ==

1. Kora Payment Gateway Settings Page.  
   ![Settings Screenshot Placeholder](https://via.placeholder.com/800x400)

2. Customer Checkout Experience using Kora.  
   ![Checkout Screenshot Placeholder](https://via.placeholder.com/800x400)

3. WooCommerce Order Page showing Kora Transactions.  
   ![Order Screenshot Placeholder](https://via.placeholder.com/800x400)

== Upgrade Notice ==

= 1.0 =

* Initial release of Kora WooCommerce Payment Gateway.
