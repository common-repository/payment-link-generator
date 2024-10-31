<?php
//Called by the main plugin file on plugin activation. It creates the ghost product if it still doesn't exists.
defined( 'EOS_WPLG_PLUGIN_DIR' ) || exit; // Exit if not accessed by the plugin

$wplg_opts = eos_wplg_get_option( 'eos_wplg_main' );
if( !isset( $wplg_opts['ghost_product'] ) ){
  $post_id = wp_insert_post( array(
      'post_title' => esc_html__( 'Custom order','eos-wplg' ),
      'post_content' => '',
      'post_status' => 'publish',
      'post_author' => 1,
      'post_type' => 'product'
  ) );
  if( $post_id ){
    update_post_meta( absint( $post_id ),'_eos_wplg_is_ghost','true' );
    update_post_meta( $post_id, '_visibility', 'hidden' );
    update_post_meta( $post_id, '_downloadable','no' );
    update_post_meta( $post_id, '_virtual','yes' );
    update_post_meta( $post_id, '_sold_individually','yes' );
    $wplg_opts['ghost_product'] = absint( $post_id );
    eos_wplg_update_option( 'eos_wplg_main',$wplg_opts );
  }
}
flush_rewrite_rules();
