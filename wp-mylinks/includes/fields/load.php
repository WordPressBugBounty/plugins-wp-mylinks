<?php
/**
 * Loader for the WP MyLinks native fields framework.
 *
 * Requires the framework classes and exposes the one public registration
 * function. Introduced in 1.1.0 to replace the bundled CMB2; during the
 * transition both systems coexist and this framework owns no production
 * metaboxes yet.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

require_once __DIR__ . '/class-wp-mylinks-field.php';
require_once __DIR__ . '/class-wp-mylinks-field-text.php';
require_once __DIR__ . '/class-wp-mylinks-field-text-url.php';
require_once __DIR__ . '/class-wp-mylinks-field-textarea-small.php';
require_once __DIR__ . '/class-wp-mylinks-field-select.php';
require_once __DIR__ . '/class-wp-mylinks-field-radio.php';
require_once __DIR__ . '/class-wp-mylinks-field-wysiwyg.php';
require_once __DIR__ . '/class-wp-mylinks-field-file.php';
require_once __DIR__ . '/class-wp-mylinks-field-oembed.php';
require_once __DIR__ . '/class-wp-mylinks-field-group.php';
require_once __DIR__ . '/class-wp-mylinks-field-select2.php';
require_once __DIR__ . '/class-wp-mylinks-field-color.php';
require_once __DIR__ . '/class-wp-mylinks-metabox.php';

add_action( 'wp_ajax_wp_mylinks_oembed_preview', array( 'Wp_Mylinks_Field_Oembed', 'ajax_preview' ) );

/**
 * Register a WP MyLinks metabox.
 *
 * The argument shape mirrors CMB2's new_cmb2_box() + add_field() arrays; see
 * Wp_Mylinks_Metabox::register() for the box keys and Wp_Mylinks_Field for
 * the per-field keys.
 *
 * @since 1.1.0
 *
 * @param array $args Box definition.
 * @return void
 */
function wp_mylinks_register_metabox( array $args ) {
	Wp_Mylinks_Metabox::register( $args );
}
