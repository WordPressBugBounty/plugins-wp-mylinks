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

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Mylinks_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Mylinks_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wp-mylinks-admin.min.css', array(), $this->version, 'all');
		// wp_enqueue_style('select2', plugin_dir_url(__FILE__) . 'css/select2.min.css', array(), '4.1.0');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Mylinks_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Mylinks_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wp-mylinks-admin.js', array('jquery'), $this->version, false);
	}
}

/**
 * Post type MyLink slug base
 *
 * @since    1.0.0
 */
function wp_mylinks_remove_slug($post_link, $post, $leavename)
{
	if ('mylink' != $post->post_type || 'publish' != $post->post_status) {
		return $post_link;
	}
	$post_link = str_replace('/' . $post->post_type . '/', '/', $post_link);
	return $post_link;
}
add_filter('post_type_link', 'wp_mylinks_remove_slug', 10, 3);

/**
 * Query the page
 *
 * @since    1.0.0
 */
function wp_mylinks_request($query)
{
	if (!$query->is_main_query() || 2 != count($query->query) || !isset($query->query['page'])) {
		return;
	}
	if (!empty($query->query['name'])) {
		$query->set('post_type', array('post', 'mylink', 'page'));
	}
}
add_action('pre_get_posts', 'wp_mylinks_request');

/**
 * Show custom columns on post listing
 *
 * @since    1.0.0
 */
add_filter('manage_posts_columns', 'wp_mylinks_default_columns_head');

function wp_mylinks_default_columns_head($defaults)
{
	global $current_screen;
	if (in_array($current_screen->post_type, array('mylink'))) {
		$defaults['slug'] = 'URL';
		$defaults['post_views'] = __('Views');
	}
	return $defaults;
}
function wp_mylinks_default_columns_content($column_name, $post_ID)
{
	global $post;
	if ($column_name == 'slug') {
		$post_slug = esc_attr($post->post_name) . '/'; // Escape the slug for use in attributes
		$home_url = home_url($post_slug);
		echo '<input class="mylink-copy" type="text" value="' . esc_url($home_url) . '" id="URLInput" onclick="this.setSelectionRange(0, this.value.length)">'; // Escape the URL for safe output
	}

	if ($column_name === 'post_views') {
		$post_views = get_post_meta(get_the_ID(), 'wp_mylinks_count_visits', true);
		echo esc_html($post_views);
	}
}
add_action('manage_posts_custom_column', 'wp_mylinks_default_columns_content', 10, 2);

/**
 * Show admin notice to flush permalink
 *
 * @since    1.0.5
 */
function wp_mylinks_admin_notice()
{
	// Prepare the URLs and escape them
	$mylink_new_url = esc_url(admin_url('post-new.php?post_type=mylink'));
	$permalink_url = esc_url(admin_url('options-permalink.php'));
	$hide_notice_url = esc_url(admin_url('edit.php?post_type=mylink&page=welcome&tab=global'));

	// Translators: %1$s is the link to the MyLinks post creation page, %2$s is the link to the Permalinks page, %3$s is the link to the Hide Admin Notice setting.
	$message = sprintf(
		/* translators: %1$s is the link to the MyLinks post creation page */
		/* translators: %2$s is the link to the Permalinks page */
		/* translators: %3$s is the link to the Hide Admin Notice setting */
		__('<b>Quick Start Tutorial:</b><br /><ol><li>Go ahead and publish your first <a href="%1$s"><b>MyLinks</b></a> page.</li><li>If your MyLinks page encounters 404 Not Found, go to <a href="%2$s"><b>Permalinks</b></a> page and click the <b>Save Changes</b> button without changing anything.</li><li>If everything\'s alright, hide this notice by ticking the <a href="%3$s"><b>Hide Admin Notice</b></a> checkbox.</li></ol>', 'wp-mylinks'),
		$mylink_new_url,
		$permalink_url,
		$hide_notice_url
	);

?>
	<div class="update-nag notice is-dismissible">
		<p><?php echo $message; // Escape safely
			?></p>
	</div>
<?php
}

// Get the option and sanitize it properly
$hide_notice = get_option('wp_mylinks_hide_notice');
if ($hide_notice !== 'yes') {
	add_action('admin_notices', 'wp_mylinks_admin_notice');
} else {
	remove_action('admin_notices', 'wp_mylinks_admin_notice');
}
