<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://walterpinem.me/
 * @since      1.0.0
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/admin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and the hooks for enqueueing the
 * admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/admin
 * @author     Walter Pinem <hello@walterpinem.me>
 */
class Wp_Mylinks_Admin
{

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

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string $plugin_name The name of this plugin.
	 * @param    string $version     The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * Loads only on plugin-relevant screens to avoid leaking styles into
	 * unrelated admin pages.
	 *
	 * @since    1.0.0
	 * @param    string $hook Current admin page hook suffix.
	 */
	public function enqueue_styles($hook = '')
	{
		if (!$this->is_plugin_screen($hook)) {
			return;
		}
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . 'css/wp-mylinks-admin.min.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * Loads only on plugin-relevant screens. Also pulls in the WP media
	 * library on the settings page so the favicon uploader works.
	 *
	 * @since    1.0.0
	 * @param    string $hook Current admin page hook suffix.
	 */
	public function enqueue_scripts($hook = '')
	{
		if (!$this->is_plugin_screen($hook)) {
			return;
		}
		// The favicon uploader needs the WP media JS modal.
		if (function_exists('wp_enqueue_media')) {
			wp_enqueue_media();
		}
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . 'js/wp-mylinks-admin.js',
			array('jquery'),
			$this->version,
			true
		);
	}

	/**
	 * Should the plugin's admin assets load on the current screen?
	 *
	 * Returns true on:
	 *   - the WP MyLinks settings page (any tab),
	 *   - the mylink and mylinks-collection edit / add / list screens.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return bool
	 */
	private function is_plugin_screen($hook)
	{
		if (!function_exists('get_current_screen')) {
			return false;
		}
		$screen = get_current_screen();
		if (!$screen) {
			return false;
		}

		// Settings page: edit.php?post_type=mylink&page=welcome.
		// Hook for that submenu is "mylink_page_welcome".
		if (isset($screen->id) && 'mylink_page_welcome' === $screen->id) {
			return true;
		}

		// Mylink CPT screens (list, edit, new).
		if (isset($screen->post_type) && in_array($screen->post_type, array('mylink', 'mylinks-collection'), true)) {
			return true;
		}

		return false;
	}
}

/**
 * NOTE: Permalink slug-stripping and request resolution have moved to
 * includes/class-wp-mylinks-rewrites.php (the Wp_Mylinks_Rewrites class).
 *
 * The previous implementation here used:
 *   - post_type_link with str_replace (could mangle posts whose slug contained
 *     "mylink"),
 *   - a pre_get_posts shim with a fragile `2 != count($query->query)` check
 *     that broke whenever any other plugin added query vars,
 *   - and a CPT slug of '/' which conflicted with pages, posts, taxonomies,
 *     and pagination.
 *
 * Those have been replaced with a clean request-resolution layer that uses a
 * real CPT slug and only routes traffic to mylink when no page or post owns the
 * same slug.
 */

/**
 * Show custom columns on post listing
 *
 * @since 1.0.0
 */
add_filter('manage_posts_columns', 'wp_mylinks_default_columns_head');

function wp_mylinks_default_columns_head($defaults)
{
	if (!is_admin()) {
		return $defaults;
	}

	$current_screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if ($current_screen && in_array($current_screen->post_type, array('mylink'), true)) {
		$defaults['slug']       = __('URL', 'wp-mylinks');
		$defaults['post_views'] = __('Views', 'wp-mylinks');
	}
	return $defaults;
}

function wp_mylinks_default_columns_content($column_name, $post_ID)
{
	if ($column_name === 'slug') {
		$post = get_post($post_ID);
		if (!$post || 'mylink' !== $post->post_type) {
			return;
		}
		// Use the canonical (slug-stripped) permalink so admins see the real URL.
		$url = get_permalink($post);
		if (!$url && !empty($post->post_name)) {
			$url = home_url('/' . $post->post_name . '/');
		}
		printf(
			'<input class="mylink-copy" type="text" readonly value="%1$s" onclick="this.setSelectionRange(0, this.value.length)">',
			esc_attr(esc_url($url))
		);
	}

	if ($column_name === 'post_views') {
		$post_views = (int) get_post_meta($post_ID, 'wp_mylinks_count_visits', true);
		echo esc_html(number_format_i18n($post_views));
	}
}
add_action('manage_posts_custom_column', 'wp_mylinks_default_columns_content', 10, 2);

