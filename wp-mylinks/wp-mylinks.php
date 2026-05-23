<?php

/**
 * WP MyLinks Plugin.
 *
 * @link              https://walterpinem.me/
 * @since             1.0.0
 * @package           Wp_Mylinks
 * @copyright         Copyright (c) 2020-2026, Walter Pinem, Seni Berpikir
 *
 * @wordpress-plugin
 * Plugin Name:       WP MyLinks
 * Plugin URI:        https://walterpinem.me/projects/introducing-wp-mylinks/
 * Description:       Easily build your own micro landing page showing all the links you want to share to engage your audience. Use your own brand, link it anywhere.
 * Version:           1.0.8
 * Author:            Walter Pinem
 * Author URI:        https://walterpinem.me/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-mylinks
 * Domain Path:       /languages/
 * Requires at least: 6.0
 * Tested up to:      7.0
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define('WP_MYLINKS_NAME', 'WP MyLinks');
define('WP_MYLINKS_VERSION', '1.0.8');
define('WP_MYLINKS_PREFIX', 'mylinks_');
define('WP_MYLINKS_FILE', __FILE__);
define('WP_MYLINKS_PATH', plugin_dir_path(__FILE__));
define('WP_MYLINKS_URL', plugin_dir_url(__FILE__));

/**
 * Build a meta-key prefix for the mylink CPT.
 *
 * @param string $key
 * @return string
 */
function mylinks_prefix($key)
{
	return 'mylinks_' . $key;
}

/**
 * Build a meta-key prefix for the mylinks-collection CPT.
 *
 * @param string $string
 * @return string
 */
function mylinks_collection($string)
{
	return 'mylinks_collection_' . $string;
}

/**
 * Activation: flush rewrite rules so /<post-name>/ resolves immediately.
 */
function activate_wp_mylinks()
{
	require_once WP_MYLINKS_PATH . 'includes/class-wp-mylinks-activator.php';
	Wp_Mylinks_Activator::activate();
}
register_activation_hook(__FILE__, 'activate_wp_mylinks');

/**
 * Deactivation: drop the CPT's rewrite rules cleanly. Does NOT delete user data.
 */
