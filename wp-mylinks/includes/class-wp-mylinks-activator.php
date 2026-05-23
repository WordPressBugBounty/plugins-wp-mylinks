<?php

/**
 * Fired during plugin activation
 *
 * @link       https://walterpinem.me/
 * @since      1.0.0
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * Registers the post type and flushes rewrite rules so that pretty permalinks
 * resolve correctly without the user having to manually visit Settings → Permalinks.
 *
 * @since 1.0.0
 */
class Wp_Mylinks_Activator
{

	/**
	 * Activation routine.
	 *
	 * @since 1.0.5
	 */
	public static function activate()
	{
		// The post-type and rewrites files are required by the main plugin file
		// before this hook fires, but be defensive in case activation order changes.
		if (!function_exists('wp_mylinks_register_post_type')) {
			require_once plugin_dir_path(__FILE__) . 'class-wp-mylinks-post-type.php';
		}
		if (!class_exists('Wp_Mylinks_Rewrites')) {
			require_once plugin_dir_path(__FILE__) . 'class-wp-mylinks-rewrites.php';
		}

		// Register the CPT and flush rules.
		Wp_Mylinks_Rewrites::flush();

		// Mark activation timestamp; useful for migrations and admin notices.
		if (!get_option('wp_mylinks_installed_at')) {
			update_option('wp_mylinks_installed_at', time(), false);
		}
		update_option('wp_mylinks_version', WP_MYLINKS_VERSION, false);
	}
}