/**
 * Show admin notice to flush permalink (only once after activation)
 *
 * Since the plugin now flushes rewrites on activation automatically, this
 * notice is informational and only appears if a user requested it via the
 * "Hide Admin Notice" setting being unchecked.
 *
 * @since 1.0.5
 */
function wp_mylinks_admin_notice()
{
	// Don't show on settings page itself.
	$current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
	if ('welcome' === $current_page) {
		return;
	}

	$mylink_new_url  = esc_url(admin_url('post-new.php?post_type=mylink'));
	$permalink_url   = esc_url(admin_url('options-permalink.php'));
	$hide_notice_url = esc_url(admin_url('edit.php?post_type=mylink&page=welcome&tab=global'));

	$message = sprintf(
		/* translators: 1: URL to create a new MyLink, 2: URL to the Permalinks settings page, 3: URL to the plugin's Global settings tab */
		__('<b>Quick Start Tutorial:</b><br /><ol><li>Go ahead and publish your first <a href="%1$s"><b>MyLinks</b></a> page.</li><li>If your MyLinks page encounters 404 Not Found, go to <a href="%2$s"><b>Permalinks</b></a> page and click the <b>Save Changes</b> button without changing anything.</li><li>If everything\'s alright, hide this notice by ticking the <a href="%3$s"><b>Hide Admin Notice</b></a> checkbox.</li></ol>', 'wp-mylinks'),
		$mylink_new_url,
		$permalink_url,
		$hide_notice_url
	);

	$allowed_html = array(
		'b'      => array(),
		'br'     => array(),
		'ol'     => array(),
		'li'     => array(),
		'a'      => array('href' => array()),
		'strong' => array(),
	);
?>
	<div class="update-nag notice is-dismissible">
		<p><?php echo wp_kses($message, $allowed_html); ?></p>
	</div>
<?php
}

if ('yes' !== get_option('wp_mylinks_hide_notice')) {
	add_action('admin_notices', 'wp_mylinks_admin_notice');
}

/**
 * Determine whether the current admin screen belongs to WP MyLinks.
 *
 * Returns true on:
 *   - the WP MyLinks settings page (any tab),
 *   - the mylink and mylinks-collection edit / add / list screens.
 *
 * Used by filters that should only modify output on plugin-owned screens.
 *
 * @since 1.0.8
 *
 * @return bool
 */
function wp_mylinks_is_plugin_admin_screen()
{
	if (!function_exists('get_current_screen')) {
		return false;
	}
	$screen = get_current_screen();
	if (!$screen) {
		return false;
	}

	// Settings page: edit.php?post_type=mylink&page=welcome.
	if (isset($screen->id) && 'mylink_page_welcome' === $screen->id) {
		return true;
	}

	// Mylink and Collection CPT screens (list, edit, new).
	if (isset($screen->post_type) && in_array($screen->post_type, array('mylink', 'mylinks-collection'), true)) {
		return true;
	}

	return false;
}

/**
 * Replace the wp-admin footer text on WP MyLinks screens with a friendly
 * 5-star review prompt. Falls through unchanged on every other admin page.
 *
 * @since 1.0.8
 *
 * @param string $footer_text Original footer text.
 * @return string Modified footer text on plugin screens, original elsewhere.
 */
function wp_mylinks_admin_footer_text($footer_text)
{
	if (!wp_mylinks_is_plugin_admin_screen()) {
		return $footer_text;
	}

	$plugin_name = defined('WP_MYLINKS_NAME') ? WP_MYLINKS_NAME : 'WP MyLinks';
	$review_url  = 'https://wordpress.org/support/plugin/wp-mylinks/reviews/?rate=5#new-post';

	$message = sprintf(
		/* translators: 1: plugin name, 2: 5-star review link */
		esc_html__('Enjoyed %1$s? Please leave a %2$s rating. I really appreciate your support!', 'wp-mylinks'),
		'<strong>' . esc_html($plugin_name) . '</strong>',
		'<a href="' . esc_url($review_url) . '" target="_blank" rel="noopener"><span class="screen-reader-text">' . esc_html__('5 stars', 'wp-mylinks') . '</span>★★★★★</a>'
	);

	return '<span class="wp-mylinks-footer-thankyou">' . $message . '</span>';
}
add_filter('admin_footer_text', 'wp_mylinks_admin_footer_text');
