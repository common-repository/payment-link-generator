<?php
if( !defined( 'WP_UNINSTALL_PLUGIN') ){
    die;
}
$wplg_opts = eos_wplg_get_option( 'eos_wplg_main' );
if( isset( $wplg_opts['ghost_product'] ) && absint( $wplg_opts['ghost_product'] ) > 0 ){
  wp_delete_post( absint( $wplg_opts['ghost_product'] ),true );
}
delete_site_option( 'eos_wplg_main' );
flush_rewrite_rules();