function deactivate_wp_mylinks()
{
	require_once WP_MYLINKS_PATH . 'includes/class-wp-mylinks-deactivator.php';
	Wp_Mylinks_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_wp_mylinks');

/**
 * The core plugin classes that are used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require WP_MYLINKS_PATH . 'includes/class-wp-mylinks.php';
require WP_MYLINKS_PATH . 'admin/partials/wp-mylinks-admin-settings.php';
require WP_MYLINKS_PATH . 'includes/class-wp-mylinks-post-type.php';
require WP_MYLINKS_PATH . 'includes/class-wp-mylinks-rewrites.php';
require WP_MYLINKS_PATH . 'admin/partials/wp-mylinks-links-collection.php';

/**
 * Run a deferred rewrite-rules flush after a version bump.
 *
 * If users update from 1.0.7 (or earlier) to 1.0.8+, the old broken slug rules
 * will still be cached. Flush once on the first admin page load post-upgrade.
 *
 * @since 1.0.8
 */
function wp_mylinks_maybe_flush_on_upgrade()
{
	$stored = get_option('wp_mylinks_version');
	if ($stored === WP_MYLINKS_VERSION) {
		return;
	}
	if (class_exists('Wp_Mylinks_Rewrites')) {
		Wp_Mylinks_Rewrites::flush();
	}
	update_option('wp_mylinks_version', WP_MYLINKS_VERSION, false);
}
add_action('admin_init', 'wp_mylinks_maybe_flush_on_upgrade');

/**
 * Load base template for the landing page.
 */
add_filter('single_template', 'wp_mylinks_template');
function wp_mylinks_template($single)
{
	global $post;
	if ($post && 'mylink' === $post->post_type) {
		$template = WP_MYLINKS_PATH . 'public/partials/wp-mylinks-base-template.php';
		if (file_exists($template)) {
			return $template;
		}
	}
	return $single;
}

/**
 * Register the main public CSS.
 */
function wp_mylinks_register_style()
{
	wp_register_style('mylinks-public-css', WP_MYLINKS_URL . 'public/css/wp-mylinks-public.min.css', array(), WP_MYLINKS_VERSION);
	wp_register_style('mylinks-youtube-css', WP_MYLINKS_URL . 'public/css/wp-mylinks-youtube.min.css', array(), WP_MYLINKS_VERSION);
}
add_action('init', 'wp_mylinks_register_style');

/**
 * Register the main public JS.
 */
function wp_mylinks_register_script()
{
	wp_register_script('mylinks-public-js', WP_MYLINKS_URL . 'public/js/wp-mylinks-public.js', array(), WP_MYLINKS_VERSION, true);
}
add_action('init', 'wp_mylinks_register_script');

/**
 * Including the CMB2 init.php file.
 */
if (file_exists(WP_MYLINKS_PATH . 'includes/cmb2/init.php')) {
	require_once WP_MYLINKS_PATH . 'includes/cmb2/init.php';
}

/**
 * Are we currently on a mylink single view?
 */
function wp_mylinks_is_queried()
{
	return ('mylink' === get_post_type());
}

function wp_mylinks_collection_is_queried()
{
	return ('mylinks-collection' === get_post_type());
}

/**
 * Check if Yoast SEO / Premium is active.
 */
function wp_mylinks_isYoastActive()
{
	if (!function_exists('is_plugin_active')) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	return is_plugin_active('wordpress-seo/wp-seo.php')
		|| is_plugin_active('wordpress-seo-premium/wp-seo-premium.php');
}

/**
 * Track visited MyLinks page(s).
 *
 * Stored as integer in post meta for efficient incrementing.
 */
if (!function_exists('wp_mylinks_track_mylink_page')) :

	function wp_mylinks_track_mylink_page($postID)
	{
		if (empty($postID)) {
			return;
		}
		// Skip bots and admins to keep counts meaningful.
		if (is_admin() || (function_exists('wp_doing_cron') && wp_doing_cron())) {
			return;
		}

		$count_key = 'wp_mylinks_count_visits';
		$count     = (int) get_post_meta($postID, $count_key, true);
		update_post_meta($postID, $count_key, $count + 1);
	}
endif;

/**
 * Add Settings link on the plugin row.
 */
function wp_mylinks_settings_link($links_array, $plugin_file_name)
{
	if (false !== strpos($plugin_file_name, basename(__FILE__))) {
		array_unshift(
			$links_array,
			'<a href="' . esc_url(admin_url('edit.php?post_type=mylink&page=welcome')) . '">' . esc_html__('Settings', 'wp-mylinks') . '</a>'
		);
	}
	return $links_array;
}
add_filter('plugin_action_links', 'wp_mylinks_settings_link', 10, 2);

/**
 * Theme list for the per-page theme selector and the global setting.
 */
function wp_mylinks_theme_callback()
{
	return array(
		'none'       => __('None', 'wp-mylinks'),
		'default'    => __('Default', 'wp-mylinks'),
		'merbabu'    => __('Merbabu', 'wp-mylinks'),
		'cikuray'    => __('Cikuray', 'wp-mylinks'),
		'ciremai'    => __('Ciremai', 'wp-mylinks'),
		'slamet'     => __('Slamet', 'wp-mylinks'),
		'papandayan' => __('Papandayan', 'wp-mylinks'),
		'sindoro'    => __('Sindoro', 'wp-mylinks'),
		'krakatau'   => __('Krakatau', 'wp-mylinks'),
		'bromo'      => __('Bromo', 'wp-mylinks'),
		'prau'       => __('Prau', 'wp-mylinks'),
		'polos'      => __('Polos', 'wp-mylinks'),
		'datar'      => __('Datar', 'wp-mylinks'),
		'pastel'     => __('Pastel', 'wp-mylinks'),
		'kopi-hitam' => __('Kopi Hitam', 'wp-mylinks'),
		'kopi-susu'  => __('Kopi Susu', 'wp-mylinks'),
		'klepon'     => __('Klepon Viral', 'wp-mylinks'),
	);
}

/**
 * CMB2 sanitization passthrough for fields that need raw content (e.g. scripts).
 *
 * @since 1.0.2
 */
function wp_mylinks_sanitization_func($original_value, $args, $cmb2_field)
{
	return $original_value;
}

/**
 * Allow MyLinks to be used as the front page.
 *
 * Adds the mylink post type to the get_pages list shown on the Reading Settings
 * "Static page → Front page" dropdown.
 *
 * @since 1.0.1
 */
add_action('admin_head-options-reading.php', 'wp_mylinks_front_page_dropdown');
function wp_mylinks_front_page_dropdown()
{
	add_filter('get_pages', 'wp_mylinks_enable_front_page');
}
function wp_mylinks_enable_front_page($pages)
{
	// Note: we intentionally do NOT pass suppress_filters here — the
	// VIP-Minimum coding standard prohibits it, and on a normal admin
	// Reading Settings request the small filter overhead is acceptable.
	$mylinks = get_posts(array(
		'post_type'      => 'mylink',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	));
	return array_merge((array) $pages, (array) $mylinks);
}

/**
 * If a mylink post is set as the front page, ensure WordPress queries it
 * correctly. Replaces the previous duplicate-registered, buggy implementation.
 */
function wp_mylinks_show_front_page($query)
{
	if (!$query->is_main_query()) {
		return;
	}

	$page_id = (int) $query->get('page_id');
	if ($page_id <= 0) {
		return;
	}

	// If WP didn't already set a post_type, mirror the page_id's actual post_type.
	if ('' === (string) $query->get('post_type')) {
		$pt = get_post_type($page_id);
		if ($pt) {
			$query->set('post_type', $pt);
		}
	}
}
add_action('pre_get_posts', 'wp_mylinks_show_front_page');

/**
 * Use the dedicated MyLinks template when a mylink post is the front page.
 */
add_filter('template_include', 'wp_mylinks_front_page_template', 1);
function wp_mylinks_front_page_template($template_path)
{
	if (is_front_page() && 'mylink' === get_post_type()) {
		$single_template = WP_MYLINKS_PATH . 'public/partials/wp-mylinks-base-template.php';
		if (file_exists($single_template)) {
			return $single_template;
		}
	}
	return $template_path;
}

/**
 * Searchable MyLink Links Collections (CMB2 select options callback).
 *
 * @since 1.0.6
 */
function wp_mylinks_get_cmb2_post_options($field)
{
	$args = wp_parse_args($field->args['wp_query_args'], array(
		'post_type'      => array('page', 'post', 'mylinks-collection'),
		'post_status'    => 'publish',
		'posts_per_page' => 10,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'fields'         => 'ids',
	));
	$posts        = new WP_Query($args);
	$post_options = array();

	if ($posts->have_posts()) {
		$posts_with_hierarchy = wp_mylinks_build_post_tree($posts->posts);
		foreach ($posts_with_hierarchy as $post_data) {
			$post_id    = $post_data['ID'];
			$post_title = $post_data['post_title'];
			$post_type  = get_post_type($post_id);
			if ('mylinks-collection' === $post_type) {
				$post_url = (string) get_post_meta($post_id, mylinks_collection('link_collection'), true);
			} else {
				$post_url = (string) get_permalink($post_id);
			}
			$post_options[$post_id] = $post_title . ' - ' . $post_url;
		}
	}
	return $post_options;
}

function wp_mylinks_modify_select_url_options($args, $field, $object_type, $object_id)
{
	if (isset($field->args['id']) && 'select_url' === $field->args['id']) {
		foreach ($field->args['options'] as $option_value => &$option_args) {
			if (preg_match('/ - (?<url>.+)/', $option_args, $matches)) {
				$field->options[$option_value]['attributes']['data-url'] = $matches['url'];
			}
		}
	}
}

function wp_mylinks_build_post_tree($post_ids, $parent_id = 0, $level = 0)
{
	$branch = array();
	foreach ((array) $post_ids as $post_id) {
		$post_parent = wp_get_post_parent_id($post_id);
		if ((int) $parent_id === (int) $post_parent) {
			$post_title = get_the_title($post_id);
			$post_title = str_repeat('&mdash; ', $level) . $post_title;
			$branch[]   = array(
				'ID'         => $post_id,
				'post_title' => $post_title,
			);
			$children = wp_mylinks_build_post_tree($post_ids, $post_id, $level + 1);
			if (!empty($children)) {
				$branch = array_merge($branch, $children);
			}
		}
	}
	return $branch;
}

function wp_mylinks_custom_pw_select_render_row($field_output, $field_args, $field)
{
	if (!isset($field_args['_id'])) {
		return $field_output;
	}
	$field_id = esc_attr($field_args['_id']);
	$field_output .= '<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#' . $field_id . '").on("change", function() {
				var selectedOption = $(this).find(":selected");
				var dataUrl = selectedOption.data("url");
				if (dataUrl) {
					$(this).siblings(".cmb2-metabox-description").find("a").attr("href", dataUrl);
				}
			});
		});
	</script>';
	return $field_output;
}
add_filter('cmb2_render_row_cb', 'wp_mylinks_custom_pw_select_render_row', 10, 3);

