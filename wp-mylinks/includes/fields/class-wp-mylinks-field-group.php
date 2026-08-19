<?php
/**
 * Group (repeater) field.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Sortable repeater storing one serialized array under the group id, with
 * file subfields keeping their `{sub}_id` sibling inside each row — CMB2's
 * exact storage shape.
 *
 * The save() method is a faithful port of CMB2::save_group_field(),
 * including its quirks, because the serialized bytes depend on them:
 *
 *   - a group absent from the request leaves the stored meta untouched;
 *   - the outer loop is per SUBFIELD, the inner per row, so a file
 *     subfield's `{sub}_id` key lands in the row array BEFORE its value key;
 *   - CMB2's per-row empty-filter only ever ran against the last row of
 *     each subfield pass (its loop-variable leak); replicated as-is;
 *   - the final pass drops empty rows but preserves row keys, and the
 *     result is stored even when it is an empty array.
 *
 * Verified byte-identical by _dev/tests/test-fields-storage-contract.php.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_Field_Group extends Wp_Mylinks_Field {

	/**
	 * Subfield definition arrays.
	 *
	 * @return array[]
	 */
	protected function subfield_defs() {
		return isset( $this->args['fields'] ) && is_array( $this->args['fields'] ) ? $this->args['fields'] : array();
	}

	/**
	 * One group option with default.
	 *
	 * @param string $key      Option key.
	 * @param mixed  $fallback Default.
	 * @return mixed
	 */
	protected function group_option( $key, $fallback = '' ) {
		return isset( $this->args['options'][ $key ] ) ? $this->args['options'][ $key ] : $fallback;
	}

	/**
	 * CMB2_Utils::filter_empty(): drop null / '' / false / array() values,
	 * preserving keys. 0 and '0' survive.
	 *
	 * @param array $values Values.
	 * @return array
	 */
	protected static function filter_empty( array $values ) {
		return array_filter(
			$values,
			static function ( $value ) {
				return ! Wp_Mylinks_Field::is_empty_value( $value );
			}
		);
	}

	/**
	 * Save the group — the ported CMB2 algorithm (see class docblock).
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Slashed request data.
	 * @return void
	 */
	public function save( $post_id, array $data ) {
		if ( ! isset( $data[ $this->id() ] ) ) {
			return;
		}

		$group_vals = $this->args['sanitization_cb'] && is_callable( $this->args['sanitization_cb'] )
			? call_user_func( $this->args['sanitization_cb'], $data[ $this->id() ], $this->args, $this )
			: $data[ $this->id() ];
		$group_vals = is_array( $group_vals ) ? $group_vals : array();

		$saved      = array();
		$last_index = null;

		foreach ( $this->subfield_defs() as $field_args ) {
			$sub = Wp_Mylinks_Metabox::make_field( (array) $field_args );
			if ( ! $sub || '' === $sub->id() ) {
				continue;
			}
			$sub_id = $sub->id();

			foreach ( $group_vals as $row_index => $row_vals ) {
				$raw = isset( $group_vals[ $row_index ][ $sub_id ] ) ? $group_vals[ $row_index ][ $sub_id ] : false;

				if ( $sub->has_supporting_data() && $sub instanceof Wp_Mylinks_Field_File ) {
					$posted_id = isset( $group_vals[ $row_index ][ $sub_id . '_id' ] )
						? $group_vals[ $row_index ][ $sub_id . '_id' ]
						: '';
					$val_array = $sub->group_value_array( $raw, $posted_id );

					$saved[ $row_index ][ $val_array['supporting_field_id'] ] = $val_array['supporting_field_value'];
					$new_val = $val_array['value'];
				} else {
					$new_val = $sub->sanitize( $raw );
				}

				$saved[ $row_index ][ $sub_id ] = $new_val;
				$last_index                     = $row_index;
			}

			// CMB2 quirk preserved: only the last-processed row is filtered
			// after each subfield pass.
			if ( null !== $last_index && isset( $saved[ $last_index ] ) ) {
				$saved[ $last_index ] = self::filter_empty( $saved[ $last_index ] );
			}
		}

		$saved = self::filter_empty( $saved );

		// Verified against CMB2: a group whose rows all filtered away DELETES
		// the meta row rather than storing an empty array.
		if ( array() === $saved ) {
			delete_post_meta( $post_id, $this->id() );
			return;
		}

		update_post_meta( $post_id, $this->id(), $saved );
	}

	/**
	 * The group's description introduces the rows rather than trailing them.
	 *
	 * @return string
	 */
	protected function desc_position() {
		return 'before';
	}

	/**
	 * Render all rows plus the add button and the JS row template.
	 *
	 * @param mixed $value Stored group array.
	 * @return void
	 */
	protected function render_control( $value ) {
		$rows     = is_array( $value ) ? array_values( $value ) : array();
		$sortable = (bool) $this->group_option( 'sortable', false );

		printf(
			'<div class="wml-group" data-group-id="%1$s" data-title-template="%2$s" data-remove-confirm="%3$s"%4$s>',
			esc_attr( $this->id() ),
			esc_attr( (string) $this->group_option( 'group_title', '{#}' ) ),
			esc_attr( (string) $this->group_option( 'remove_confirm', '' ) ),
			$sortable ? ' data-sortable="1"' : ''
		);

		echo '<div class="wml-group__rows">';
		if ( empty( $rows ) ) {
			$this->render_group_row( 0, array(), $sortable );
		} else {
			foreach ( $rows as $index => $row ) {
				$this->render_group_row( $index, is_array( $row ) ? $row : array(), $sortable );
			}
		}
		echo '</div>';

		printf(
			'<p><button type="button" class="wml-button-secondary wml-group__add">%s</button></p>',
			esc_html( (string) $this->group_option( 'add_button', __( 'Add Row', 'wp-mylinks' ) ) )
		);

		// Inert row template for the add button; JS swaps the index tokens.
		echo '<script type="text/template" class="wml-group__row-template">';
		$this->render_group_row( '__wmlidx__', array(), $sortable, '__wmlnum__' );
		echo '</script>';

		echo '</div>';
	}

	/**
	 * Render one repeater row.
	 *
	 * @param int|string $index       Row index (or the JS placeholder token).
	 * @param array      $row         Row values.
	 * @param bool       $sortable    Whether rows are drag-sortable.
	 * @param string     $num_display Display number override (JS placeholder).
	 * @return void
	 */
	protected function render_group_row( $index, array $row, $sortable, $num_display = '' ) {
		$number = '' !== $num_display ? $num_display : (string) ( (int) $index + 1 );
		$title  = str_replace( '{#}', $number, (string) $this->group_option( 'group_title', '{#}' ) );

		echo '<div class="wml-group__row">';
		echo '<div class="wml-group__row-head">';
		if ( $sortable ) {
			echo '<span class="wml-group__handle dashicons dashicons-menu" aria-hidden="true"></span>';
		}
		echo '<h4 class="wml-group__title">' . esc_html( $title ) . '</h4>';
		printf(
			'<button type="button" class="wml-group__collapse" aria-expanded="true"><span class="screen-reader-text">%s</span><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>',
			esc_html__( 'Collapse row', 'wp-mylinks' )
		);
		printf(
			'<button type="button" class="wml-button-secondary wml-group__row-remove">%s</button>',
			esc_html( (string) $this->group_option( 'remove_button', __( 'Remove', 'wp-mylinks' ) ) )
		);
		echo '</div>';

		echo '<div class="wml-group__row-body">';
		foreach ( $this->subfield_defs() as $field_args ) {
			$field_args = (array) $field_args;
			if ( empty( $field_args['id'] ) ) {
				continue;
			}
			$sub_id = (string) $field_args['id'];

			$field_args['_name']    = $this->id() . '[' . $index . '][' . $sub_id . ']';
			$field_args['_id_attr'] = $this->id() . '_' . $index . '_' . $sub_id;

			$sub = Wp_Mylinks_Metabox::make_field( $field_args );
			if ( ! $sub ) {
				continue;
			}

			if ( $sub instanceof Wp_Mylinks_Field_File ) {
				$sub->args['_id_value'] = isset( $row[ $sub_id . '_id' ] ) ? $row[ $sub_id . '_id' ] : 0;
			}

			$sub_value = isset( $row[ $sub_id ] ) ? $row[ $sub_id ] : ( null !== $sub->arg( 'default' ) ? $sub->arg( 'default' ) : '' );
			$sub->render_row( $sub_value );
		}
		echo '</div>';
		echo '</div>';
	}
}
