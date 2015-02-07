<?php 
    /*
    Plugin Name: Bimbler WooFoo
    Plugin URI: http://www.bimblers.com
    Description: Plugin to remove extraneous fields from the WooCommerce checkout page and to add custom statuses.
    Author: Paul Perkins
    Version: 0.1
    Author URI: http://www.bimblers.com
    */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
        die;
} // end if

require_once( plugin_dir_path( __FILE__ ) . 'class-bimbler-woofoo.php' );

Bimbler_WooFoo::get_instance();
