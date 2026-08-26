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
 * Plugin URI:        https://www.onlinestorekit.com/wp-mylinks/
 * Description:       Easily build your own micro landing page showing all the links you want to share to engage your audience. Use your own brand, link it anywhere.
 * Version:           1.1.1
 * Author:            Walter Pinem
 * Author URI:        https://walterpinem.me/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-mylinks
 * Domain Path:       /languages/
 * Requires at least: 6.0
 * Tested up to:      7.1
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if ( ! defined('WPINC') ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define('WP_MYLINKS_NAME', 'WP MyLinks');
define('WP_MYLINKS_VERSION', '1.1.1');
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
function mylinks_prefix( $key ) {
	return 'mylinks_' . $key;
}

/**
 * Build a meta-key prefix for the mylinks-collection CPT.
 *
 * @param string $key
 * @return string
 */
function mylinks_collection( $key ) {
	return 'mylinks_collection_' . $key;
}

/**
 * Activation: flush rewrite rules so /<post-name>/ resolves immediately.
 */
function activate_wp_mylinks() {
	require_once WP_MYLINKS_PATH . 'includes/class-wp-mylinks-activator.php';
	Wp_Mylinks_Activator::activate();
}
register_activation_hook(__FILE__, 'activate_wp_mylinks');

/**
 * Deactivation: drop the CPT's rewrite rules cleanly. Does NOT delete user data.
 */
function deactivate_wp_mylinks() {
	require_once WP_MYLINKS_PATH . 'includes/class-wp-mylinks-deactivator.php';
	Wp_Mylinks_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_wp_mylinks');

/**
 * The core plugin classes that are used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require WP_MYLINKS_PATH . 'includes/class-wp-mylinks.php';
require WP_MYLINKS_PATH . 'includes/fields/load.php';
require WP_MYLINKS_PATH . 'admin/partials/wp-mylinks-admin-settings.php';
require WP_MYLINKS_PATH . 'includes/class-wp-mylinks-more-plugins.php';
require WP_MYLINKS_PATH . 'includes/class-wp-mylinks-tools.php';
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
function wp_mylinks_maybe_flush_on_upgrade() {
	$stored = get_option('wp_mylinks_version');
	if ( $stored === WP_MYLINKS_VERSION ) {
		return;
	}
	if ( class_exists('Wp_Mylinks_Rewrites') ) {
		Wp_Mylinks_Rewrites::flush();
	}
	update_option('wp_mylinks_version', WP_MYLINKS_VERSION, false);
}
add_action('admin_init', 'wp_mylinks_maybe_flush_on_upgrade');

/**
 * Load base template for the landing page.
 */
add_filter('single_template', 'wp_mylinks_template');
function wp_mylinks_template( $single ) {
	global $post;
	if ( $post && 'mylink' === $post->post_type ) {
		$template = WP_MYLINKS_PATH . 'public/partials/wp-mylinks-base-template.php';
		if ( file_exists($template) ) {
			return $template;
		}
	}
	return $single;
}

/**
 * Cache-busting version for a public asset: the file's mtime, so a changed
 * stylesheet or script is never served stale from cache under an unchanged
 * plugin version. Falls back to the plugin version if the file is unreadable.
 *
 * @since 1.1.0
 *
 * @param string $relative Path relative to the plugin root.
 * @return string
 */
function wp_mylinks_asset_ver( $relative ) {
	$mtime = @filemtime(WP_MYLINKS_PATH . $relative); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- version fallback below covers failure.
	return $mtime ? (string) $mtime : WP_MYLINKS_VERSION;
}

/**
 * Register the main public CSS.
 */
function wp_mylinks_register_style() {
	wp_register_style('mylinks-public-css', WP_MYLINKS_URL . 'public/css/wp-mylinks-public.min.css', array(), wp_mylinks_asset_ver('public/css/wp-mylinks-public.min.css'));
	wp_register_style('mylinks-youtube-css', WP_MYLINKS_URL . 'public/css/wp-mylinks-youtube.min.css', array(), wp_mylinks_asset_ver('public/css/wp-mylinks-youtube.min.css'));
}
add_action('init', 'wp_mylinks_register_style');

/**
 * Register the main public JS.
 */
function wp_mylinks_register_script() {
	wp_register_script('mylinks-public-js', WP_MYLINKS_URL . 'public/js/wp-mylinks-public.js', array(), wp_mylinks_asset_ver('public/js/wp-mylinks-public.js'), true);
	// Translatable accessibility labels for the lazy-loaded YouTube embed.
	wp_localize_script(
		'mylinks-public-js',
		'wpMylinksPublic',
		array(
			'youtubePlayer' => __('YouTube video player', 'wp-mylinks'),
			'playVideo'     => __('Play video', 'wp-mylinks'),
		)
	);
}
add_action('init', 'wp_mylinks_register_script');

/**
 * Metabox definitions (native fields framework — replaced bundled CMB2 in 1.1.0).
 */
require_once WP_MYLINKS_PATH . 'includes/class-wp-mylinks-metaboxes.php';

/**
 * Are we currently on a mylink single view?
 */
function wp_mylinks_is_queried() {
	return ( 'mylink' === get_post_type() );
}

function wp_mylinks_collection_is_queried() {
	return ( 'mylinks-collection' === get_post_type() );
}

/**
 * Check if Yoast SEO / Premium is active.
 */
function wp_mylinks_isYoastActive() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid -- Public API since 1.0.0; renaming would break function_exists() integrations.
	if ( ! function_exists('is_plugin_active') ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	return is_plugin_active('wordpress-seo/wp-seo.php')
		|| is_plugin_active('wordpress-seo-premium/wp-seo-premium.php');
}

/**
 * Is the current request from an obvious bot (or otherwise not worth
 * counting)? Shared by the page-visit and link-click trackers.
 *
 * A substring user-agent check, not a full bot database: it catches the
 * crawlers that actually hit bio pages, and stays intentionally lightweight.
 *
 * @since 1.1.0
 *
 * @return bool
 */
if ( ! function_exists('wp_mylinks_is_bot_request') ) {
	function wp_mylinks_is_bot_request() {
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower( (string) wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
		return '' === $ua || (bool) preg_match('/bot|crawl|spider|slurp|curl|wget|python-|headless|lighthouse|pingdom|monitor|preview|facebookexternalhit/', $ua);
	}
}

/**
 * Track visited MyLinks page(s).
 *
 * Increments with a single in-place UPDATE so concurrent hits don't lose
 * counts, and skips admin, cron, and obvious bots so the number stays
 * meaningful. Pro's analytics will read this same meta key.
 *
 * @since 1.1.0 Atomic increment + user-agent bot filter (was a lossy
 *              read-then-write that also counted crawlers).
 */
if ( ! function_exists('wp_mylinks_track_mylink_page') ) :

	function wp_mylinks_track_mylink_page( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return;
		}
		if ( is_admin() || ( function_exists('wp_doing_cron') && wp_doing_cron() ) ) {
			return;
		}
		if ( wp_mylinks_is_bot_request() ) {
			return;
		}

		$count_key = 'wp_mylinks_count_visits';

		// First visit: add_post_meta with $unique = true is race-safe — the
		// loser of a concurrent first insert falls through to the UPDATE.
		if ( '' === (string) get_post_meta($post_id, $count_key, true) ) {
			if ( add_post_meta($post_id, $count_key, 1, true) ) {
				return;
			}
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic increment; the meta cache is invalidated right after.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = meta_value + 1 WHERE post_id = %d AND meta_key = %s",
				$post_id,
				$count_key
			)
		);
		wp_cache_delete($post_id, 'post_meta');
	}
endif;

/**
 * The set of currently valid link URLs for a MyLink page, keyed by md5.
 *
 * Uses the same render-time Collection resolution as the template, so the
 * hashes match what visitors actually click.
 *
 * @since 1.1.0
 *
 * @param int $post_id Post ID.
 * @return array<string,string> md5( url ) => url.
 */
function wp_mylinks_valid_link_urls( $post_id ) {
	$post_id = (int) $post_id;
	$urls    = array();
	$links   = get_post_meta( $post_id, mylinks_prefix('links'), true );

	foreach ( (array) $links as $link ) {
		if ( ! is_array( $link ) ) {
			continue;
		}
		$url = isset( $link['url'] ) ? (string) $link['url'] : '';
		if ( isset( $link['select_url'] ) ) {
			$fresh = wp_mylinks_resolve_selected_url( $link['select_url'] );
			if ( '' !== $fresh ) {
				$url = $fresh;
			}
		}
		if ( '' !== $url ) {
			// Accept every serialization the click beacon might report for this
			// href: the raw stored value, its esc_url_raw() encoding (what the
			// href actually renders as), and the browser's bare-authority "/"
			// normalization of that. Without the last, a path-less URL such as
			// "https://example.com" never matches the browser's "…/.com/".
			foreach ( array( $url, esc_url_raw( $url ), wp_mylinks_browser_normalize_url( esc_url_raw( $url ) ) ) as $variant ) {
				if ( '' !== (string) $variant ) {
					$urls[ md5( $variant ) ] = $url;
				}
			}
		}
	}

	return $urls;
}

/**
 * Normalize a URL the way a browser serializes an <a>.href property, so the
 * click beacon (which reads anchor.href) matches a stored value that lacks the
 * trailing slash. Browsers insert "/" after a bare authority (before any query
 * or fragment); URLs that already have a path are returned unchanged.
 *
 * @since 1.1.0
 *
 * @param string $url URL to normalize.
 * @return string
 */
function wp_mylinks_browser_normalize_url( $url ) {
	$parts = wp_parse_url( (string) $url );
	if ( ! is_array( $parts ) || empty( $parts['host'] ) || ! empty( $parts['path'] ) ) {
		return (string) $url;
	}
	$auth = ( isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '//' )
		. ( isset( $parts['user'] ) ? $parts['user'] . ( isset( $parts['pass'] ) ? ':' . $parts['pass'] : '' ) . '@' : '' )
		. $parts['host']
		. ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
	return $auth . '/'
		. ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' )
		. ( isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '' );
}

/**
 * AJAX endpoint recording one click on a MyLink page link.
 *
 * Data shape (read by Pro's analytics as-is): post meta
 * `wp_mylinks_link_clicks` = array( md5( link URL ) => int count ). Keying by
 * URL hash survives row reordering; editing a link's URL starts a fresh
 * count, which is the honest semantic for "a different link".
 *
 * Deliberately nonce-free: bio pages are routinely full-page-cached, so a
 * rendered nonce would expire in the cache and silently drop every click.
 * Instead the input is strictly validated — the post must be a published
 * mylink and the clicked URL must hash-match one of its stored links — and
 * obvious bots are filtered. Same trust level as the visit counter.
 *
 * @since 1.1.0
 * @return void
 */
function wp_mylinks_handle_link_click() {
	if ( wp_mylinks_is_bot_request() ) {
		wp_send_json_error( null, 400 );
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Public tracking beacon; see docblock. Input is strictly validated below.
	$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
	$url     = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	if ( $post_id <= 0 || '' === $url ) {
		wp_send_json_error( null, 400 );
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'mylink' !== $post->post_type || 'publish' !== $post->post_status ) {
		wp_send_json_error( null, 404 );
	}

	$hash  = md5( $url );
	$valid = wp_mylinks_valid_link_urls( $post_id );
	if ( ! isset( $valid[ $hash ] ) ) {
		wp_send_json_error( null, 400 );
	}

	// Read-modify-write on the array meta: a lost race costs at most one count
	// in a per-link tally, so no lock is warranted. The page total stays exact.
	$clicks = get_post_meta( $post_id, 'wp_mylinks_link_clicks', true );
	$clicks = is_array( $clicks ) ? $clicks : array();

	$clicks[ $hash ] = isset( $clicks[ $hash ] ) ? (int) $clicks[ $hash ] + 1 : 1;
	update_post_meta( $post_id, 'wp_mylinks_link_clicks', $clicks );

	/**
	 * Fires after a link click is recorded.
	 *
	 * Pro's analytics listens here to store richer, per-event data.
	 *
	 * @since 1.1.0
	 *
	 * @param int    $post_id Post ID of the MyLink page.
	 * @param string $url     The clicked link URL.
	 * @param string $hash    md5 of the URL — the key in wp_mylinks_link_clicks.
	 * @param int    $count   The new count for this link.
	 */
	do_action( 'wp_mylinks_track_link_click', $post_id, $url, $hash, $clicks[ $hash ] );

	wp_send_json_success();
}
add_action( 'wp_ajax_wp_mylinks_link_click', 'wp_mylinks_handle_link_click' );
add_action( 'wp_ajax_nopriv_wp_mylinks_link_click', 'wp_mylinks_handle_link_click' );

/**
 * Total recorded link clicks for a MyLink page.
 *
 * @since 1.1.0
 *
 * @param int $post_id Post ID.
 * @return int
 */
function wp_mylinks_get_link_clicks_total( $post_id ) {
	$clicks = get_post_meta( (int) $post_id, 'wp_mylinks_link_clicks', true );
	return is_array( $clicks ) ? (int) array_sum( array_map( 'intval', $clicks ) ) : 0;
}

/**
 * Build the accent-color override CSS for a MyLink page (F8, 1.1.0).
 *
 * Four values, resolved per-page meta → global option → unset. Only set
 * values emit declarations, so pages without accents keep their theme
 * untouched. The values are also exposed as custom properties on the body
 * for developers who want to build on them.
 *
 * @since 1.1.0
 *
 * @param int $post_id Post ID.
 * @return string CSS, or '' when no accent is set.
 */
function wp_mylinks_accent_css( $post_id ) {
	$accents = array(
		'bg'          => wp_mylinks_resolve_meta_or_option( $post_id, mylinks_prefix('accent-bg'), 'wp_mylinks_accent_bg', '' ),
		'button-bg'   => wp_mylinks_resolve_meta_or_option( $post_id, mylinks_prefix('accent-button-bg'), 'wp_mylinks_accent_button_bg', '' ),
		'button-text' => wp_mylinks_resolve_meta_or_option( $post_id, mylinks_prefix('accent-button-text'), 'wp_mylinks_accent_button_text', '' ),
		'text'        => wp_mylinks_resolve_meta_or_option( $post_id, mylinks_prefix('accent-text'), 'wp_mylinks_accent_text', '' ),
	);

	$accents = array_filter( array_map( 'sanitize_hex_color', array_map( 'strval', $accents ) ) );
	if ( empty( $accents ) ) {
		return '';
	}

	$vars = '';
	foreach ( $accents as $key => $color ) {
		$vars .= '--mylinks-accent-' . $key . ':' . $color . ';';
	}

	$css = '.mylinks-body{' . $vars . '}';
	if ( isset( $accents['bg'] ) ) {
		$css .= '.mylinks-body{background:var(--mylinks-accent-bg) !important}';
	}
	if ( isset( $accents['button-bg'] ) ) {
		$css .= '.mylinks-body .button,.mylinks-body .mylink-card-title-wrapper{background:var(--mylinks-accent-button-bg) !important;border-color:var(--mylinks-accent-button-bg) !important}';
	}
	if ( isset( $accents['button-text'] ) ) {
		$css .= '.mylinks-body .button,.mylinks-body .button .link-text,.mylinks-body .mylink-card-title{color:var(--mylinks-accent-button-text) !important}';
	}
	if ( isset( $accents['text'] ) ) {
		$css .= '.mylinks-body,.mylinks-body .name h1,.mylinks-body .description,.mylinks-body .description p{color:var(--mylinks-accent-text) !important}';
	}

	return $css;
}

/**
 * Add Settings link on the plugin row.
 */
function wp_mylinks_settings_link( $links_array, $plugin_file_name ) {
	if ( false !== strpos($plugin_file_name, basename(__FILE__)) ) {
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
function wp_mylinks_theme_callback() {
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
 * Sanitize a raw code field (custom scripts / CSS) by the author's capability.
 *
 * These fields legitimately hold `<script>`, `<style>`, and `<iframe>` — but
 * only for users WordPress trusts with raw markup. `current_user_can(
 * 'unfiltered_html' )` is the correct gate: on multisite, core withholds that
 * capability from Editors AND site Administrators (only super admins keep it),
 * so anyone without it gets the value filtered through `wp_kses_post()`, which
 * strips `<script>`/`<iframe>`. This mirrors how core protects post content and
 * the Custom HTML widget, and closes a stored-XSS bypass where a multisite
 * Editor could persist arbitrary JavaScript onto a public MyLink page.
 *
 * Single-site Administrators/Editors already hold `unfiltered_html`, so their
 * behavior is unchanged: the value is returned verbatim (still slashed, per the
 * meta storage contract) after only control-character cleanup.
 *
 * @since 1.1.0
 *
 * @param string $value Raw field value.
 * @return string
 */
function wp_mylinks_sanitize_raw_code( $value ) {
	if ( ! is_string($value) ) {
		return '';
	}
	// Strip NULL and other low control chars; keep \n, \r, \t. No /u modifier:
	// the class is single-byte ASCII controls, so byte-wise matching is correct
	// and never touches multibyte UTF-8 (whose bytes are all >= 0x80). With /u,
	// a single non-UTF-8 byte (e.g. a Windows-1252 smart quote pasted from Word)
	// makes preg_replace return null, which would silently wipe the whole field.
	$stripped = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
	if ( null !== $stripped ) {
		$value = $stripped;
	}
	// Normalize line endings to LF.
	$value = str_replace(array( "\r\n", "\r" ), "\n", $value);

	if ( current_user_can('unfiltered_html') ) {
		return $value;
	}
	return wp_kses_post($value);
}

/**
 * Per-page custom-script field sanitizer (native fields save path).
 *
 * Delegates to the shared capability gate. Named for the field framework's
 * `sanitization_cb`; the trailing CMB2-era arguments are unused.
 *
 * @since 1.0.2
 *
 * @param string $original_value Raw field value.
 * @return string
 */
function wp_mylinks_sanitization_func( $original_value ) {
	return wp_mylinks_sanitize_raw_code($original_value);
}

/**
 * Sanitize a CSS font-family value (custom font support, 1.1.0).
 *
 * Keeps everything a valid `font-family` needs (font names, generic families,
 * commas, single/double quotes, spaces, hyphens) and strips anything that
 * could break out of the CSS declaration it lands in: braces, semicolons,
 * angle brackets, parentheses, backslashes, colons. The result is safe to drop
 * verbatim into a scoped `font-family:<value>` rule. Length-capped.
 *
 * @since 1.1.0
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function wp_mylinks_sanitize_font_family( $value ) {
	if ( ! is_string($value) ) {
		return '';
	}
	$clean = preg_replace('/[^A-Za-z0-9 ,\'"\-]/', '', $value);
	$clean = trim( (string) $clean );
	return function_exists('mb_substr') ? mb_substr($clean, 0, 200) : substr($clean, 0, 200);
}

/**
 * Sanitize a space-separated list of CSS icon classes (bring-your-own icon
 * font support, 1.1.0).
 *
 * Icon fonts use one or more classes (e.g. "fa-brands fa-discord"). Each token
 * passes through sanitize_html_class(), so only safe class characters survive,
 * then they are rejoined with single spaces. Capped at 10 classes. Returns ''
 * when nothing valid remains.
 *
 * @since 1.1.0
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function wp_mylinks_sanitize_icon_classes( $value ) {
	if ( ! is_string($value) ) {
		return '';
	}
	$tokens = preg_split('/\s+/', trim($value));
	$clean  = array();
	foreach ( (array) $tokens as $token ) {
		$token = sanitize_html_class($token);
		if ( '' !== $token ) {
			$clean[] = $token;
		}
	}
	return implode(' ', array_slice($clean, 0, 10));
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
function wp_mylinks_front_page_dropdown() {
	add_filter('get_pages', 'wp_mylinks_enable_front_page');
}
function wp_mylinks_enable_front_page( $pages ) {
	// Note: we intentionally do NOT pass suppress_filters here — the
	// VIP-Minimum coding standard prohibits it, and on a normal admin
	// Reading Settings request the small filter overhead is acceptable.
	$mylinks = get_posts(
		array(
			'post_type'      => 'mylink',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	return array_merge( (array) $pages, (array) $mylinks);
}

/**
 * If a mylink post is set as the front page, ensure WordPress queries it
 * correctly. Replaces the previous duplicate-registered, buggy implementation.
 */
function wp_mylinks_show_front_page( $query ) {
	if ( ! $query->is_main_query() ) {
		return;
	}

	$page_id = (int) $query->get('page_id');
	if ( $page_id <= 0 ) {
		return;
	}

	// If WP didn't already set a post_type, mirror the page_id's actual post_type.
	if ( '' === (string) $query->get('post_type') ) {
		$pt = get_post_type($page_id);
		if ( $pt ) {
			$query->set('post_type', $pt);
		}
	}
}
add_action('pre_get_posts', 'wp_mylinks_show_front_page');

/**
 * Use the dedicated MyLinks template when a mylink post is the front page.
 */
add_filter('template_include', 'wp_mylinks_front_page_template', 1);
function wp_mylinks_front_page_template( $template_path ) {
	if ( is_front_page() && 'mylink' === get_post_type() ) {
		$single_template = WP_MYLINKS_PATH . 'public/partials/wp-mylinks-base-template.php';
		if ( file_exists($single_template) ) {
			return $single_template;
		}
	}
	return $template_path;
}

/**
 * Public post types the user may include as Link URL sources, keyed by slug
 * with a display label. Auto-populates as plugins/themes register new public
 * CPTs. The plugin's own types are excluded: attachments are not link targets,
 * and Collections are always included by wp_mylinks_get_link_post_types().
 *
 * @since 1.1.0
 *
 * @return array<string,string> slug => label.
 */
function wp_mylinks_selectable_link_post_types() {
	$exclude = array( 'attachment', 'mylink', 'mylinks-collection' );
	$out     = array();
	foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $slug => $obj ) {
		if ( in_array( $slug, $exclude, true ) ) {
			continue;
		}
		$out[ $slug ] = isset( $obj->labels->name ) && $obj->labels->name ? $obj->labels->name : $obj->label;
	}
	return $out;
}

/**
 * Default Link URL post types when the user has never saved a choice: Posts and
 * Pages, plus Products only when WooCommerce is active. Computed on the fly (not
 * stored at install) so activating WooCommerce later adds Products without a
 * re-save.
 *
 * @since 1.1.0
 *
 * @return string[] Post type slugs.
 */
function wp_mylinks_default_link_post_types() {
	$defaults = array( 'post', 'page' );
	if ( class_exists( 'WooCommerce' ) && post_type_exists( 'product' ) ) {
		$defaults[] = 'product';
	}
	return $defaults;
}

/**
 * The effective Link URL post types: the saved Tools choice when present,
 * otherwise the dynamic defaults. Stale slugs (a CPT that was deregistered) are
 * dropped. Collections are always a valid link source regardless of this list,
 * so pass $include_collection to append 'mylinks-collection'.
 *
 * @since 1.1.0
 *
 * @param bool $include_collection Append the mylinks-collection post type.
 * @return string[] Post type slugs.
 */
function wp_mylinks_get_link_post_types( $include_collection = false ) {
	$stored = get_option( 'wp_mylinks_link_post_types', null );
	if ( ! is_array( $stored ) ) {
		$types = wp_mylinks_default_link_post_types();
	} else {
		$valid = wp_mylinks_selectable_link_post_types();
		$types = array();
		foreach ( $stored as $slug ) {
			if ( isset( $valid[ $slug ] ) ) {
				$types[] = $slug;
			}
		}
	}
	if ( $include_collection ) {
		$types[] = 'mylinks-collection';
	}
	return $types;
}

/**
 * Sanitize the Tools-tab Link URL post-types option: keep only registered,
 * selectable public post types; drop unknowns and duplicates. An empty result
 * is a valid "Collections only" choice.
 *
 * @since 1.1.0
 *
 * @param mixed $value Raw submitted value (array of slugs, or null when the
 *                     user unchecked every box).
 * @return string[] Sanitized slug list.
 */
function wp_mylinks_sanitize_post_types( $value ) {
	$valid = wp_mylinks_selectable_link_post_types();
	$out   = array();
	foreach ( (array) $value as $slug ) {
		$slug = sanitize_key( $slug );
		if ( isset( $valid[ $slug ] ) && ! in_array( $slug, $out, true ) ) {
			$out[] = $slug;
		}
	}
	return $out;
}

/**
 * Options callback for the Links box "Link URL" picker (the `pw_select`
 * / Select2 field).
 *
 * LIVE code, not CMB2 legacy: the native fields framework
 * (Wp_Mylinks_Field::options()) invokes this with the field object, whose
 * public `->args` deliberately mirrors CMB2's shape. Returns up to ten
 * pages/posts/collections as `id => "Title - URL"`; the admin JS splits that
 * label on " - " to auto-fill the URL. Renamed from
 * wp_mylinks_get_cmb2_post_options() in 1.1.0 (thin alias kept below).
 *
 * @since 1.0.6
 *
 * @param Wp_Mylinks_Field $field The field requesting its options.
 * @return array<int,string> Map of post ID => "Title - URL" label.
 */
function wp_mylinks_get_post_options( $field ) {
	$query_args = ( isset($field->args['wp_query_args']) && is_array($field->args['wp_query_args']) )
		? $field->args['wp_query_args']
		: array();
	$args       = wp_parse_args(
		$query_args,
		array(
			'post_type'      => wp_mylinks_get_link_post_types( true ),
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	// The editor renders this picker once per existing link row with identical
	// args, so memoize per request: many rows then cost a single query, not one
	// each. AJAX search is the primary path; this is only the preloaded set.
	static $memo = array();
	$memo_key    = md5( wp_json_encode( $args ) );
	if ( isset( $memo[ $memo_key ] ) ) {
		return $memo[ $memo_key ];
	}

	$posts        = new WP_Query($args);
	$post_options = array();

	if ( $posts->have_posts() ) {
		$posts_with_hierarchy = wp_mylinks_build_post_tree($posts->posts);
		foreach ( $posts_with_hierarchy as $post_data ) {
			$post_id    = $post_data['ID'];
			$post_title = $post_data['post_title'];
			$post_type  = get_post_type($post_id);
			if ( 'mylinks-collection' === $post_type ) {
				$post_url = (string) get_post_meta($post_id, mylinks_collection('link_collection'), true);
			} else {
				$post_url = (string) get_permalink($post_id);
			}
			$post_options[ $post_id ] = $post_title . ' - ' . $post_url;
		}
	}

	$memo[ $memo_key ] = $post_options;
	return $post_options;
}

/**
 * The Link URL picker label for one post: "Title - URL". Collections resolve
 * to their stored destination URL rather than a permalink, matching
 * wp_mylinks_get_post_options().
 *
 * @since 1.1.0
 *
 * @param int $post_id Post ID.
 * @return string
 */
function wp_mylinks_link_option_label( $post_id ) {
	$post_id = (int) $post_id;
	if ( 'mylinks-collection' === get_post_type( $post_id ) ) {
		$url = (string) get_post_meta( $post_id, mylinks_collection( 'link_collection' ), true );
	} else {
		$url = (string) get_permalink( $post_id );
	}
	return get_the_title( $post_id ) . ' - ' . $url;
}

add_action( 'wp_ajax_wp_mylinks_link_search', 'wp_mylinks_ajax_link_search' );
/**
 * AJAX search for the Links box "Link URL" picker.
 *
 * The picker is a Select2 that searches server-side, so the post types chosen
 * on the Tools tab (Collections always included) are reachable at any catalog
 * size instead of being capped at a preloaded page. Requires the fields AJAX
 * nonce and an editing-capable user; returns Select2's {results:[{id,text}]}.
 *
 * @since 1.1.0
 *
 * @return void
 */
function wp_mylinks_ajax_link_search() {
	check_ajax_referer( 'wp_mylinks_fields_ajax', 'nonce' );

	// The mylink post type registers page-level capabilities, so gate on the
	// same primitive that lets a user reach the editor this picker lives in.
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_send_json_error( array( 'message' => __( 'Not allowed.', 'wp-mylinks' ) ), 403 );
	}

	$term  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$query = new WP_Query(
		array(
			'post_type'           => wp_mylinks_get_link_post_types( true ),
			'post_status'         => 'publish',
			's'                   => $term,
			'posts_per_page'      => 30,
			'orderby'             => '' !== $term ? 'relevance' : 'title',
			'order'               => 'ASC',
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);

	$results = array();
	foreach ( $query->posts as $post_id ) {
		$results[] = array(
			'id'   => (string) $post_id,
			'text' => wp_mylinks_link_option_label( $post_id ),
		);
	}

	wp_send_json_success( array( 'results' => $results ) );
}

/**
 * Backward-compatible alias for the pre-1.1.0 callback name.
 *
 * Kept because the name may be referenced as a string in a third-party or Pro
 * box definition passed through the `wp_mylinks_meta_boxes` filter.
 *
 * @since 1.0.6
 * @deprecated 1.1.0 Use wp_mylinks_get_post_options().
 *
 * @param Wp_Mylinks_Field $field The field requesting its options.
 * @return array<int,string>
 */
function wp_mylinks_get_cmb2_post_options( $field ) {
	return wp_mylinks_get_post_options($field);
}

/**
 * Flatten a set of post IDs into a title-indented hierarchy list.
 *
 * Helper for wp_mylinks_get_post_options(): orders children under parents and
 * prefixes each nested title with em-dashes so the Select2 dropdown reads as a
 * tree. Recurses by parent ID.
 *
 * @since 1.0.6
 *
 * @param int[] $post_ids  Post IDs to arrange.
 * @param int   $parent_id Parent to collect children for (0 = top level).
 * @param int   $level     Current depth, for indentation.
 * @return array<int,array{ID:int,post_title:string}>
 */
function wp_mylinks_build_post_tree( $post_ids, $parent_id = 0, $level = 0 ) {
	$branch = array();
	foreach ( (array) $post_ids as $post_id ) {
		$post_parent = wp_get_post_parent_id($post_id);
		if ( (int) $parent_id === (int) $post_parent ) {
			$post_title = get_the_title($post_id);
			$post_title = str_repeat('&mdash; ', $level) . $post_title;
			$branch[]   = array(
				'ID'         => $post_id,
				'post_title' => $post_title,
			);
			$children   = wp_mylinks_build_post_tree($post_ids, $post_id, $level + 1);
			if ( ! empty($children) ) {
				$branch = array_merge($branch, $children);
			}
		}
	}
	return $branch;
}

/*
 * Removed in 1.1.0: wp_mylinks_custom_pw_select_render_row() and its
 * cmb2_render_row_cb filter. The script it injected read data-url attributes
 * that only the never-hooked wp_mylinks_modify_select_url_options() would have
 * set, so the handler could never fire. The working URL auto-fill lives in
 * admin/js/wp-mylinks-select.js (label-string splitting).
 */

/**
 * Resolve a link row's Collection/post selection to its CURRENT URL.
 *
 * Link rows store both the picked post ID (select_url) and a snapshot of its
 * URL (url) that admin JS copied at pick time. Before 1.1.0 only the snapshot
 * rendered, so editing a Collection link later left stale URLs on every page
 * that used it. Render-time resolution fixes that; the stored string remains
 * the fallback when the picked post is gone.
 *
 * @since 1.1.0
 *
 * @param int $selected_id Post ID stored in the row's select_url.
 * @return string Fresh URL, or '' when it cannot be resolved.
 */
function wp_mylinks_resolve_selected_url( $selected_id ) {
	$selected_id = (int) $selected_id;
	if ( $selected_id <= 0 ) {
		return '';
	}
	$selected = get_post( $selected_id );
	if ( ! $selected instanceof WP_Post || 'publish' !== $selected->post_status ) {
		return '';
	}
	if ( 'mylinks-collection' === $selected->post_type ) {
		return (string) get_post_meta( $selected->ID, mylinks_collection('link_collection'), true );
	}
	return (string) get_permalink( $selected );
}

/**
 * Read social-platform URL and icon meta for the current post.
 *
 * @since 1.0.6
 */
function wp_mylinks_get_social_meta( $platform ) {
	$post_id = get_the_ID();
	$url     = $post_id ? get_post_meta($post_id, mylinks_prefix("{$platform}-url"), true) : '';
	$icon    = $post_id ? get_post_meta($post_id, mylinks_prefix("{$platform}-icon"), true) : '';
	return array( $url, $icon );
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
function wp_mylinks_extract_youtube_id( $url ) {
	if ( ! is_string($url) || '' === $url ) {
		return '';
	}

	$parsed = wp_parse_url($url);
	if ( ! is_array($parsed) || empty($parsed['host']) ) {
		return '';
	}

	$host = strtolower($parsed['host']);
	$path = isset($parsed['path']) ? trim($parsed['path'], '/') : '';

	// youtu.be/<id>
	if ( 'youtu.be' === $host ) {
		$candidate = $path;
	} elseif ( preg_match('#^(?:.*\.)?youtube(?:-nocookie)?\.com$#', $host) ) {
		// /watch?v=<id>
		if ( ! empty($parsed['query']) ) {
			parse_str($parsed['query'], $qs);
			if ( ! empty($qs['v']) ) {
				$candidate = (string) $qs['v'];
			}
		}
		// /embed/<id> or /shorts/<id>
		if ( empty($candidate) && '' !== $path ) {
			$parts = explode('/', $path);
			if ( count($parts) >= 2 && in_array($parts[0], array( 'embed', 'shorts', 'v' ), true) ) {
				$candidate = $parts[1];
			}
		}
	}

	if ( empty($candidate) ) {
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
function wp_mylinks_dequeue_others() {
	if ( ! is_singular('mylink') ) {
		return;
	}

	global $wp_scripts, $wp_styles;
	if ( $wp_scripts && ! empty($wp_scripts->queue) ) {
		foreach ( $wp_scripts->queue as $handle ) {
			if ( ! isset($wp_scripts->registered[ $handle ]) ) {
				continue;
			}
			$src = (string) $wp_scripts->registered[ $handle ]->src;
			if ( false === strpos($src, '/wp-mylinks/') ) {
				wp_dequeue_script($handle);
			}
		}
	}
	if ( $wp_styles && ! empty($wp_styles->queue) ) {
		foreach ( $wp_styles->queue as $handle ) {
			if ( ! isset($wp_styles->registered[ $handle ]) ) {
				continue;
			}
			$src = (string) $wp_styles->registered[ $handle ]->src;
			if ( false === strpos($src, '/wp-mylinks/') ) {
				wp_dequeue_style($handle);
			}
		}
	}
}
if ( 'yes' === get_option('wp_mylinks_dequeue') ) {
	add_action('wp_print_scripts', 'wp_mylinks_dequeue_others', 100);
	add_action('wp_print_styles', 'wp_mylinks_dequeue_others', 100);
}

/**
 * Decorate an Online Store Kit / author-owned URL with campaign parameters.
 *
 * Only for links to our own properties (onlinestorekit.com, walterpinem.me,
 * seniberpikir.com), and only ever followed by an admin who clicked one.
 * Nothing is requested in the background and nothing is sent unless the link
 * is used, so this is link tagging rather than telemetry — WordPress.org URLs
 * are deliberately left alone.
 *
 * Matches the shared Online Store Kit helper shape (see Indonesian Banks'
 * `ibfw_url()`); the `wpmylinks_url_params` filter name predates that and is
 * kept as public API.
 *
 * @param string $base_url URL to decorate.
 * @return string          Fully escaped URL.
 */
if ( ! function_exists('wpmylinks_url') ) {
	function wpmylinks_url( $base_url ) {
		$base_url = esc_url_raw($base_url);

		if ( function_exists('get_user_locale') ) {
			$user_language = get_user_locale();
		} elseif ( function_exists('determine_locale') ) {
			$user_language = determine_locale();
		} else {
			$user_language = get_locale();
		}

		$screen = 'frontend';
		if ( is_admin() ) {
            // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only screen naming for the campaign parameter.
			$page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
			$tab  = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
            // phpcs:enable WordPress.Security.NonceVerification.Recommended
			if ( $page ) {
				$screen = $page . ( $tab ? '-' . $tab : '' );
			}
		}

		$params = array(
			'php_version'    => phpversion(),
			'wp_version'     => get_bloginfo('version'),
			'plugin_name'    => sanitize_title(WP_MYLINKS_NAME),
			'plugin_version' => WP_MYLINKS_VERSION,
			'user_language'  => $user_language,
			'screen'         => $screen,
		);

		/**
		 * Filter the parameters appended to Online Store Kit links.
		 *
		 * @param array  $params   Parameters before the URL is built.
		 * @param string $base_url The original URL.
		 */
		$params = apply_filters('wpmylinks_url_params', $params, $base_url);

		return esc_url(add_query_arg($params, $base_url));
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
if ( ! function_exists('wp_mylinks_resolve_meta_or_option') ) {
	function wp_mylinks_resolve_meta_or_option( $post_id, $post_meta_key, $option_key, $fallback = '' ) {
		$post_id = (int) $post_id;
		if ( $post_id > 0 ) {
			$value = get_post_meta($post_id, $post_meta_key, true);
			if ( '' !== $value && null !== $value ) {
				return $value;
			}
		}
		$value = get_option($option_key);
		if ( '' !== $value && null !== $value && false !== $value ) {
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
if ( ! function_exists('wp_mylinks_yoast_handles_og') ) {
	function wp_mylinks_yoast_handles_og() {
		if ( ! function_exists('wp_mylinks_isYoastActive') || ! wp_mylinks_isYoastActive() ) {
			return false;
		}
		$social = get_option('wpseo_social');
		if ( ! is_array($social) ) {
			return false;
		}
		return ! empty($social['opengraph']);
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
if ( ! function_exists('wp_mylinks_yoast_handles_schema') ) {
	function wp_mylinks_yoast_handles_schema() {
		if ( ! function_exists('wp_mylinks_isYoastActive') || ! wp_mylinks_isYoastActive() ) {
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
if ( ! function_exists('wp_mylinks_collect_socials') ) {
	function wp_mylinks_collect_socials( $post_id ) {
		$post_id   = (int) $post_id;
		$collected = array();

		if ( $post_id <= 0 ) {
			return $collected;
		}

		// Hardcoded 8 platforms — preserved exactly to avoid breaking existing data.
		$platforms = array( 'facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'pinterest', 'tiktok', 'discord' );
		foreach ( $platforms as $platform ) {
			$url = (string) get_post_meta($post_id, mylinks_prefix($platform . '-url'), true);
			if ( '' === trim($url) ) {
				continue;
			}
			$icon        = (string) get_post_meta($post_id, mylinks_prefix($platform . '-icon'), true);
			$collected[] = array(
				'name' => ucfirst($platform),
				'url'  => $url,
				'icon' => $icon,
			);
		}

		// Additional Social Platforms (CMB2 group, new in 1.0.8).
		$additional = get_post_meta($post_id, mylinks_prefix('additional-socials'), true);
		if ( is_array($additional) ) {
			foreach ( $additional as $row ) {
				if ( ! is_array($row) ) {
					continue;
				}
				$name = isset($row['name']) ? (string) $row['name'] : '';
				$url  = isset($row['url']) ? (string) $row['url'] : '';
				$icon = isset($row['icon']) ? (string) $row['icon'] : '';
				if ( '' === trim($url) || '' === trim($name) ) {
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
if ( ! function_exists('wp_mylinks_resolve_og_image') ) {
	function wp_mylinks_resolve_og_image( $post_id ) {
		$post_id = (int) $post_id;

		if ( $post_id > 0 ) {
			$per_page = (string) get_post_meta($post_id, mylinks_prefix('og-image'), true);
			if ( '' !== $per_page ) {
				return $per_page;
			}
		}

		$global_og = (string) get_option('wp_mylinks_og_image', '');
		if ( '' !== $global_og ) {
			return $global_og;
		}

		if ( $post_id > 0 && function_exists('wp_mylinks_isYoastActive') && wp_mylinks_isYoastActive() ) {
			$yoast_og = (string) get_post_meta($post_id, '_yoast_wpseo_opengraph-image', true);
			if ( '' !== $yoast_og ) {
				return $yoast_og;
			}
		}

		if ( $post_id > 0 ) {
			$avatar = (string) get_post_meta($post_id, mylinks_prefix('avatar'), true);
			if ( '' !== $avatar ) {
				return $avatar;
			}
		}

		$favicon = (string) get_option('mylinks_upload_favicon', '');
		if ( '' !== $favicon ) {
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
if ( ! function_exists('wp_mylinks_build_schema_json') ) {
	function wp_mylinks_build_schema_json( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return null;
		}

		// Per-page override: 'inherit' / 'Person' / 'Organization' / '' (= inherit).
		$per_page_type = (string) get_post_meta($post_id, mylinks_prefix('schema-type'), true);
		if ( 'Person' === $per_page_type || 'Organization' === $per_page_type ) {
			$schema_type = $per_page_type;
		} else {
			$global_type = (string) get_option('wp_mylinks_schema_type', 'Person');
			$schema_type = ( 'Organization' === $global_type ) ? 'Organization' : 'Person';
		}

		$post = get_post($post_id);
		if ( ! $post ) {
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
		if ( '' === $permalink ) {
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

		if ( '' !== $display_name ) {
			$entity['name'] = $display_name;
		}
		if ( '' !== $description_plain ) {
			$entity['description'] = $description_plain;
		}
		if ( '' !== $avatar ) {
			$entity['image'] = esc_url_raw($avatar);
		}
		$entity['url'] = esc_url_raw($permalink);

		// sameAs from collected socials.
		$socials = wp_mylinks_collect_socials($post_id);
		$same_as = array();
		foreach ( $socials as $social ) {
			if ( ! empty($social['url']) ) {
				$same_as[] = esc_url_raw($social['url']);
			}
		}
		if ( ! empty($same_as) ) {
			$entity['sameAs'] = array_values(array_unique($same_as));
		}

		// -------------------------------------------------------------
		// Build the ProfilePage wrapper (optional, controlled by toggle).
		// -------------------------------------------------------------

		$emit_profilepage = ( 'yes' === get_option('wp_mylinks_enable_profilepage') );
		$profilepage      = null;

		if ( $emit_profilepage ) {
			$profilepage = array(
				'@type' => 'ProfilePage',
				'@id'   => $permalink . '#profilepage',
				'url'   => esc_url_raw($permalink),
			);

			if ( '' !== $display_name ) {
				$profilepage['name'] = $display_name;
			}
			if ( '' !== $description_plain ) {
				$profilepage['description'] = $description_plain;
			}

			// dateCreated / dateModified — required for high-quality ProfilePage.
			if ( ! empty($post->post_date_gmt) && '0000-00-00 00:00:00' !== $post->post_date_gmt ) {
				$profilepage['dateCreated'] = mysql2date('c', $post->post_date_gmt, false);
			}
			if ( ! empty($post->post_modified_gmt) && '0000-00-00 00:00:00' !== $post->post_modified_gmt ) {
				$profilepage['dateModified'] = mysql2date('c', $post->post_modified_gmt, false);
			}

			// inLanguage — useful for multilingual sites.
			$locale = function_exists('determine_locale') ? determine_locale() : get_locale();
			if ( is_string($locale) && '' !== $locale ) {
				// Schema.org expects a BCP-47 tag (e.g. en-US). WP locales are
				// underscore-joined (en_US); convert.
				$profilepage['inLanguage'] = str_replace('_', '-', $locale);
			}

			// mainEntity — the link to the Person/Organization we built above.
			$profilepage['mainEntity'] = array( '@id' => $entity_id );
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
			'@type'           => 'BreadcrumbList',
			'@id'             => $permalink . '#breadcrumb',
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
		$graph = array( $entity );
		if ( $profilepage ) {
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

		if ( ! is_array($data) || empty($data) ) {
			return null;
		}

		// JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE keeps URLs and
		// non-ASCII names readable in source view. JSON_HEX_TAG + JSON_HEX_AMP
		// hex-encode <, >, & so a literal "</script>" in any value can never
		// break out of the surrounding <script type="application/ld+json"> block
		// — a latent breakout that JSON_UNESCAPED_SLASHES would otherwise leave
		// open for any future filter or relaxed field sanitizer.
		$flags = 0;
		if ( defined('JSON_UNESCAPED_SLASHES') ) {
			$flags |= JSON_UNESCAPED_SLASHES;
		}
		if ( defined('JSON_UNESCAPED_UNICODE') ) {
			$flags |= JSON_UNESCAPED_UNICODE;
		}
		if ( defined('JSON_HEX_TAG') ) {
			$flags |= JSON_HEX_TAG;
		}
		if ( defined('JSON_HEX_AMP') ) {
			$flags |= JSON_HEX_AMP;
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
if ( ! function_exists('wp_mylinks_sanitize_schema_type') ) {
	function wp_mylinks_sanitize_schema_type( $value ) {
		// Case-sensitive compare against the exact form value; sanitize_key()
		// was used here before 1.1.0 and lowercased the value first, which made
		// 'Organization' unsaveable (it always reset to 'Person').
		return ( is_string($value) && 'Organization' === trim($value) ) ? 'Organization' : 'Person';
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
if ( ! function_exists('wp_mylinks_sanitize_twitter_handle') ) {
	function wp_mylinks_sanitize_twitter_handle( $value ) {
		if ( ! is_string($value) ) {
			return '';
		}
		$value = trim($value);
		if ( '' === $value ) {
			return '';
		}
		// Keep only alphanumerics and underscore (Twitter rules).
		$value = preg_replace('/[^A-Za-z0-9_]/', '', $value);
		if ( '' === $value ) {
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
function run_wp_mylinks() {
	$plugin = new Wp_Mylinks();
	$plugin->run();
}
run_wp_mylinks();
