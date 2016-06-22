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

        	// Enqueue the CSS and Fontawesome.
        	add_action ('admin_enqueue_scripts', array ($this, 'enqueue_scripts'));
        	
        	// Remove checkout fields.
        	add_filter( 'woocommerce_checkout_fields' , array ($this, 'custom_override_checkout_fields'));

        	// Register new order statuses.
        	add_action( 'init', array ($this, 'register_bimbler_order_statuses'));
        	
        	// Add new order statuses.
        	add_filter( 'wc_order_statuses', array ($this, 'add_bimbler_order_statuses'));

			// Turn off breadcrumbs on Woo pages.			
			add_action( 'init', array ($this, 'remove_wc_breadcrumbs'));
			
			// Add hook for custom statuses on order page.
			add_action('admin_footer-edit.php', array ($this, 'custom_bulk_admin_footer'));
			
			// Process bulk updates.
			add_action('load-edit.php', array ($this, 'custom_bulk_action')); 
        	
		} // End constructor.
	
	
	
	
		/**
		* Adds a note (comment) to the order
		*
		* @access public
		* @param string $note Note to add
		* @param int $is_customer_note (default: 0) Is this a note for the customer?
		* @return id Comment ID
		*
		* *file is class-wp-order.php*
		*/
		public function add_order_note( $post_id, $note, $is_customer_note = 0 ) {
		
			$is_customer_note = intval( $is_customer_note );
		
			if ( is_user_logged_in() && current_user_can( 'manage_woocommerce' ) ) {
				$user                 = get_user_by( 'id', get_current_user_id() );
				$comment_author       = $user->display_name;
				$comment_author_email = $user->user_email;
			} else {
				$comment_author       = __( 'WooCommerce', 'woocommerce' );
				$comment_author_email = strtolower( __( 'WooCommerce', 'woocommerce' ) ) . '@';
				$comment_author_email .= isset( $_SERVER['HTTP_HOST'] ) ? str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) : 'noreply.com';
				$comment_author_email = sanitize_email( $comment_author_email );
			}
		
			$comment_post_ID        = $post_id;
			$comment_author_url     = '';
			$comment_content        = $note;
			$comment_agent          = 'WooCommerce';
			$comment_type           = 'order_note';
			$comment_parent         = 0;
			$comment_approved       = 1;
			$commentdata            = apply_filters( 'woocommerce_new_order_note_data', compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_agent', 'comment_type', 'comment_parent', 'comment_approved' ), array( 'order_id' => $this->id, 'is_customer_note' => $is_customer_note ) );
		
			$comment_id = wp_insert_comment( $commentdata );
		
			add_comment_meta( $comment_id, 'is_customer_note', $is_customer_note );
		
			if ( $is_customer_note )
				do_action( 'woocommerce_new_customer_note', array( 'order_id' => $this->id, 'customer_note' => $note ) );
		
			return $comment_id;
		}
			
		// Add custom statuses on order page.
		function custom_bulk_admin_footer() {
 
			global $post_type;
			
			if($post_type == 'shop_order') {
				?>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery('<option>').val('wc-bimbler-paid').text('<?php _e('Mark paid')?>').appendTo("select[name='action']");
						jQuery('<option>').val('wc-bimbler-paid').text('<?php _e('Mark paid')?>').appendTo("select[name='action2']");
						jQuery('<option>').val('wc-bimbler-ordered').text('<?php _e('Mark ordered')?>').appendTo("select[name='action']");
						jQuery('<option>').val('wc-bimbler-ordered').text('<?php _e('Mark ordered')?>').appendTo("select[name='action2']");
						jQuery('<option>').val('wc-bimbler-arrived').text('<?php _e('Mark arrived')?>').appendTo("select[name='action']");
						jQuery('<option>').val('wc-bimbler-arrived').text('<?php _e('Mark arrived')?>').appendTo("select[name='action2']");
					});
				</script>
				<?php
			} 
		}
		
		function update_post_status ($post_id, $new_status, $new_status_text) {
			
			$post_object = get_post ($post_id);
			
			error_log ('Updating order ID ' . $post_id . ' status from ' . $post_object->post_status . ' to ' . $new_status);
			
			$post_object->post_status = $new_status;
			
			wp_update_post ($post_object);
			
			$this->add_order_note ($post_id, 'Order status changed to ' . $new_status_text . '.');
			//error_log ('Post:' .  json_encode ($post_object));
		}
		
		// Process custom bulk action.
		function custom_bulk_action () {
			
			$wp_list_table = _get_list_table('WP_Posts_List_Table');
			
			$action = $wp_list_table->current_action();
			
			if (empty ($action)) {
				return;
			}
			
			//error_log ('Bulk action is ' . $action);
			
			check_admin_referer('bulk-posts');

			$post_ids = $_GET['post'];
			
			$sendback = 'post_type=shop_order';
			
			$sendback = remove_query_arg( array('ordered', 'arrived', 'paid', 'ids'), wp_get_referer() );
			
			if ( ! $sendback ) {				
				$sendback = admin_url( "edit.php?post_type=$post_type" );
			}
			
			switch ($action) {
				case 'wc-bimbler-paid':
				
					$new_status = 'Paid';
					
					$paid = 0;
					
					foreach( $post_ids as $post_id ) {
						$this->update_post_status ($post_id, $action, $new_status);
							
						$paid++;
					}
									
					$sendback = add_query_arg( array('paid' => $paid, 'ids' => join(',', $post_ids) ), $sendback );

					break;
					
				case 'wc-bimbler-ordered':
				
					$new_status = 'Ordered';

					$ordered = 0;
					
					foreach( $post_ids as $post_id ) {
						$this->update_post_status ($post_id, $action, $new_status);
							
						$ordered++;
					}
									
					$sendback = add_query_arg( array('ordered' => $ordered, 'ids' => join(',', $post_ids) ), $sendback );

					break;
					
				case 'wc-bimbler-arrived':
					$new_status = 'Arrived';
				
					$arrived = 0;
					
					foreach( $post_ids as $post_id ) {
						$this->update_post_status ($post_id, $action, $new_status);
							
						$arrived++;
					}
									
					$sendback = add_query_arg( array('arrived' => $arrived, 'ids' => join(',', $post_ids) ), $sendback );

					break;
					
					
				default:
					return;
			}
			
			wp_redirect($sendback);
			
			exit ();
		}
	
		function register_bimbler_order_statuses() {
			
			register_post_status( 'wc-bimbler-paid', 
									array(	'label'                     => 'Paid',
											'public'                    => true,
											'exclude_from_search'       => false,
											'show_in_admin_all_list'    => true,
											'show_in_admin_status_list' => true,
											'label_count'               => _n_noop( 'Paid <span class="count">(%s)</span>', 
																					'Paid <span class="count">(%s)</span>' )
								));
			
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
		 * 	- pending payment
		 *  - paid (new)
		 *
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

				if ( 'wc-pending' === $key ) {
					$new_order_statuses['wc-bimbler-paid'] = 'Paid';
				}

		
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

		wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css'); 
	}
	
	/**
	 * Remove WooCommerce breadcumb.
	 *
	 */
	function remove_wc_breadcrumbs() {
    		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );
	}

	
} // End class
