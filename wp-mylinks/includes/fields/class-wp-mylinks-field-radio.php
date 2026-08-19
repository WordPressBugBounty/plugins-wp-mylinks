<?php
/**
 * Radio field (stacked and inline).
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Radio group; registered for both the 'radio' and 'radio_inline' types
 * (the type string decides the layout class). Handles ''-keyed options —
 * the three-way Inherit / Yes / No pattern — correctly: an empty stored
 * value checks the '' option, and saving '' deletes the meta row, which is
 * exactly the CMB2 "inherit" behavior.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_Field_Radio extends Wp_Mylinks_Field {

	/**
	 * A radio group has no single labelable control; render the name as a
	 * group label tied to the fieldset instead.
	 *
	 * @return void
	 */
	protected function render_label() {
		printf(
			'<span class="wml-field__label" id="%1$s-label">%2$s</span>',
			esc_attr( $this->input_attr_id() ),
			esc_html( $this->args['name'] )
		);
	}

	/**
	 * Render the radio options.
	 *
	 * @param mixed $value Current value.
	 * @return void
	 */
	protected function render_control( $value ) {
		printf(
			'<fieldset class="wml-field__radios" aria-labelledby="%s-label">',
			esc_attr( $this->input_attr_id() )
		);
		$index = 0;
		foreach ( $this->options() as $option_value => $label ) {
			$input_id = $this->input_attr_id() . '-' . $index;
			++$index;
			printf(
				'<label class="wml-field__radio" for="%1$s"><input type="radio" id="%1$s" name="%2$s" value="%3$s"%5$s> %4$s</label>',
				esc_attr( $input_id ),
				esc_attr( $this->input_name() ),
				esc_attr( (string) $option_value ),
				esc_html( (string) $label ),
				checked( (string) $value, (string) $option_value, false )
			);
		}
		echo '</fieldset>';
	}
}
