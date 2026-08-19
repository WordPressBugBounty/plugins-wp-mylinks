<?php
/**
 * Base field for the WP MyLinks native fields framework.
 *
 * Replaces CMB2 field rendering/saving with a plugin-owned implementation.
 * The registration array shape deliberately mirrors CMB2's so the existing
 * metabox definitions port mechanically, and the storage contract is
 * byte-identical (verified by _dev/tests/test-fields-storage-contract.php):
 *
 *   - flat single-value post meta under the field id;
 *   - an empty sanitized value (null / '' / array()) DELETES the meta row
 *     rather than storing an empty one, exactly as CMB2 did;
 *   - save receives slashed data (as from $_POST) and hands the still-slashed
 *     sanitized value to update_post_meta(), which unslashes internally —
 *     unslashing earlier would corrupt values containing backslashes.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Base class: argument normalization, value IO, sanitization dispatch, and
 * the shared row wrapper. Concrete types implement render_control() and may
 * override sanitize_default().
 *
 * @since 1.1.0
 */
abstract class Wp_Mylinks_Field {

	/**
	 * Normalized field arguments. Public like CMB2_Field::$args was — the
	 * options_cb callbacks (e.g. wp_mylinks_get_post_options) read
	 * `$field->args['wp_query_args']` directly.
	 *
	 * @var array
	 */
	public $args;

	/**
	 * HTML allowed in `before_row` and `desc` strings.
	 *
	 * @var array
	 */
	protected static $help_html = array(
		'a'      => array(
			'href'   => array(),
			'target' => array(),
			'rel'    => array(),
		),
		'abbr'   => array( 'title' => array() ),
		'b'      => array(),
		'br'     => array(),
		'code'   => array(),
		'em'     => array(),
		'strong' => array(),
	);

	/**
	 * Set up the field from a CMB2-shaped argument array.
	 *
	 * @param array $args Field arguments. Requires 'id'; everything else optional.
	 */
	public function __construct( array $args ) {
		$this->args = wp_parse_args(
			$args,
			array(
				'id'              => '',
				'name'            => '',
				'desc'            => '',
				'type'            => 'text',
				'default'         => null,
				'attributes'      => array(),
				'options'         => array(),
				'options_cb'      => null,
				'before_row'      => '',
				'protocols'       => null,
				'sanitization_cb' => null,
				'classes'         => '',
				'_name'           => '',
				'_id_attr'        => '',
			)
		);
		// CMB2 accepted 'description' as an alias for 'desc'; keep the ported
		// definitions working either way.
		if ( '' === (string) $this->args['desc'] && ! empty( $this->args['description'] ) ) {
			$this->args['desc'] = (string) $this->args['description'];
		}
	}

	/**
	 * The field id, which is also the meta key.
	 *
	 * @return string
	 */
	public function id() {
		return (string) $this->args['id'];
	}

	/**
	 * One argument by key.
	 *
	 * @param string $key Argument name.
	 * @return mixed Null when unset.
	 */
	public function arg( $key ) {
		return isset( $this->args[ $key ] ) ? $this->args[ $key ] : null;
	}

	/**
	 * The submitted input name. Group subfields override this with the
	 * bracketed group name ("group[0][sub]"); standalone fields use the id.
	 *
	 * @return string
	 */
	public function input_name() {
		return '' !== (string) $this->args['_name'] ? (string) $this->args['_name'] : $this->id();
	}

	/**
	 * The HTML id attribute for the control. Group subfields get a unique
	 * per-row id; standalone fields use the field id.
	 *
	 * @return string
	 */
	public function input_attr_id() {
		return '' !== (string) $this->args['_id_attr'] ? (string) $this->args['_id_attr'] : $this->id();
	}

	/**
	 * Whether this type stores a supporting sibling value inside group rows
	 * (the file type's `{sub}_id`). Mirrors CMB2's has_supporting_data arg.
	 *
	 * @return bool
	 */
	public function has_supporting_data() {
		return false;
	}

