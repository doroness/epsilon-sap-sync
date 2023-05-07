<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://site-pro.co.il
 * @since      1.0.0
 *
 * @package    Epsilon_Sap_Sync
 * @subpackage Epsilon_Sap_Sync/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Epsilon_Sap_Sync
 * @subpackage Epsilon_Sap_Sync/includes
 * @author     DoroNess <support@sitepro.co.il>
 */
class Epsilon_Sap_Sync_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'epsilon-sap-sync',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
