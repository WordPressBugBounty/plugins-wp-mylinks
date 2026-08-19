<?php
/**
 * URL field.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * URL input. Rendered as type="text" with inputmode="url" on purpose:
 * type="url" refuses schemeless input ("example.com") at the browser level,
 * while the sanitizer below — a byte-for-byte port of CMB2's
 * sanitize_and_secure_url() — accepts it and prepends https.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_Field_Text_Url extends Wp_Mylinks_Field {

	/**
	 * Render the input.
	 *
	 * @param mixed $value Current value.
	 * @return void
	 */
	protected function render_control( $value ) {
		printf(
			'<input type="text" inputmode="url" class="wml-field__input" id="%1$s" name="%2$s" value="%3$s"%4$s>',
			esc_attr( $this->input_attr_id() ),
			esc_attr( $this->input_name() ),
			esc_attr( (string) $value ),
			$this->attributes_html( array( 'placeholder' => 'https://' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped key-by-key in attributes_html().
		);
	}

	/**
	 * CMB2's URL sanitization, preserved exactly: empty input returns the
	 * field default (usually null, which deletes the row); otherwise
	 * esc_url_raw() against the field's protocols, defaulting the scheme
	 * to https when none was typed.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	protected function sanitize_default( $value ) {
		if ( empty( $value ) ) {
			// CMB2's get_default() returns false when no default is set, and
			// that false is what group rows store — b:0; vs N; in the
			// serialized bytes, so the distinction is part of the contract.
			return null === $this->args['default'] ? false : $this->args['default'];
		}

		$orig_scheme = wp_parse_url( (string) $value, PHP_URL_SCHEME );
		$value       = esc_url_raw( (string) $value, $this->args['protocols'] );

		if ( null === $orig_scheme ) {
			$value = set_url_scheme( $value, 'https' );
		}

		return $value;
	}
}