	/**
	 * Current value for a post: the stored meta when a row exists, otherwise
	 * the field default (matching CMB2, which only falls back to the default
	 * when nothing was ever saved).
	 *
	 * @param int $post_id Post ID.
	 * @return mixed
	 */
	public function value( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id > 0 && metadata_exists( 'post', $post_id, $this->id() ) ) {
			return get_post_meta( $post_id, $this->id(), true );
		}
		return null !== $this->args['default'] ? $this->args['default'] : '';
	}

	/**
	 * Resolved options for select/radio types: `options_cb` wins over the
	 * static `options` array, mirroring CMB2.
	 *
	 * @return array
	 */
	public function options() {
		if ( is_callable( $this->args['options_cb'] ) ) {
			$options = call_user_func( $this->args['options_cb'], $this );
			return is_array( $options ) ? $options : array();
		}
		return is_array( $this->args['options'] ) ? $this->args['options'] : array();
	}

	/**
	 * Sanitize a raw (slashed) submitted value.
	 *
	 * A field-level `sanitization_cb` overrides the type default and receives
	 * the same (value, field_args, field) signature CMB2 used, so existing
	 * callbacks like wp_mylinks_sanitization_func() keep working unchanged.
	 *
	 * @param mixed $value Raw submitted value (null when absent from the request).
	 * @return mixed Sanitized value.
	 */
	public function sanitize( $value ) {
		if ( is_callable( $this->args['sanitization_cb'] ) ) {
			return call_user_func( $this->args['sanitization_cb'], $value, $this->args, $this );
		}
		return $this->sanitize_default( $value );
	}

	/**
	 * Type-default sanitization. CMB2's fallback for text/select/radio:
	 * sanitize_text_field, mapped over arrays.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	protected function sanitize_default( $value ) {
		return is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : sanitize_text_field( (string) $value );
	}

	/**
	 * Save the field for a post from a slashed data array.
	 *
	 * Mirrors CMB2_Field::save_field(): empty sanitized values delete the
	 * meta row; everything else updates it.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Slashed request data (usually $_POST).
	 * @return void
	 */
	public function save( $post_id, array $data ) {
		$raw   = isset( $data[ $this->id() ] ) ? $data[ $this->id() ] : null;
		$value = $this->sanitize( $raw );

		if ( self::is_empty_value( $value ) ) {
			delete_post_meta( $post_id, $this->id() );
			return;
		}

		update_post_meta( $post_id, $this->id(), $value );
	}

	/**
	 * CMB2_Utils::isempty(), verbatim: null, '', false, and array() are
	 * empty; 0 and '0' are not.
	 *
	 * @param mixed $value Value to check.
	 * @return bool
	 */
	public static function is_empty_value( $value ) {
		return null === $value || '' === $value || false === $value || array() === $value;
	}

	/**
	 * Render the full field row for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function render( $post_id ) {
		$this->render_row( $this->value( $post_id ) );
	}

	/**
	 * Render the full field row for an explicit value: before_row, label,
	 * control, help text. Group rendering calls this directly with row data.
	 *
	 * @param mixed $value Current value.
	 * @return void
	 */
	public function render_row( $value ) {
		if ( '' !== (string) $this->args['before_row'] ) {
			echo '<div class="wml-field__before">' . wp_kses( $this->args['before_row'], self::$help_html ) . '</div>';
		}

		$row_classes = array(
			'wml-field',
			'wml-field--' . sanitize_html_class( str_replace( '_', '-', (string) $this->args['type'] ) ),
		);
		foreach ( preg_split( '/\s+/', (string) $this->args['classes'], -1, PREG_SPLIT_NO_EMPTY ) as $extra_class ) {
			$row_classes[] = sanitize_html_class( $extra_class );
		}
		// data-wml-field carries the field id so admin JS can show or hide a
		// field conditionally (e.g. the Links row-type / card-layout logic).
		echo '<div class="' . esc_attr( implode( ' ', $row_classes ) ) . '" data-wml-field="' . esc_attr( $this->id() ) . '">';

		if ( '' !== (string) $this->args['name'] ) {
			$this->render_label();
		}

		$has_desc = ( '' !== (string) $this->args['desc'] );

		if ( $has_desc && 'before' === $this->desc_position() ) {
			echo '<p class="wml-field__help">' . wp_kses( $this->args['desc'], self::$help_html ) . '</p>';
		}

		$this->render_control( $value );

		if ( $has_desc && 'before' !== $this->desc_position() ) {
			echo '<p class="wml-field__help">' . wp_kses( $this->args['desc'], self::$help_html ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Where the field's desc renders relative to the control. Types whose
	 * control is large (repeaters) put the description above it, where it
	 * reads as an intro instead of trailing the last row.
	 *
	 * @return string 'before' or 'after'.
	 */
	protected function desc_position() {
		return 'after';
	}

	/**
	 * The label element. Types whose control is not a single labelable input
	 * (radio groups) override this with a group label.
	 *
	 * @return void
	 */
	protected function render_label() {
		echo '<label class="wml-field__label" for="' . esc_attr( $this->input_attr_id() ) . '">' . esc_html( $this->args['name'] ) . '</label>';
	}

	/**
	 * Render the input control itself.
	 *
	 * @param mixed $value Current value.
	 * @return void
	 */
	abstract protected function render_control( $value );

	/**
	 * Build an escaped HTML attribute string from the field's `attributes`
	 * array merged with type defaults. Values are escaped here; keys are
	 * constrained to safe attribute-name characters.
	 *
	 * @param array $defaults Type-level attribute defaults (overridden by field args).
	 * @return string Leading-space-prefixed attribute string, or ''.
	 */
	protected function attributes_html( array $defaults = array() ) {
		$attributes = array_merge( $defaults, (array) $this->args['attributes'] );
		$html       = '';
		foreach ( $attributes as $key => $attr_value ) {
			if ( ! preg_match( '/\A[a-zA-Z][a-zA-Z0-9_:-]*\z/', (string) $key ) ) {
				continue;
			}
			if ( false === $attr_value || null === $attr_value ) {
				continue;
			}
			if ( true === $attr_value ) {
				$html .= ' ' . esc_attr( $key );
				continue;
			}
			// CMB2 allowed array class lists; join them.
			if ( is_array( $attr_value ) ) {
				$attr_value = implode( ' ', array_map( 'strval', $attr_value ) );
			}
			$html .= ' ' . esc_attr( $key ) . '="' . esc_attr( (string) $attr_value ) . '"';
		}
		return $html;
	}
}
