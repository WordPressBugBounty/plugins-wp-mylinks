<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://walterpinem.me/
 * @since      1.0.0
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes
 * @author     Walter Pinem <hello@walterpinem.me>
 */
class Wp_Mylinks_I18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * Note: WordPress 4.6+ on WordPress.org loads translations automatically
	 * from translate.wordpress.org. We still call load_plugin_textdomain()
	 * here so that:
	 *   1. Self-hosted /wp-content/languages/wp-mylinks/ files are picked up,
	 *   2. Sites that bundle a custom .mo inside the plugin's /languages/
	 *      folder continue to work.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {

		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Kept for self-hosted translation files; WP.org auto-loader still works alongside this call.
		load_plugin_textdomain(
			'wp-mylinks',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
