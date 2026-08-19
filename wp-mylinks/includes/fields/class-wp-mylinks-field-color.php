<?php
/**
 * Color picker field.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Hex color input, enhanced to core's wp-color-picker (Iris) by the fields
 * JS. New in 1.1.0 (no CMB2 legacy to match): stores a `#rrggbb` string,
 * anything invalid sanitizes to '' and deletes the row.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_Field_Color extends Wp_Mylinks_Field {

	/**
	 * Render the input.
	 *
	 * @param mixed $value Current value.
	 * @return void
	 */
	protected function render_control( $value ) {
		printf(
			'<input type="text" class="wml-color" id="%1$s" name="%2$s" value="%3$s"%4$s>',
			esc_attr( $this->input_attr_id() ),
			esc_attr( $this->input_name() ),
			esc_attr( (string) $value ),
			$this->attributes_html() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped key-by-key in attributes_html().
		);
	}

	/**
	 * Valid hex color or empty (which deletes the meta row).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected function sanitize_default( $value ) {
		$color = sanitize_hex_color( trim( (string) $value ) );
		return is_string( $color ) ? $color : '';
	}
}
