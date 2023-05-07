<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://site-pro.co.il
 * @since             1.0.0
 * @package           Epsilon_Sap_Sync
 *
 * @wordpress-plugin
 * Plugin Name:       Epsilon SAP Sync
 * Plugin URI:        https://site-pro.co.il
 * Description:       A custom plugin for SAP <- -> Site syncing
 * Version:           1.0.0
 * Author:            DoroNess
 * Author URI:        https://site-pro.co.il
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       epsilon-sap-sync
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'EPSILON_SAP_SYNC_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-epsilon-sap-sync-activator.php
 */
function activate_epsilon_sap_sync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-epsilon-sap-sync-activator.php';
	Epsilon_Sap_Sync_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-epsilon-sap-sync-deactivator.php
 */
function deactivate_epsilon_sap_sync() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-epsilon-sap-sync-deactivator.php';
	Epsilon_Sap_Sync_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_epsilon_sap_sync' );

register_deactivation_hook( __FILE__, 'deactivate_epsilon_sap_sync' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-epsilon-sap-sync.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_epsilon_sap_sync() {

	//TODO: Check if woo is active

	//if woocommerce is not active, notify the user and deactivate the plugin

	$plugin = new Epsilon_Sap_Sync();

	$plugin->run();

}

run_epsilon_sap_sync();
