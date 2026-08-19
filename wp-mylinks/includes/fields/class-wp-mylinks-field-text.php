<?php
/**
 * Text field.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Single-line text input. Sanitizes with the base sanitize_text_field.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_Field_Text extends Wp_Mylinks_Field {

	/**
	 * Render the input.
	 *
	 * @param mixed $value Current value.
	 * @return void
	 */
	protected function render_control( $value ) {
		printf(
			'<input type="text" class="wml-field__input" id="%1$s" name="%2$s" value="%3$s"%4$s>',
			esc_attr( $this->input_attr_id() ),
			esc_attr( $this->input_name() ),
			esc_attr( (string) $value ),
			$this->attributes_html() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped key-by-key in attributes_html().
		);
	}
}
