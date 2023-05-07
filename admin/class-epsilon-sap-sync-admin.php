<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://site-pro.co.il
 * @since      1.0.0
 *
 * @package    Epsilon_Sap_Sync
 * @subpackage Epsilon_Sap_Sync/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Epsilon_Sap_Sync
 * @subpackage Epsilon_Sap_Sync/admin
 * @author     DoroNess <support@sitepro.co.il>
 */
class Epsilon_Sap_Sync_Admin {

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

	private $sap_connector;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;

		$this->version = $version;

		$this->sap_connector = new Sap_Connector();

	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/epsilon-sap-sync-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_register_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/epsilon-sap-sync-admin.js', array( 'jquery' ), $this->version, false );

		$obj = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'sync_products' ),
		);

		wp_localize_script( $this->plugin_name, 'ajax_object', $obj  );

		wp_enqueue_script( $this->plugin_name);

	}

	public function add_plugin_admin_menu() {
		add_options_page( 'Epsilon SAP Sync', 'Epsilon SAP Sync', 'manage_options', $this->plugin_name, array($this, 'display_plugin_setup_page') );
	}

	public function display_plugin_setup_page () {

		//start the buffer
		ob_start();

		include_once( 'partials/epsilon-sap-sync-admin-display.php' );

		//get the buffer contents
		$contents = ob_get_contents();

		//clean the buffer
		ob_end_clean();

		//return the contents
		echo $contents;

	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function options_update() {

		ob_start();

		include_once( 'partials/epsilon-sap-sync-admin-display_code.php' );

				//get the buffer contents
		$contents = ob_get_contents();

		//clean the buffer
		ob_end_clean();

		//return the contents
		echo $contents;

	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function sync_invantory () : void	 {	

		$message_array = $this->sap_connector->sync_full_invantory();

		$updated = $message_array['updated_products'];

		$failed = $message_array['failed_products'];

		$statuse = $message_array['message'];

		$message = "Sync Status: $statuse. Failed: $failed. Updated: $updated.";

		//send json response success
		wp_send_json_success( $message,200 );

		exit();

	}

	/*
	* sync invantory in chunks
	*/
	public function sync_invantory_in_chunks () : void {
		
		$index = 0;

		$updated = 0;

		$failed = 0;

		$params = "?\$select=QuantityOnStock,ItemCode&\$skip=$index";

		$data = $this->sap_connector->get_quantity_data_in_chunks($params);
			
		while (isset($data['odata.nextLink'])) {
			
			$params =  str_replace("Items", "", $data['odata.nextLink']);

			$data = $this->sap_connector->get_quantity_data_in_chunks($params);

			if (isset($data['value'])) {
				
				$update_status = $this->update_products_quantity($data['value']);

				$updated += $update_status['updated_products'];

				$failed += $update_status['failed_products'];

			} else {
				
				break;

				wp_send_json_error( 'Something went wrong', 500);

			}
			
		} //end while

		$message = "Sync Status: Success. Failed: $failed. Updated: $updated.";

		//send json response success

		wp_send_json_success( $message,200 );
	}
	
	private function update_products_quantity (array $products) : array {

		$update_status = array(
			'updated_products' => 0,
			'failed_products' => 0,
		);

		foreach ($products as $product) {
			
			$quantity 	= $product['QuantityOnStock'];

			$sku 		= $product['ItemCode'];

			$price 		= $product['ItemPrices'][0]['Price'];

			$product_id = wc_get_product_id_by_sku($sku);

			if ($product_id) {
				
				$wc_product = wc_get_product($product_id);

				$wc_product->set_manage_stock(true);

				$wc_product->set_stock_quantity($quantity);

				$wc_product->set_price($price);

				$wc_product->save();

				$update_status['updated_products']++;

			} else {
				
				$update_status['failed_products']++;

			}
		}

		return $update_status;

	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function check_login_status () {

		$login_status = $this->sap_connector->check_login_status();

		wp_send_json_success( $login_status,200 );

		exit();

	}

	public function manual_login_to_sap () {

		$response= $this->sap_connector->login_to_sap();

		wp_send_json_success( $response,200 );

		exit();

	}

	public function check_php_compatibility () {

		if (version_compare(PHP_VERSION, '8.0.0', '<')) 
      	add_action('admin_notices', [$this, 'php_version_notice']);
			
    	
	}
	public function display_custom_order_meta_data ($order) {
	    

    	       // Get the order ID
        $order_id = $order->get_id();
    
        // Retrieve the custom meta data from the order using the meta key
        $custom_meta_data = get_post_meta($order_id, 'sap_order_number', true);
    
        // Check if the custom meta data exists before displaying it
        if (!empty($custom_meta_data)) {
            // Display the custom meta data in a table row
            echo '<p class="form-field form-field-wide"><strong>Sap Order Number:</strong> ' . esc_html($custom_meta_data) . '</p>';
        }
	}

	private function php_version_notice() {

  		$class = 'notice notice-error';

   	$message = __('Your PHP version is lower than 8.0.0. Please update PHP to version 8 or higher to use the My Plugin plugin.', 'my-plugin-textdomain');

   	printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));

	}
}