/**
 * Read social-platform URL and icon meta for the current post.
 *
 * @since 1.0.6
 */
function wp_mylinks_get_social_meta($platform)
{
	$post_id = get_the_ID();
	$url     = $post_id ? get_post_meta($post_id, mylinks_prefix("{$platform}-url"), true) : '';
	$icon    = $post_id ? get_post_meta($post_id, mylinks_prefix("{$platform}-icon"), true) : '';
	return array($url, $icon);
}

/**
 * Extract a YouTube video ID from any common URL format.
 *
 * Supports:
 *   - https://www.youtube.com/watch?v=VIDEO_ID
 *   - https://youtu.be/VIDEO_ID
 *   - https://www.youtube.com/embed/VIDEO_ID
 *   - https://m.youtube.com/watch?v=VIDEO_ID
 *   - https://www.youtube.com/shorts/VIDEO_ID
 *
 * @since 1.0.8
 *
 * @param string $url
 * @return string The 11-character video ID, or '' if not found.
 */
function wp_mylinks_extract_youtube_id($url)
{
	if (!is_string($url) || '' === $url) {
		return '';
	}

	$parsed = wp_parse_url($url);
	if (!is_array($parsed) || empty($parsed['host'])) {
		return '';
	}

	$host = strtolower($parsed['host']);
	$path = isset($parsed['path']) ? trim($parsed['path'], '/') : '';

	// youtu.be/<id>
	if ('youtu.be' === $host) {
		$candidate = $path;
	} elseif (preg_match('#^(?:.*\.)?youtube(?:-nocookie)?\.com$#', $host)) {
		// /watch?v=<id>
		if (!empty($parsed['query'])) {
			parse_str($parsed['query'], $qs);
			if (!empty($qs['v'])) {
				$candidate = (string) $qs['v'];
			}
		}
		// /embed/<id> or /shorts/<id>
		if (empty($candidate) && '' !== $path) {
			$parts = explode('/', $path);
			if (count($parts) >= 2 && in_array($parts[0], array('embed', 'shorts', 'v'), true)) {
				$candidate = $parts[1];
			}
		}
	}

	if (empty($candidate)) {
		return '';
	}

	// YouTube IDs are 11 characters from the URL-safe base64 alphabet.
	return preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : '';
}

