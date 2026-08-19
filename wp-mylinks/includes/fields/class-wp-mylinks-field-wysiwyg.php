<?php
/**
 * WYSIWYG field.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * wp_editor() wrapper. The field's `options` array passes through to the
 * editor settings (wpautop, media_buttons, textarea_rows, teeny — the set
 * the plugin's CMB2 definition used). Sanitizes with wp_kses_post, matching
 * CMB2's wysiwyg default.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_Field_Wysiwyg extends Wp_Mylinks_Field {

	/**
	 * Render the editor.
	 *
	 * @param mixed $value Current value.
	 * @return void
	 */
	protected function render_control( $value ) {
		// Editor ids must be lowercase [a-z0-9_]; meta keys may contain hyphens.
		$editor_id = str_replace( '-', '_', sanitize_key( $this->id() ) );

		$settings = wp_parse_args(
			is_array( $this->args['options'] ) ? $this->args['options'] : array(),
			array(
				'wpautop'       => true,
				'media_buttons' => false,
				'textarea_rows' => 5,
				'teeny'         => true,
			)
		);
		// The submitted name must stay the meta key regardless of editor id.
		$settings['textarea_name'] = $this->input_name();
		$settings['editor_class']  = 'wml-field__wysiwyg';

		wp_editor( (string) $value, $editor_id, $settings );
	}

	/**
	 * CMB2 sanitizes wysiwyg through wp_kses_post.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	protected function sanitize_default( $value ) {
		return is_array( $value ) ? array_map( 'wp_kses_post', $value ) : wp_kses_post( (string) $value );
	}

	/**
	 * The label targets the editor's textarea id, not the meta key.
	 *
	 * @return void
	 */
	protected function render_label() {
		printf(
			'<label class="wml-field__label" for="%1$s">%2$s</label>',
			esc_attr( str_replace( '-', '_', sanitize_key( $this->id() ) ) ),
			esc_html( $this->args['name'] )
		);
	}
}
