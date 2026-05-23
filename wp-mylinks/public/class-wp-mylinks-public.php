<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://walterpinem.me/
 * @since      1.0.0
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/public
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * The public-facing functionality of the plugin.
 *
 * The mylink template is fully standalone and bypasses the active theme entirely
 * (see public/partials/wp-mylinks-base-template.php). It enqueues its own
 * assets inline. These methods are kept as orchestration points in case future
 * features need to register additional public assets at wp_enqueue_scripts.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/public
 * @author     Walter Pinem <hello@walterpinem.me>
 */
class Wp_Mylinks_Public
{

	/**
	 * @var string
	 */
	private $plugin_name;

	/**
	 * @var string
	 */
	private $version;

	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Reserved hook for future public-side stylesheet enqueueing. The mylink
	 * template enqueues its own assets directly.
	 */
	public function enqueue_styles()
	{
		// Intentionally empty — see class docblock.
	}

	/**
	 * Reserved hook for future public-side script enqueueing. The mylink template
	 * enqueues its own assets directly.
	 */
	public function enqueue_scripts()
	{
		// Intentionally empty — see class docblock.
	}
}