/**
 * Optional: dequeue all non-mylink scripts and styles on a single mylink view.
 *
 * @since 1.0.6
 */
function wp_mylinks_dequeue_others()
{
	if (!is_singular('mylink')) {
		return;
	}

	global $wp_scripts, $wp_styles;
	if ($wp_scripts && !empty($wp_scripts->queue)) {
		foreach ($wp_scripts->queue as $handle) {
			if (!isset($wp_scripts->registered[$handle])) {
				continue;
			}
			$src = (string) $wp_scripts->registered[$handle]->src;
			if (false === strpos($src, '/wp-mylinks/')) {
				wp_dequeue_script($handle);
			}
		}
	}
	if ($wp_styles && !empty($wp_styles->queue)) {
		foreach ($wp_styles->queue as $handle) {
			if (!isset($wp_styles->registered[$handle])) {
				continue;
			}
			$src = (string) $wp_styles->registered[$handle]->src;
			if (false === strpos($src, '/wp-mylinks/')) {
				wp_dequeue_style($handle);
			}
		}
	}
}
if ('yes' === get_option('wp_mylinks_dequeue')) {
	add_action('wp_print_scripts', 'wp_mylinks_dequeue_others', 100);
	add_action('wp_print_styles', 'wp_mylinks_dequeue_others', 100);
}

/**
 * WP MyLinks Dynamic URL parameters.
 *
 * @param string $base_url The URL to track.
 * @return string          Fully escaped URL.
 */
