<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://site-pro.co.il
 * @since      1.0.0
 *
 * @package    Epsilon_Sap_Sync
 * @subpackage Epsilon_Sap_Sync/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Epsilon_Sap_Sync
 * @subpackage Epsilon_Sap_Sync/public
 * @author     DoroNess <support@sitepro.co.il>
 */
class Epsilon_Sap_Sync_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */


	private $sap_connector;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;

		$this->version = $version;

		$this->sap_connector = new Sap_Connector();

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Epsilon_Sap_Sync_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Epsilon_Sap_Sync_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/epsilon-sap-sync-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Epsilon_Sap_Sync_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Epsilon_Sap_Sync_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/epsilon-sap-sync-public.js', array( 'jquery' ), $this->version, false );

	}

	public function send_order_for_sap (int $order_id) : bool {

		$products_list = array();
		
		// Get the order object
		$order = wc_get_order($order_id);

		if ( !$order ) {

			error_log('order not found');

			return false;
			
		}
			
		
		// Loop through the order items
		foreach ( $order->get_items() as $item_id => $item ) {
 
			$item = array(
				'ItemCode'			=> $item->get_product()->get_sku(),
				'Quantity' 			=> $item->get_quantity(),
				'Price' 				=> $item->get_total(),
				"ItemDescription" => $item->get_name(),

			);

			array_push($products_list, $item);

		}

		//get billing email

		$billing_email = $order->get_billing_email();

		//get user id by email

		$user = get_user_by( 'email', $billing_email );		

		if ( $user && !empty($products_list) ) {

			$card_code = get_field('card_sap_number', 'user_' . $user->ID);

			//if card code is empty, and not null, and not 0, then send order to sap

			if ( !empty($card_code) && $card_code != 0 ) 
		 		return $this->sap_connector->send_order_to_sap($card_code, $products_list, $order_id);
			else
				return false;
		 
		}

		else {

			return false;

		}

	}

	/**
 * Disable guest checkout if option is set to yes
 */

	public function disable_guest_checkout () {

		//TODO: add code to make sure other relevant woo setting are set

		// Get the option value
		$allow_guest_checkout = get_option( 'woocommerce_enable_guest_checkout' );

		// Check if option is set to yes
		if ( 'yes' === $allow_guest_checkout ) 
		// Set option value to no
			update_option( 'woocommerce_enable_guest_checkout', 'no' );
		
	}

	public function schedule_inventory_update () {

		if (!wp_next_scheduled('cron_update_product_inventory')) {
        wp_schedule_event(time(), 'twicedaily', 'cron_update_product_inventory');
    }

	}

	public function update_product_inventory () {
	    
	    error_log('----------------- cron sync started --------------------------------');

		$sync_result = $this->sap_connector->sync_full_invantory();

		$sync_result['time'] = date('Y-m-d H:i:s');
		
    
		error_log(print_r($sync_result, true));
		
		error_log('----------------- cron sync ended ----------------------------------');

	}



}
