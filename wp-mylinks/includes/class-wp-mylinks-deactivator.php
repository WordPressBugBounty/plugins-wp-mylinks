<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://walterpinem.me/
 * @since      1.0.0
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Fired during plugin deactivation.
 *
 * Important: deactivation is NOT uninstall. We must not delete user data here —
 * a user who deactivates the plugin temporarily (e.g. while debugging another
 * plugin) should not lose their settings.
 *
 * The previous version called delete_option() on every plugin option here,
 * which permanently destroyed user configuration on every deactivation. That
 * has been removed.
 *
 * If true uninstall behavior is needed, add an uninstall.php file at the
 * plugin root.
 *
 * @since 1.0.0
 */
class Wp_Mylinks_Deactivator {


	/**
	 * Deactivation routine.
	 *
	 * Only flushes rewrite rules so the /<post-name>/ rules registered by the
	 * mylink CPT are removed cleanly.
	 *
	 * @since 1.0.5
	 */
	public static function deactivate() {
		// Unregister the post type so its rewrite rules are dropped.
		if ( post_type_exists('mylink') ) {
			unregister_post_type('mylink');
		}
		if ( post_type_exists('mylinks-collection') ) {
			unregister_post_type('mylinks-collection');
		}

		// Flush rewrite rules so leftover /<post-name>/ rules don't linger.
		flush_rewrite_rules(false);
	}
}
