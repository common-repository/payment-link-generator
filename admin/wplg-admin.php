<?php
defined( 'EOS_WPLG_PLUGIN_DIR' ) || exit; // Exit if not accessed by the plugin

add_action( 'admin_notices','eos_wplg_admin_notices' );
//Warn user if WooCommerce is not installed
function eos_wplg_admin_notices(){
  if( !function_exists( 'WC') ){
  ?>
  <div class="notice notice-error" style="padding:20px"><?php esc_html_e( 'WooCommerce must be installed and active, in another case Payment Link Generator will not work','eos-wplg' ); ?></div>
  <?php
  }
}

add_filter( 'woocommerce_settings_tabs_array','eos_wplg_woo_tab_array',50,1 );
//Add plugin settings tab to the tabs of WooCommerce
function eos_wplg_woo_tab_array( $settings_tabs ) {
     $settings_tabs['eos_wplg'] = esc_html__( 'Payme Link','eos-wplg' );
     return $settings_tabs;
 }

add_action( 'woocommerce_settings_tabs_eos_wplg','eos_wplg_woo_settings_content',10 );
//Adds content to the Plugin settings
function eos_wplg_woo_settings_content(){
  $wplg_opts = eos_wplg_get_option( 'eos_wplg_main' );
  if( isset( $wplg_opts['ghost_product'] ) ){
    $checkout_url = wc_get_page_permalink( 'checkout' );
    if( !$checkout_url || esc_url( $checkout_url ) !== $checkout_url ){
      ?>
      <h2><?php esc_html_e( "You should first set the page for the checkout, only then you can have a pay-me link.",'eos-wplg' ); ?></h2>
      <?php
    }
    else{
      $default_amount = eos_wplg_get_option( 'eos_wplg_default_amount' );
      $default_amount = $default_amount ? $default_amount : 100;
      $url1 = get_home_url().'/payme/?amount='.absint( $default_amount );
      $url2 = get_home_url().'/checkout/?amount='.absint( $default_amount );
      ?>
      <h2><?php esc_html_e( 'Link to be paid.','eos-wplg' ); ?></h2>
      <p><?php esc_html_e( 'To get paid share the following URL:','eos-wplg' ); ?></p>
      <p><a href="<?php echo esc_url( $url1 ); ?>" target="_blank"><?php echo esc_url( $url1 ); ?></a></p>
      <p><?php esc_html_e( 'Alternatively you can also share this URL:','eos-wplg' ); ?></p>
      <p><a href="<?php echo esc_url( $url2 ); ?>" target="_blank"><?php echo esc_url( $url2 ); ?></a></p>
      <p><?php printf( esc_html__( 'Replace %s with the amount you want.','eos-wplg' ),$default_amount ); ?></p>
      <?php
    }
  }
  else{
    ?>
    <h2><?php esc_html_e( "It's not possible to generate a link, try disabling and reactivating again the plugin.",'eos-wplg' ); ?></h2>
    <?php
  }
  woocommerce_admin_fields( eos_wplg_woo_get_settings() );
}

//Callback for the settings
function eos_wplg_woo_get_settings(){
  $pages = array( 0 => esc_html__( 'Select page...','eos-wplg' ) );
  $posts = get_pages();
  foreach( $posts as $page ){
    $pages[$page->ID] = $page->post_title;
  }
  $settings = array(
   'default_amount_title' => array(
       'name'     => esc_html__( 'Default amount', 'eos-wplg' ),
       'type'     => 'title',
       'desc'     => '',
       'id'       => 'eos_wplg_default_amount_title'
   ),
   'eos_wplg_default_amount' => array(
    'name' => esc_html__( 'Default amount', 'eos-wplg' ),
    'type' => 'number',
    'desc' => esc_html__( 'Default amount when not specified in the URL', 'eos-wplg' ),
    'id'   => 'eos_wplg_default_amount',
    'std'      => 100,
    'default'  => 100
  ),
  array( 'type' => 'sectionend', 'id' => 'eos_wplg_default_amount' ),
   'thankyou_page_title' => array(
       'name'     => esc_html__( 'Thank you page', 'eos-wplg' ),
       'type'     => 'title',
       'desc'     => '',
       'id'       => 'eos_wplg_thankyou_page_title'
   ),
   'eos_wplg_thankyou_page' => array(
    'name' => esc_html__( 'Thank you page', 'eos-wplg' ),
    'type' => 'select',
    'desc' => esc_html__( 'Thank you page after checkout', 'eos-wplg' ),
    'id'   => 'eos_wplg_thankyou_page',
    'options' => $pages
  ),
  array( 'type' => 'sectionend', 'id' => 'eos_wplg_thankyou_page' ),
 );
 return apply_filters( 'wc_settings_tab_demo_settings', $settings );
}

add_action( 'woocommerce_update_options_eos_wplg','eos_wplg_update_freesoul_woo_settings' );
//Saves options
function eos_wplg_update_freesoul_woo_settings(){
   woocommerce_update_options( eos_wplg_woo_get_settings() );
}

add_action( 'admin_menu','register_my_custom_submenu_page',9999 );
//Add submenu item under WooCommerce
function register_my_custom_submenu_page() {
    add_menu_page( esc_html__( 'Payme Link','eos-wplg' ),esc_html__( 'Payme Link','eos-wplg' ),'manage_woocommerce','admin.php?page=wc-settings&tab=eos_wplg','','dashicons-admin-links',120 );
    add_submenu_page( 'woocommerce',esc_html__( 'Payme Link','eos-wplg' ),esc_html__( 'Payme Link','eos-wplg' ),'manage_woocommerce',admin_url( 'admin.php?page=wc-settings&tab=eos_wplg' ),'',120 );
}
$plugin = EOS_WPLG_PLUGIN_BASE_NAME;
add_filter( "plugin_action_links_$plugin", 'eos_wplg_plugin_add_settings_link' );
//It adds a settings link to the action links in the plugins page
function eos_wplg_plugin_add_settings_link( $links ) {
    if( !function_exists( 'WC') ){
      $settings_link = '<span style="font-weight:bold;color:#000">'.esc_html__( 'It needs WooCommerce!','eos-wplg' ).'</span>';
    }
    else{
      $settings_link = '<a class="eos-dp-setts" href="'.admin_url( 'admin.php?page=wc-settings&tab=eos_wplg' ).'">'.esc_html__( 'Settings','eos-wplg' ).'</a>';
    }
    array_push( $links, $settings_link );
  	return $links;
}
