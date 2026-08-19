<?php
/**
 * Select field.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Select dropdown fed by `options` or `options_cb`. Stored value is
 * sanitize_text_field'd like CMB2's — deliberately not validated against the
 * option list, so legacy values saved under CMB2 survive a round-trip; use a
 * per-field `sanitization_cb` where strict whitelisting is wanted.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_Field_Select extends Wp_Mylinks_Field {

	/**
	 * Render the select.
	 *
	 * @param mixed $value Current value.
	 * @return void
	 */
	protected function render_control( $value ) {
		printf(
			'<select id="%1$s" name="%2$s"%3$s>',
			esc_attr( $this->input_attr_id() ),
			esc_attr( $this->input_name() ),
			$this->attributes_html() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped key-by-key in attributes_html().
		);
		foreach ( $this->options() as $option_value => $label ) {
			printf(
				'<option value="%1$s"%3$s>%2$s</option>',
				esc_attr( (string) $option_value ),
				esc_html( (string) $label ),
				selected( (string) $value, (string) $option_value, false )
			);
		}
		echo '</select>';
	}
}
