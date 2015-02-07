<?php
/**
 * Bimbler NoMenu
 *
 * @package   Bimbler_WooFoo
 * @author    Paul Perkins <paul@paulperkins.net>
 * @license   GPL-2.0+
 * @link      http://www.paulperkins.net
 * @copyright 2014 Paul Perkins
 */

/**
 * Include dependencies necessary... (none at present)
 *
 */

/**
 * Bimbler Woo Foo
 *
 * @package Bimbler_WooFoo
 * @author  Paul Perkins <paul@paulperkins.net>
 */
class Bimbler_WooFoo {

        /*--------------------------------------------*
         * Constructor
         *--------------------------------------------*/

        /**
         * Instance of this class.
         *
         * @since    1.0.0
         *
         * @var      object
         */
        protected static $instance = null;

        /**
         * Return an instance of this class.
         *
         * @since     1.0.0
         *
         * @return    object    A single instance of this class.
         */
        public static function get_instance() {

                // If the single instance hasn't been set, set it now.
                if ( null == self::$instance ) {
                        self::$instance = new self;
                } // end if

                return self::$instance;

        } // end get_instance

        /**
         * Initializes the plugin by setting localization, admin styles, and content filters.
         */
        private function __construct() {

        	// Enqueue the CSS.
        	add_action ('admin_enqueue_scripts', array ($this, 'enqueue_scripts'));
        	
        	// Remove checkout fields.
        	add_filter( 'woocommerce_checkout_fields' , array ($this, 'custom_override_checkout_fields'));

        	// Register new order statuses.
        	add_action( 'init', array ($this, 'register_bimbler_order_statuses'));
        	
        	// Add new order statuses.
        	add_filter( 'wc_order_statuses', array ($this, 'add_bimbler_order_statuses'));
        	
		} // End constructor.
	
		function register_bimbler_order_statuses() {
			
			register_post_status( 'wc-bimbler-ordered', 
									array(	'label'                     => 'Ordered',
											'public'                    => true,
											'exclude_from_search'       => false,
											'show_in_admin_all_list'    => true,
											'show_in_admin_status_list' => true,
											'label_count'               => _n_noop( 'Ordered <span class="count">(%s)</span>', 
																					'Ordered <span class="count">(%s)</span>' )
								));
			
			register_post_status( 'wc-bimbler-arrived',
									array(	'label'                     => 'Arrived',
											'public'                    => true,
											'exclude_from_search'       => false,
											'show_in_admin_all_list'    => true,
											'show_in_admin_status_list' => true,
											'label_count'               => _n_noop( 'Arrived <span class="count">(%s)</span>',
																					'Arrived <span class="count">(%s)</span>' )
								));
		}
		
		/*
		 * Add custom order statuses:
		 * 	- processing
		 *  - ordered (new)
		 *  - arrived (new)
		 *  - completed
		 */
		function add_bimbler_order_statuses( $order_statuses ) {
		
			$new_order_statuses = array();
		
			// add new order status after processing
			foreach ( $order_statuses as $key => $status ) {
				
				$new_order_statuses[ $key ] = $status;
		
				if ( 'wc-processing' === $key ) {
					$new_order_statuses['wc-bimbler-arrived'] = 'Arrived';
					$new_order_statuses['wc-bimbler-ordered'] = 'Ordered';
				}
			}
		
			return $new_order_statuses;
		}		
		
		
	/**
	 * Remove Checkout Fields
	 *
	 */
	function custom_override_checkout_fields( $fields ) {
		//unset($fields['billing']['billing_first_name']);
		//unset($fields['billing']['billing_last_name']);
		unset($fields['billing']['billing_company']);
		unset($fields['billing']['billing_address_1']);
		unset($fields['billing']['billing_address_2']);
		unset($fields['billing']['billing_city']);
		unset($fields['billing']['billing_postcode']);
		unset($fields['billing']['billing_country']);
		unset($fields['billing']['billing_state']);
		unset($fields['billing']['billing_phone']);
		unset($fields['order']['order_comments']);
		return $fields;
	}
			
	/*
	 * Enqueue CSS.
	 */
	function enqueue_scripts () {
		
		wp_register_style( 'style-bimbler-woofoo', plugins_url('bimbler-woofoo.css', __FILE__) );
		wp_enqueue_style( 'style-bimbler-woofoo' );

	}
	
} // End class
