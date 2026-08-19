<?php

/**
 * Uninstall handler for WP MyLinks.
 *
 * Fired by WordPress when a user clicks "Delete" on the Plugins screen.
 * Deactivation must NOT delete user data — only true uninstall does.
 *
 * Behavior is governed by the "Delete Plugin Data on Uninstall?" setting on
 * Settings → MyLinks → Global:
 *   - When unchecked (default): nothing is deleted. Reinstalling restores
 *     the user's previous configuration intact.
 *   - When checked: the plugin's wp_options entries and oEmbed transients
 *     are removed. MyLink and Collection posts are preserved unless the
 *     WP_MYLINKS_PURGE_POSTS_ON_UNINSTALL constant is also defined and true.
 *
 * @package    Wp_Mylinks
 * @since      1.0.8
 * @link       https://walterpinem.me/
 */

// Exit if WordPress did not invoke this script as an uninstall.
if ( ! defined('WP_UNINSTALL_PLUGIN') ) {
	exit;
}

// --- Honor the user's "Delete Plugin Data on Uninstall?" setting -------------

$delete_data = (string) get_option('wp_mylinks_delete_data_on_uninstall');

if ( 'yes' !== $delete_data ) {
	// User has not opted in to data deletion. Leave everything in place so
	// reinstalling the plugin restores their settings cleanly.
	return;
}

// --- Delete plugin options ---------------------------------------------------

$options_to_delete = array(
	'mylinks_theme',
	'mylinks_meta_title',
	'mylinks_meta_description',
	'mylinks_upload_favicon',
	'wp_mylinks_nofollow',
	'wp_mylinks_noindex',
	'wp_mylinks_credits',
	'wp_mylinks_hide_notice',
	'wp_mylinks_analytics',
	'wp_mylinks_header_script',
	'wp_mylinks_open_body_script',
	'wp_mylinks_footer_script',
	'wp_mylinks_custom_css',
	'wp_mylinks_dequeue',
	'wp_mylinks_delete_data_on_uninstall',
	// 1.0.8 additions.
	'wp_mylinks_enable_schema',
	'wp_mylinks_schema_type',
	'wp_mylinks_enable_profilepage',
	'wp_mylinks_enable_og',
	'wp_mylinks_og_image',
	'wp_mylinks_twitter_handle',
	'wp_mylinks_installed_at',
	'wp_mylinks_version',
	// 1.1.0 additions (accent colors).
	'wp_mylinks_accent_bg',
	'wp_mylinks_accent_button_bg',
	'wp_mylinks_accent_button_text',
	'wp_mylinks_accent_text',
	// 1.1.0 additions (font family, Link URL sources).
	'wp_mylinks_font_family',
	'wp_mylinks_link_post_types',
);
foreach ( $options_to_delete as $opt ) {
	delete_option($opt);
	// In multisite, also clean network options where applicable.
	if ( is_multisite() ) {
		delete_site_option($opt);
	}
}

// --- Delete oEmbed transients ------------------------------------------------
//
// The plugin caches oEmbed responses using set_transient('wp_mylinks_oembed_*').
// Clean those up too.

global $wpdb;
// Direct DELETE on the options table is the only practical way to clean up
// transients matching a wildcard. delete_transient() requires knowing the
// exact key, which we don't here. This is a one-shot cleanup that runs once
// when the plugin is being removed, so caching is irrelevant.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Wildcard transient cleanup on uninstall; one-shot, no Core API alternative.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like('_transient_wp_mylinks_oembed_') . '%',
		$wpdb->esc_like('_transient_timeout_wp_mylinks_oembed_') . '%'
	)
);

// --- Optional: purge mylink posts (off by default) --------------------------
//
// Setting WP_MYLINKS_PURGE_POSTS_ON_UNINSTALL=true in wp-config.php will fully
// delete all mylink and mylinks-collection posts. Default behavior leaves them
// in place so users can recover them by reinstalling the plugin.

if ( defined('WP_MYLINKS_PURGE_POSTS_ON_UNINSTALL') && WP_MYLINKS_PURGE_POSTS_ON_UNINSTALL ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk post-ID enumeration on uninstall; one-shot.
	$post_ids = $wpdb->get_col(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('mylink', 'mylinks-collection')"
	);
	foreach ( (array) $post_ids as $mylinks_post_id ) {
		wp_delete_post( (int) $mylinks_post_id, true);
	}
}

// --- Final: flush rewrites for any remaining sites --------------------------
flush_rewrite_rules(false);
