=== Payment Link Generator ===
Contributors: giuse
Donate link: https://commerce.coinbase.com/checkout/501bd0a4-965c-4e5d-b614-aae0e35a9d6c
Tags: payment link, payment link generator
Requires at least: 4.6
Tested up to: 5.7
Stable tag: 0.0.7
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generate and share a direct checkout link to be paid through WooCommerce.

== Description ==

Generate and share a direct checkout link to be paid through WooCommerce.

Sharing a link that looks like https://yourwebsite.com/payme?amount=250, your clients will see the checkout page where they can pay the specified amount.

In the checkout page your client will have the possibility to choose the payment method you have set up in the WooCommerce settings.

After payment the process handled by WooCommerce works as usually. The client will receive the emails sent by WooCommerce, and you can add all the functionalities given by the WooCommerce add-ons.

You will find the link in WooCommerce => Settings => Payme Link.



== Installation ==

1. Upload the entire `payment-link-generator` folder to the `/wp-content/plugins/` directory or install it using the usual installation button in the Plugins administration page.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. After successful activation you will find the settings page on WooCommerce => Settings => Payme Link.
4. All done. Good job!



== Changelog ==



= 0.0.7 =
* Fix: Checkout not working

= 0.0.6 =
* Fix: Not possible to expand the Payment methods


= 0.0.5 =
* Fix: Settings page link not working on some backend pages


= 0.0.4 =
* Fix: PHP warning on the plugins page
* Improved: settings page


= 0.0.3 =
* Added: warning if WooCommerce is not active


= 0.0.2 =
* Added: option thank you page
* Added: rewrite rules

= 0.0.1 =
* Initial Release
