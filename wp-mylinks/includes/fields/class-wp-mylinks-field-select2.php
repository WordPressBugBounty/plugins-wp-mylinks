<?php
/**
 * Select2 picker field (the `pw_select` replacement).
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Searchable single-select rendered with the bundled Select2 4.1.0,
 * registered under the `pw_select` type string so the ported Links box
 * definition stays literal. Stores whatever the option value is (a post ID
 * for the link picker), sanitized like CMB2 did: sanitize_text_field.
 *
 * Improvement over the CMB2-era field: the options_cb only returns a page
 * of posts, so a previously saved selection could fall outside the list and
 * silently render as empty. When that happens the saved post is prepended
 * as a selected option, so re-saving a page never loses the selection.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_Field_Select2 extends Wp_Mylinks_Field {

	/**
	 * Render the select.
	 *
	 * @param mixed $value Current value.
	 * @return void
	 */
	protected function render_control( $value ) {
		$value   = (string) $value;
		$options = $this->options();

		if ( '' !== $value && ! isset( $options[ $value ] ) && ctype_digit( $value ) ) {
			$saved_post = get_post( (int) $value );
			if ( $saved_post instanceof WP_Post ) {
				$saved_url = 'mylinks-collection' === $saved_post->post_type
					? (string) get_post_meta( $saved_post->ID, mylinks_collection( 'link_collection' ), true )
					: (string) get_permalink( $saved_post );

				$options = array( $value => get_the_title( $saved_post ) . ' - ' . $saved_url ) + $options;
			}
		}

		printf(
			'<select id="%1$s" name="%2$s" class="wml-select2"%3$s>',
			esc_attr( $this->input_attr_id() ),
			esc_attr( $this->input_name() ),
			$this->attributes_html() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped key-by-key in attributes_html().
		);

		// Select2's placeholder/allowClear need a leading empty option.
		echo '<option value=""></option>';

		foreach ( $options as $option_value => $label ) {
			printf(
				'<option value="%1$s"%3$s>%2$s</option>',
				esc_attr( (string) $option_value ),
				esc_html( (string) $label ),
				selected( $value, (string) $option_value, false )
			);
		}
		echo '</select>';
	}
}
