<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://site-pro.co.il
 * @since      1.0.0
 *
 * @package    Epsilon_Sap_Sync
 * @subpackage Epsilon_Sap_Sync/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Epsilon_Sap_Sync
 * @subpackage Epsilon_Sap_Sync/includes
 * @author     DoroNess <support@sitepro.co.il>
 */


class Epsilon_Sap_Sync {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Epsilon_Sap_Sync_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'EPSILON_SAP_SYNC_VERSION' ) ) {
			$this->version = EPSILON_SAP_SYNC_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'epsilon-sap-sync';

		$this->load_dependencies();

		$this->set_locale();

		$this->define_admin_hooks();
		
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Epsilon_Sap_Sync_Loader. Orchestrates the hooks of the plugin.
	 * - Epsilon_Sap_Sync_i18n. Defines internationalization functionality.
	 * - Epsilon_Sap_Sync_Admin. Defines all hooks for the admin area.
	 * - Epsilon_Sap_Sync_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-epsilon-sap-sync-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-epsilon-sap-sync-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-epsilon-sap-sync-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-epsilon-sap-sync-public.php';
		
		/**
		 * 
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-sap-connector.php';


		$this->loader = new Epsilon_Sap_Sync_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Epsilon_Sap_Sync_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Epsilon_Sap_Sync_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Epsilon_Sap_Sync_Admin( $this->get_plugin_name(), $this->get_version() );

		//load admin scripts only on plugin page 

		if(isset($_GET['page']) && $_GET['page'] == 'epsilon-sap-sync') {

			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );

			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		}

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

		$this->loader->add_action( 'admin_init', $plugin_admin, 'options_update' );

	  	$this->loader->add_action( 'wp_ajax_sync_sap_full', $plugin_admin , 'sync_invantory' );

		$this->loader->add_action( 'wp_ajax_nopriv_sync_sap_full', $plugin_admin , 'sync_invantory' );

		$this->loader->add_action( 'wp_ajax_sync_sap_full_in_chunks', $plugin_admin , 'sync_invantory_in_chunks' );

		$this->loader->add_action( 'wp_ajax_nopriv_sync_sap_full_in_chunks', $plugin_admin , 'sync_invantory_in_chunks' );

		$this->loader->add_action( 'wp_ajax_check_login', $plugin_admin , 'check_login_status' );

		$this->loader->add_action( 'wp_ajax_manual_login_to_sap', $plugin_admin , 'manual_login_to_sap' );

		$this->loader->add_action( 'admin_init', $plugin_admin , 'check_php_compatibility' );
		
		$this->loader->add_action( 'woocommerce_admin_order_data_after_order_details', $plugin_admin , 'display_custom_order_meta_data' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Epsilon_Sap_Sync_Public( $this->get_plugin_name(), $this->get_version() );

		//run on order woocommerce loaded

		$this->loader->add_action( 'woocommerce_loaded', $plugin_public, 'disable_guest_checkout' );

		$this->loader->add_action( 'woocommerce_thankyou', $plugin_public, 'send_order_for_sap' );

		$this->loader->add_action( 'wp', $plugin_public, 'schedule_inventory_update' );

		$this->loader->add_action( 'cron_update_product_inventory', $plugin_public, 'update_product_inventory' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Epsilon_Sap_Sync_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
