<?php
/**
 * Small textarea field.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Four-row textarea. Sanitizes with wp_kses_post, matching CMB2's
 * textarea_small default.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_Field_Textarea_Small extends Wp_Mylinks_Field {

	/**
	 * Render the textarea.
	 *
	 * @param mixed $value Current value.
	 * @return void
	 */
	protected function render_control( $value ) {
		printf(
			'<textarea class="wml-field__input" id="%1$s" name="%2$s" rows="4"%4$s>%3$s</textarea>',
			esc_attr( $this->input_attr_id() ),
			esc_attr( $this->input_name() ),
			esc_textarea( (string) $value ),
			$this->attributes_html() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped key-by-key in attributes_html().
		);
	}

	/**
	 * CMB2 sanitizes textarea_small through wp_kses_post.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	protected function sanitize_default( $value ) {
		return is_array( $value ) ? array_map( 'wp_kses_post', $value ) : wp_kses_post( (string) $value );
	}
}
