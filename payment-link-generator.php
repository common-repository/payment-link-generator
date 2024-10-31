<?php
/*
Plugin Name: Payment Link Generator
Description: Generate and share a direct checkout link to be paid through WooCommerce.
Author: Jose Mortellaro
Author URI: https://josemortellaro.com
Text Domain: eos-wplg
Domain Path: /languages/
Version: 0.0.7
*/
/*  This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

//Definitions
define( 'EOS_WPLG_VERSION','0.0.7' );
define( 'EOS_WPLG_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );
define( 'EOS_WPLG_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );
define( 'EOS_WPLG_PLUGIN_BASE_NAME', untrailingslashit( plugin_basename( __FILE__ ) ) );

if( is_admin() || isset( $_REQUEST['amount'] ) ){
	//Filter translation files
	add_action( 'init','eos_wplg_load_textdomain' );
	add_filter( 'load_textdomain_mofile', 'eos_wplg_load_translation_file',10,2 );
}

//It loads plugin translation files
function eos_wplg_load_textdomain(){
	load_plugin_textdomain( 'eos-wplg', FALSE,EOS_WPLG_PLUGIN_DIR . '/languages/' );
}

//Filter function to read plugin translation files
function eos_wplg_load_translation_file( $mofile, $domain ) {
	if ( 'eos-wplg' === $domain ) {
		$loc = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$mofile = EOS_WPLG_PLUGIN_DIR . '/languages/eos-wplg-' . $loc . '.mo';
	}
	return $mofile;
}

//Actions triggered after plugin activation or after a new site of a multisite installation is created
function eos_wplg_initialize_plugin( $networkwide ){
	require EOS_WPLG_PLUGIN_DIR.'/wplg-plugin-activation.php';
}
register_activation_hook( __FILE__, 'eos_wplg_initialize_plugin' );

//Update options in case of single or multisite installation.
function eos_wplg_update_option( $option,$newvalue,$autoload = true ){
	if( !is_multisite() ){
		return update_option( $option,$newvalue,$autoload );
	}
	else{
		return update_blog_option( get_current_blog_id(),$option,$newvalue );
	}
}

//Get options in case of single or multisite installation.
function eos_wplg_get_option( $option ){
	if( !is_multisite() ){
		return get_option( $option );
	}
	else{
		return get_blog_option( get_current_blog_id(),$option );
	}
}

add_action( 'pre_get_posts', 'eos_wplg_filter_pre_get_posts' );
//Exclude ghost product form the query
function eos_wplg_filter_pre_get_posts( $query ) {
	if( method_exists( $query,'set' ) ){
		$opts = eos_wplg_get_option( 'eos_wplg_main' );
		if( isset( $opts['ghost_product'] ) && absint( $opts['ghost_product'] ) > 0 ){
			$posts_not_in = $query->get( 'post__not_in' );
			$posts_not_in = array_merge( $posts_not_in,array( absint( $opts['ghost_product'] ) ) );
			$query->set( 'post__not_in',$posts_not_in );
		}
	}
}

add_action( 'init','eos_wplg_rewrite_rules' );
//Add rewrite rules
function eos_wplg_rewrite_rules() {
	$wplg_opts = eos_wplg_get_option( 'eos_wplg_main' );
	if( isset( $wplg_opts['ghost_product'] ) && function_exists( 'wc_get_page_id' ) ){
  	add_rewrite_rule('/checkout\?amount\=([0-9]+)/', 'index.php?page_id='.absint( wc_get_page_id( 'checkout' ) ).'&add-to-cart='.absint( ( $wplg_opts['ghost_product'] ) ).'&amount=$1', 'top');
  	add_rewrite_rule('/payme\?amount\=([0-9]+)/', 'index.php?page_id='.absint( wc_get_page_id( 'checkout' ) ).'&add-to-cart='.absint( ( $wplg_opts['ghost_product'] ) ).'&amount=$1', 'top');
	}
}

if( is_admin() ){
	//Load file for the settings page
	require EOS_WPLG_PLUGIN_DIR.'/admin/wplg-admin.php';
}







add_action( 'template_redirect','eos_wplg_checkout',20 );
//Set quantity on the checkout page
function eos_wplg_checkout() {
	global $woocommerce;
	$amount = eos_wplg_payme_amount();
	if( !$amount || !function_exists( 'WC' ) || !WC() || null === WC()->cart ){
		if( $woocommerce && isset( $woocommerc->session ) ){
			$woocommerce->session->__unset( 'payme' );
		}
		return;
	}
	$wplg_opts = eos_wplg_get_option( 'eos_wplg_main' );
	if( isset( $wplg_opts['ghost_product'] ) ){
		if( $woocommerce && isset( $woocommerce->session ) ){
			$woocommerce->session->set( 'payme',absint( $amount ) );
		}
		eos_wplg_only_payme_in_cart( $wplg_opts['ghost_product'] );
		if( isset( $_REQUEST['key'] ) && false !== strpos( $_REQUEST['key'],'wc_order_' ) ){
			$thankyou_id = eos_wplg_get_option( 'eos_wplg_thankyou_page' );
			$url = $thankyou_id && absint( $thankyou_id ) > 0 ? get_permalink( absint( $thankyou_id ) ) : get_home_url().'?thankyou=true';
		  wp_safe_redirect( esc_url( $url ) );
		}
	}
}

//Helpder function to empty the cart and add the payme product
function eos_wplg_only_payme_in_cart( $product_id ){
	if( !function_exists( 'WC' ) ) return false;
	WC()->cart->empty_cart();
	WC()->cart->add_to_cart( absint( $product_id ) );
}

add_filter('woocommerce_is_purchasable','eos_wplg_preorder_is_purchasable', 10, 2 );
//Set purchasable true if payme ghost product
function eos_wplg_preorder_is_purchasable( $is_purchasable,$object ) {
	$wplg_opts = eos_wplg_get_option( 'eos_wplg_main' );
	if( isset( $wplg_opts['ghost_product'] ) && $object->get_id() === $wplg_opts['ghost_product'] ){
		return true;
	}
  return $is_purchasable;
}

add_filter( 'woocommerce_product_get_price', 'eos_wplg_get_price',9999999, 1 );
//Return price according to the URL query argument
function eos_wplg_get_price( $price ){
	global $woocommerce;
	if( $woocommerce && isset( $woocommerce->session ) ){
		$session = $woocommerce->session->get( 'payme' );
		if( $session && absint( $session ) > 0 ) return absint( $session );
	}
	$amount = eos_wplg_payme_amount();
	if( $amount ){
		add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false');
		add_filter( 'woocommerce_checkout_fields', 'eos_wplg_remove_checkout_fields' );
		$price = $amount;
	}
	return $price;
}

add_action( 'plugins_loaded','eos_wplg_after_plugins_loaded' );
//After plugins loaded
function eos_wplg_after_plugins_loaded(){
	$amount = eos_wplg_payme_amount();
	if( $amount ){
		$wplg_opts = eos_wplg_get_option( 'eos_wplg_main' );
		if( isset( $wplg_opts['ghost_product'] ) ){
			$_GET['payme'] = floatval( $amount );
			$_REQUEST['payme'] = floatval( $amount );
			$_GET['add-to-cart'] = absint( $wplg_opts['ghost_product'] );
			$_REQUEST['add-to-cart'] = absint( $wplg_opts['ghost_product'] );
			if( function_exists( 'wc_get_page_id' ) ){
				$_GET['page_id'] = absint( wc_get_page_id( 'checkout' ) );
				$_REQUEST['page_id'] = absint( $_GET['page_id'] );
			}
			add_action( 'wp_enqueue_scripts','eos_wplg_enqueue_scripts',999999 );
			add_action( 'woocommerce_before_calculate_totals', 'eos_wplg_custom_items_prices', 10, 1 );
			add_filter( 'woocommerce_add_to_cart_sold_individually_found_in_cart','__return_false' );
			add_filter( 'woocommerce_add_message','__return_false' );
			remove_all_actions( 'woocommerce_before_checkout_form' );

		}
	}
}
add_filter( 'woocommerce_get_checkout_url','eos_wplg_checkout_url' );
//Filter checkout URL
function eos_wplg_checkout_url( $checkout_url ){
	if( isset( $_REQUEST['amount'] ) ){
		$checkout_url = add_query_arg( 'amount',floatval( $_REQUEST['amount'] ),$checkout_url );
	}
	return $checkout_url;
}
// Dequeue scripts on checkout
function eos_wplg_enqueue_scripts(){
	wp_enqueue_script( 'eos-wplg',EOS_WPLG_PLUGIN_URL.'/assets/js/wplg.js',array( 'jquery' ),EOS_WPLG_PLUGIN_URL,true );
}

//Filter price from the URL query argument
function eos_wplg_custom_items_prices( $cart ) {
	$price = eos_wplg_payme_amount();
	if( 0 === $price ){
		$default_amount = eos_wplg_get_option( 'eos_wplg_default_amount' );
		$default_amount = $default_amount ? $default_amount : 100;
		$price = $default_amount;
	}
  foreach ( $cart->get_cart() as $cart_item ) {
      $cart_item['data']->set_price( $price );
  }
}

//Remove unneeded fields;
function eos_wplg_remove_checkout_fields( $fields ) {
	foreach( $fields as $field_type => $field_arr ){
		if( 'billing' !== $field_type ){
			foreach( $field_arr as $field_name => $arr ){
				if( 'order_comments' !== $field_name ){
					unset( $fields[$field_type][$field_name] );
				}
			}
		}
	}
	return $fields;
}
add_action( 'init','eos_wplg_remove_checkout_process_actions' );
//Remove all actions hooked to the checkout process
function eos_wplg_remove_checkout_process_actions(){
	if( eos_wplg_payme_amount() ){
		remove_all_actions( 'woocommerce_checkout_process' );
	}
}

add_action( 'woocommerce_after_checkout_validation','eos_wplg_after_checkout_validation',999999,2 );
function eos_wplg_after_checkout_validation( $data,$errors ){
	if( isset( $_REQUEST['add-to-cart'] ) ){
		$wplg_opts = eos_wplg_get_option( 'eos_wplg_main' );
		if( isset( $wplg_opts['ghost_product'] ) && $_REQUEST['add-to-cart'] === $wplg_opts['ghost_product'] ){
			eos_wplg_only_payme_in_cart( absint( $_REQUEST['add-to-cart'] ) );
			global $woocommerc;
			if( $woocommerce && isset( $woocommerc->session ) ){
				$woocommerce->session->__unset( 'payme' );
			}
		}
	}
}
add_action( 'woocommerce_checkout_process','eos_wplg_custom_checkout_field_process',999999 );
function eos_wplg_custom_checkout_field_process(){
	if( eos_wplg_payme_amount() ){
    if ( !$_POST['billing_first_name'] || '' === $_POST['billing_first_name'] ){
      wc_add_notice( esc_html__( 'Please enter a first name.','eos-wplg' ),'error' );
		}
    if ( !$_POST['billing_last_name'] || '' === $_POST['billing_last_name'] ){
      wc_add_notice( esc_html__( 'Please enter a last name.','eos-wplg' ),'error' );
		}
    if ( !$_POST['billing_email'] || sanitize_email( $_POST['billing_email'] ) !== $_POST['billing_email'] ){
      wc_add_notice( esc_html__( 'Please enter a valid email.','eos-wplg' ),'error' );
		}
	}
}

add_filter( 'woocommerce_billing_fields', 'eos_wplg_unrequire_fields');
//Unrequire fields
function eos_wplg_unrequire_fields( $fields ) {
	if( eos_wplg_payme_amount() ){
		$fields['billing_state']['required'] = false;
		$fields['billing_country']['required'] = false;
		$fields['billing_address_1']['required'] = false;
		$fields['billing_address_2']['required'] = false;
		$fields['billing_postcode']['required'] = false;
		$fields['billing_phone']['required'] = false;
	}
	return $fields;
}

//CHeck if payme session
function eos_wplg_payme_amount(){
	static $price = 0;
	if( $price) return false;
	$called = true;
	if( isset( $_REQUEST['wc-ajax'] ) && isset( $_SERVER['HTTP_REFERER'] ) ){
		$refA = parse_url( esc_url( $_SERVER['HTTP_REFERER'] ) );
		if( isset( $refA['query'] ) && false !== strpos( $refA['query'],'amount=' ) ){
			$amountA = explode( 'amount=',$refA['query'] );
			if( isset( $amountA[1] ) ){
				$amountA = explode( '&',$amountA[1] );
				if( absint( $amountA[0] ) > 0 ){
					return floatval( $amountA[0] );
				}
			}
		}
	}
	if( isset( $_REQUEST['_eos_wplg_amount'] ) ){
		return floatval( $_REQUEST['_eos_wplg_amount'] );
	}
	if( isset( $_REQUEST['amount'] ) ){
		return floatval( $_REQUEST['amount'] );
	}
	return false;
}

//Add custom checkout fields to send the amount
add_action('woocommerce_after_order_notes', 'eos_wplg_checkout_fields');
function eos_wplg_checkout_fields( $checkout ){
	$amount = eos_wplg_payme_amount();
	if( $amount ){
	  woocommerce_form_field( '_eos_wpg_amount',array(
	    'type' => 'hidden',
	    'class' => array( 'eos-wpg-amount' ),
	    'required' => true,
	  ),floatval( $amount ) );
	}
}