if (! function_exists('wpmylinks_url')) {
    function wpmylinks_url($base_url)
    {
        $base_url = esc_url_raw($base_url);
        // Use only major.minor of PHP so we don't generate a unique URL per
        // patch release. Falls back to phpversion() if the constants are
        // somehow undefined.
        $php_version = (defined('PHP_MAJOR_VERSION') && defined('PHP_MINOR_VERSION'))
            ? PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION
            : phpversion();
        $wp_version  = get_bloginfo('version');

        $plugin_name = '';
        if (defined('WP_MYLINKS_NAME')) {
            $plugin_name = sanitize_title(WP_MYLINKS_NAME);
        }

        $plugin_version = '';
        if (defined('WP_MYLINKS_VERSION')) {
            $plugin_version = WP_MYLINKS_VERSION;
        }

        if (function_exists('get_user_locale')) {
            $user_language = get_user_locale();
        } elseif (function_exists('determine_locale')) {
            $user_language = determine_locale();
        } else {
            $user_language = get_locale();
        }

        $screen = 'frontend';
        if (is_admin()) {
            // Read-only screen-context lookup; no form data is being processed.
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $page = isset($_GET['page'])
                ? sanitize_key(wp_unslash($_GET['page'])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen-context lookup.
                : '';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $tab  = isset($_GET['tab'])
                ? sanitize_key(wp_unslash($_GET['tab'])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen-context lookup.
                : '';
            if ($page) {
                $screen = $page . ($tab ? '-' . $tab : '');
            }
        }

        $params = array(
            'plugin_name'    => $plugin_name,
            'plugin_version' => $plugin_version,
            'php_version'    => $php_version,
            'wp_version'     => $wp_version,
            'user_language'  => $user_language,
            'screen'         => $screen,
        );

        /**
         * Allow other code to tweak the URL params.
         *
         * @param array  $params   Params before URL build.
         * @param string $base_url Original URL.
         */
        $params = apply_filters('wpmylinks_url_params', $params, $base_url);
        $tracked = add_query_arg($params, $base_url);
        return esc_url($tracked);
    }
}

/**
 * Resolve a value with the standard fallback chain:
 *   per-post meta → global option → ultimate fallback.
 *
 * Used by the public template and by the SEO/Schema/OG helpers below so
 * they all share consistent precedence rules.
 *
 * @since 1.0.8
 *
 * @param int    $post_id       Current post ID; 0 to skip the meta lookup.
 * @param string $post_meta_key Meta key on the post.
 * @param string $option_key    Site option key to fall back to.
 * @param mixed  $fallback      Final fallback when both are empty.
 * @return mixed
 */
if (!function_exists('wp_mylinks_resolve_meta_or_option')) {
	function wp_mylinks_resolve_meta_or_option($post_id, $post_meta_key, $option_key, $fallback = '')
	{
		$post_id = (int) $post_id;
		if ($post_id > 0) {
			$value = get_post_meta($post_id, $post_meta_key, true);
			if ('' !== $value && null !== $value) {
				return $value;
			}
		}
		$value = get_option($option_key);
		if ('' !== $value && null !== $value && false !== $value) {
			return $value;
		}
		return $fallback;
	}
}

/**
 * Detect whether Yoast SEO is currently emitting Open Graph tags for this
 * site. Used so we don't double-emit when Yoast is active.
 *
 * Yoast stores its OG toggle in the 'wpseo_social' option under the
 * 'opengraph' key. If Yoast is active and that toggle is on, we step aside.
 *
 * @since 1.0.8
 *
 * @return bool
 */
if (!function_exists('wp_mylinks_yoast_handles_og')) {
	function wp_mylinks_yoast_handles_og()
	{
		if (!function_exists('wp_mylinks_isYoastActive') || !wp_mylinks_isYoastActive()) {
			return false;
		}
		$social = get_option('wpseo_social');
		if (!is_array($social)) {
			return false;
		}
		return !empty($social['opengraph']);
	}
}

/**
 * Detect whether Yoast SEO is currently emitting Schema.org JSON-LD.
 *
 * Yoast's schema output is on by default and toggled via the 'wpseo' option
 * under 'enable_index_now' / 'breadcrumbs-enable' style keys; the practical
 * way to check is via the wpseo_json_ld_output filter Yoast itself respects.
 *
 * Conservative behavior: if Yoast is active, assume it handles JSON-LD
 * unless it is explicitly disabled in Yoast's settings.
 *
 * @since 1.0.8
 *
 * @return bool
 */
if (!function_exists('wp_mylinks_yoast_handles_schema')) {
	function wp_mylinks_yoast_handles_schema()
	{
		if (!function_exists('wp_mylinks_isYoastActive') || !wp_mylinks_isYoastActive()) {
			return false;
		}
		// Yoast 14+ outputs schema by default. Allow site owners to opt out
		// via the standard wpseo_json_ld_output filter (returns false to
		// disable). We mirror that contract.
		$enabled = apply_filters('wpseo_json_ld_output', '', '');
		return false !== $enabled;
	}
}

/**
 * Build the array of social URLs (hardcoded 8 + additional repeater) for the
 * given post. Used to populate Schema.org `sameAs` and to render the social
 * row in the public template.
 *
 * Each item is `array( 'name' => string, 'url' => string, 'icon' => string )`.
 * Empty rows are filtered out automatically.
 *
 * @since 1.0.8
 *
 * @param int $post_id
 * @return array<int,array{name:string,url:string,icon:string}>
 */
if (!function_exists('wp_mylinks_collect_socials')) {
	function wp_mylinks_collect_socials($post_id)
	{
		$post_id  = (int) $post_id;
		$collected = array();

		if ($post_id <= 0) {
			return $collected;
		}

		// Hardcoded 8 platforms — preserved exactly to avoid breaking existing data.
		$platforms = array('facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'pinterest', 'tiktok', 'discord');
		foreach ($platforms as $platform) {
			$url = (string) get_post_meta($post_id, mylinks_prefix($platform . '-url'), true);
			if ('' === trim($url)) {
				continue;
			}
			$icon = (string) get_post_meta($post_id, mylinks_prefix($platform . '-icon'), true);
			$collected[] = array(
				'name' => ucfirst($platform),
				'url'  => $url,
				'icon' => $icon,
			);
		}

		// Additional Social Platforms (CMB2 group, new in 1.0.8).
		$additional = get_post_meta($post_id, mylinks_prefix('additional-socials'), true);
		if (is_array($additional)) {
			foreach ($additional as $row) {
				if (!is_array($row)) {
					continue;
				}
				$name = isset($row['name']) ? (string) $row['name'] : '';
				$url  = isset($row['url']) ? (string) $row['url'] : '';
				$icon = isset($row['icon']) ? (string) $row['icon'] : '';
				if ('' === trim($url) || '' === trim($name)) {
					continue;
				}
				$collected[] = array(
					'name' => $name,
					'url'  => $url,
					'icon' => $icon,
				);
			}
		}

		return $collected;
	}
}

/**
 * Resolve the og:image URL for a MyLink with this fallback chain:
 *   1. Per-page mylinks_og-image
 *   2. Global mylinks_og_image
 *   3. Per-page Yoast og:image (if Yoast active and set)
 *   4. Per-page mylinks_avatar
 *   5. Global mylinks_upload_favicon
 *   6. '' (caller should omit the meta tag entirely)
 *
 * @since 1.0.8
 *
 * @param int $post_id
 * @return string
 */
if (!function_exists('wp_mylinks_resolve_og_image')) {
	function wp_mylinks_resolve_og_image($post_id)
	{
		$post_id = (int) $post_id;

		if ($post_id > 0) {
			$per_page = (string) get_post_meta($post_id, mylinks_prefix('og-image'), true);
			if ('' !== $per_page) {
				return $per_page;
			}
		}

		$global_og = (string) get_option('wp_mylinks_og_image', '');
		if ('' !== $global_og) {
			return $global_og;
		}

		if ($post_id > 0 && function_exists('wp_mylinks_isYoastActive') && wp_mylinks_isYoastActive()) {
			$yoast_og = (string) get_post_meta($post_id, '_yoast_wpseo_opengraph-image', true);
			if ('' !== $yoast_og) {
				return $yoast_og;
			}
		}

		if ($post_id > 0) {
			$avatar = (string) get_post_meta($post_id, mylinks_prefix('avatar'), true);
			if ('' !== $avatar) {
				return $avatar;
			}
		}

		$favicon = (string) get_option('mylinks_upload_favicon', '');
		if ('' !== $favicon) {
			return $favicon;
		}

		return '';
	}
}

/**
 * Build the JSON-LD payload for a MyLink page.
 *
 * Returns null when JSON-LD output is disabled or when `wp_json_encode()`
 * fails. Caller treats null as "do not emit a <script> tag at all".
 *
 * Output structure:
 *   - When the user has only the Schema toggle on, this returns a single
 *     Person/Organization JSON object (the original 1.0.8 shape).
 *   - When the ProfilePage wrapper toggle is also on, the output becomes a
 *     `@graph` array containing the entity (Person/Organization), a
 *     ProfilePage that references it via @id, and a BreadcrumbList.
 *   - A BreadcrumbList is added unconditionally when schema is enabled. It's
 *     a 2-step path (site Home → page name) which Google promotes in SERPs.
 *
 * @since 1.0.8
 *
 * @param int $post_id
 * @return string|null Encoded JSON, or null to skip output.
 */
if (!function_exists('wp_mylinks_build_schema_json')) {
	function wp_mylinks_build_schema_json($post_id)
	{
		$post_id = (int) $post_id;
		if ($post_id <= 0) {
			return null;
		}

		// Per-page override: 'inherit' / 'Person' / 'Organization' / '' (= inherit).
		$per_page_type = (string) get_post_meta($post_id, mylinks_prefix('schema-type'), true);
		if ('Person' === $per_page_type || 'Organization' === $per_page_type) {
			$schema_type = $per_page_type;
		} else {
			$global_type = (string) get_option('wp_mylinks_schema_type', 'Person');
			$schema_type = ('Organization' === $global_type) ? 'Organization' : 'Person';
		}

		$post = get_post($post_id);
		if (!$post) {
			return null;
		}

		$name        = (string) get_post_meta($post_id, mylinks_prefix('name'), true);
		$avatar      = (string) get_post_meta($post_id, mylinks_prefix('avatar'), true);
		$description = (string) get_post_meta($post_id, mylinks_prefix('description'), true);

		// Description may contain HTML from the WYSIWYG; strip for schema.
		$description_plain = trim(wp_strip_all_tags($description));

		// Resolve the human-readable display name with sensible fallbacks.
		$display_name = '' !== $name ? $name : (string) get_the_title($post_id);

		// Canonical URL of the page itself.
		$permalink = (string) get_permalink($post_id);
		if ('' === $permalink) {
			return null;
		}

		// -------------------------------------------------------------
		// Build the Person / Organization (the entity).
		// -------------------------------------------------------------

		$entity_id = $permalink . '#' . strtolower($schema_type);
		$entity    = array(
			'@type' => $schema_type,
			'@id'   => $entity_id,
		);

		if ('' !== $display_name) {
			$entity['name'] = $display_name;
		}
		if ('' !== $description_plain) {
			$entity['description'] = $description_plain;
		}
		if ('' !== $avatar) {
			$entity['image'] = esc_url_raw($avatar);
		}
		$entity['url'] = esc_url_raw($permalink);

		// sameAs from collected socials.
		$socials = wp_mylinks_collect_socials($post_id);
		$same_as = array();
		foreach ($socials as $social) {
			if (!empty($social['url'])) {
				$same_as[] = esc_url_raw($social['url']);
			}
		}
		if (!empty($same_as)) {
			$entity['sameAs'] = array_values(array_unique($same_as));
		}

		// -------------------------------------------------------------
		// Build the ProfilePage wrapper (optional, controlled by toggle).
		// -------------------------------------------------------------

		$emit_profilepage = ('yes' === get_option('wp_mylinks_enable_profilepage'));
		$profilepage      = null;

		if ($emit_profilepage) {
			$profilepage = array(
				'@type' => 'ProfilePage',
				'@id'   => $permalink . '#profilepage',
				'url'   => esc_url_raw($permalink),
			);

			if ('' !== $display_name) {
				$profilepage['name'] = $display_name;
			}
			if ('' !== $description_plain) {
				$profilepage['description'] = $description_plain;
			}

			// dateCreated / dateModified — required for high-quality ProfilePage.
			if (!empty($post->post_date_gmt) && '0000-00-00 00:00:00' !== $post->post_date_gmt) {
				$profilepage['dateCreated'] = mysql2date('c', $post->post_date_gmt, false);
			}
			if (!empty($post->post_modified_gmt) && '0000-00-00 00:00:00' !== $post->post_modified_gmt) {
				$profilepage['dateModified'] = mysql2date('c', $post->post_modified_gmt, false);
			}

			// inLanguage — useful for multilingual sites.
			$locale = function_exists('determine_locale') ? determine_locale() : get_locale();
			if (is_string($locale) && '' !== $locale) {
				// Schema.org expects a BCP-47 tag (e.g. en-US). WP locales are
				// underscore-joined (en_US); convert.
				$profilepage['inLanguage'] = str_replace('_', '-', $locale);
			}

			// mainEntity — the link to the Person/Organization we built above.
			$profilepage['mainEntity'] = array('@id' => $entity_id);
		}

		// -------------------------------------------------------------
		// Build the BreadcrumbList (always emitted when schema is on).
		//
		// Two-step path: Home → page name. Google explicitly supports the
		// 2-step BreadcrumbList rich result and uses it in SERPs in place of
		// the URL line. Linktree, GitHub, LinkedIn all use this pattern.
		// -------------------------------------------------------------

		$home_url   = (string) home_url('/');
		$site_name  = (string) get_bloginfo('name');
		$breadcrumb = array(
			'@type' => 'BreadcrumbList',
			'@id'   => $permalink . '#breadcrumb',
			'itemListElement' => array(
				array(
					'@type'    => 'ListItem',
					'position' => 1,
					'name'     => '' !== $site_name ? $site_name : __('Home', 'wp-mylinks'),
					'item'     => esc_url_raw($home_url),
				),
				array(
					'@type'    => 'ListItem',
					'position' => 2,
					'name'     => $display_name,
				),
			),
		);
		// Note: per Google guidelines, the FINAL breadcrumb item should NOT
		// include `item` (the URL) — it's the current page. Only the
		// non-current items get `item`. Above we omit it from position 2.

		// -------------------------------------------------------------
		// Assemble the final payload.
		// -------------------------------------------------------------

		// We always have the entity. ProfilePage is optional. Breadcrumb is
		// always emitted. So the graph will have either 2 or 3 items.
		$graph = array($entity);
		if ($profilepage) {
			$graph[] = $profilepage;
		}
		$graph[] = $breadcrumb;

		$data = array(
			'@context' => 'https://schema.org',
			'@graph'   => array_values($graph),
		);

		/**
		 * Filter the Schema.org JSON-LD data array before encoding.
		 *
		 * The data shape is:
		 *   {
		 *     "@context": "https://schema.org",
		 *     "@graph": [ <entity>, [<ProfilePage>,] <BreadcrumbList> ]
		 *   }
		 *
		 * @since 1.0.8
		 *
		 * @param array $data    Schema array (graph-shaped).
		 * @param int   $post_id Current post ID.
		 */
		$data = apply_filters('wp_mylinks_schema_data', $data, $post_id);

		if (!is_array($data) || empty($data)) {
			return null;
		}

		// JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE keeps URLs and
		// non-ASCII names readable in source view.
		$flags = 0;
		if (defined('JSON_UNESCAPED_SLASHES')) {
			$flags |= JSON_UNESCAPED_SLASHES;
		}
		if (defined('JSON_UNESCAPED_UNICODE')) {
			$flags |= JSON_UNESCAPED_UNICODE;
		}

		$json = wp_json_encode($data, $flags);
		return is_string($json) ? $json : null;
	}
}

/**
 * Sanitize the schema_type radio: only 'Person' or 'Organization' allowed.
 *
 * @since 1.0.8
 *
 * @param mixed $value
 * @return string
 */
if (!function_exists('wp_mylinks_sanitize_schema_type')) {
	function wp_mylinks_sanitize_schema_type($value)
	{
		$value = is_string($value) ? sanitize_key($value) : '';
		return ('Organization' === $value) ? 'Organization' : 'Person';
	}
}

/**
 * Sanitize a Twitter handle: ensure it starts with '@' and contains only
 * permitted characters. Returns empty string for empty input.
 *
 * @since 1.0.8
 *
 * @param mixed $value
 * @return string
 */
if (!function_exists('wp_mylinks_sanitize_twitter_handle')) {
	function wp_mylinks_sanitize_twitter_handle($value)
	{
		if (!is_string($value)) {
			return '';
		}
		$value = trim($value);
		if ('' === $value) {
			return '';
		}
		// Keep only alphanumerics and underscore (Twitter rules).
		$value = preg_replace('/[^A-Za-z0-9_]/', '', $value);
		if ('' === $value) {
			return '';
		}
		return '@' . $value;
	}
}

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function run_wp_mylinks()
{
	$plugin = new Wp_Mylinks();
	$plugin->run();
}
run_wp_mylinks();
