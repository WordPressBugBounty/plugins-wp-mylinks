<?php

/**
 * Register all actions and filters for the plugin
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
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes
 * @author     Walter Pinem <hello@walterpinem.me>
 */
class Wp_Mylinks_Loader
{

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array $actions
	 */
	protected $actions;

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array $filters
	 */
	protected $filters;

	public function __construct()
	{
		$this->actions = array();
		$this->filters = array();
	}

	public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
	{
		$this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
	}

	public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
	{
		$this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
	}

	private function add($hooks, $hook, $component, $callback, $priority, $accepted_args)
	{
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
		return $hooks;
	}

	public function run()
	{
		foreach ($this->filters as $hook) {
			add_filter(
				$hook['hook'],
				array($hook['component'], $hook['callback']),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
		foreach ($this->actions as $hook) {
			add_action(
				$hook['hook'],
				array($hook['component'], $hook['callback']),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}

/**
 * Front-page support for the mylink CPT in the Reading Settings dropdown.
 *
 * Adds mylink posts to the "Front page" select on Settings → Reading and the
 * Customizer, and 301-redirects single mylink URLs back to the homepage when a
 * mylink is set as the front page.
 *
 * @since 1.0.0
 * @author wpscholar (original implementation)
 */
class Wp_Mylinks_Front_Page_Plugin
{

	/**
	 * Singleton instance.
	 *
	 * @var Wp_Mylinks_Front_Page_Plugin|null
	 */
	private static $instance = null;

	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		if (is_admin()) {
			add_filter('wp_dropdown_pages', array($this, 'wp_dropdown_pages'));
		} else {
			add_action('pre_get_posts', array($this, 'pre_get_posts'));
			add_action('template_redirect', array($this, 'template_redirect'));
		}
	}

	/**
	 * Replace the front-page dropdown with one that includes mylink posts.
	 */
	public function wp_dropdown_pages($output)
	{
		global $pagenow;
		if (
			('options-reading.php' === $pagenow || 'customize.php' === $pagenow)
			&& preg_match('#page_on_front#', $output)
		) {
			$output = $this->posts_dropdown();
		}
		return $output;
	}

	/**
	 * Build the replacement dropdown markup.
	 */
	protected function posts_dropdown()
	{
		$posts = get_posts(array(
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_type'      => array('page', 'mylink'),
			'post_status'    => 'publish',
		));

		$front_page_id = (int) get_option('page_on_front');

		$select  = __('Select', 'wp-mylinks');
		$output  = '<select name="page_on_front" id="page_on_front">';
		$output .= '<option value="0">&mdash; ' . esc_html($select) . ' &mdash;</option>';

		foreach ($posts as $post) {
			$selected      = selected($front_page_id, $post->ID, false);
			$post_type_obj = get_post_type_object($post->post_type);
			$singular      = $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;
			$output .= sprintf(
				'<option value="%1$d"%2$s>%3$s (%4$s)</option>',
				(int) $post->ID,
				$selected,
				esc_html($post->post_title),
				esc_html($singular)
			);
		}
		$output .= '</select>';
		return $output;
	}

	/**
	 * Ensure WP queries the correct post type when a non-page is the front page.
	 */
	public function pre_get_posts($query)
	{
		if (!$query->is_main_query()) {
			return;
		}
		$post_type = $query->get('post_type');
		$page_id   = (int) $query->get('page_id');
		if (empty($post_type) && $page_id > 0) {
			$pt = get_post_type($page_id);
			if ($pt) {
				$query->set('post_type', $pt);
			}
		}
	}

	/**
	 * If a mylink is the front page, 301 its singular URL to the home URL.
	 */
	public function template_redirect()
	{
		global $post;
		if (
			is_singular()
			&& !is_front_page()
			&& isset($post->ID)
			&& absint(get_option('page_on_front')) === (int) $post->ID
		) {
			wp_safe_redirect(home_url('/'), 301);
			exit;
		}
	}
}

Wp_Mylinks_Front_Page_Plugin::get_instance();
