<?php
/**
 * File (media) field.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Media-library picker storing the file URL under the field id and the
 * attachment ID under `{id}_id` — CMB2's exact storage shape, including the
 * group form, where the pair lives inside the row array with the `_id` key
 * written first (an artifact of CMB2's save order that serialization makes
 * significant).
 *
 * Field args honored from the CMB2 definitions: `options.url` (false hides
 * the URL text input; the value still posts via a hidden input),
 * `text.add_upload_file_text` / `options.add_upload_file_text` (button
 * label — the Links image field used the latter), `query_args.type` (media
 * library filter), `preview_size`.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_Field_File extends Wp_Mylinks_Field {

	/**
	 * File fields carry a supporting `{id}_id` sibling inside group rows.
	 *
	 * @return bool
	 */
	public function has_supporting_data() {
		return true;
	}

	/**
	 * The media button label from either CMB2 location.
	 *
	 * @return string
	 */
	protected function button_label() {
		if ( ! empty( $this->args['text']['add_upload_file_text'] ) ) {
			return (string) $this->args['text']['add_upload_file_text'];
		}
		if ( ! empty( $this->args['options']['add_upload_file_text'] ) ) {
			return (string) $this->args['options']['add_upload_file_text'];
		}
		return __( 'Add or Upload File', 'wp-mylinks' );
	}

	/**
	 * Render URL input (visible or hidden), id input, buttons, and preview.
	 *
	 * @param mixed $value Current URL value.
	 * @return void
	 */
	protected function render_control( $value ) {
		$url          = (string) $value;
		$id_name      = $this->supporting_input_name();
		$attach_id    = $this->current_attachment_id();
		$show_url     = ! isset( $this->args['options']['url'] ) || false !== $this->args['options']['url'];
		$media_type   = isset( $this->args['query_args']['type'] ) ? (string) $this->args['query_args']['type'] : '';
		$preview_size = ! empty( $this->args['preview_size'] ) ? $this->args['preview_size'] : 'thumbnail';

		echo '<div class="wml-file" data-media-type="' . esc_attr( $media_type ) . '">';

		// Input and buttons sit side by side on one row.
		echo '<div class="wml-file__row">';

		printf(
			'<input type="%1$s" inputmode="url" class="wml-field__input wml-file__url" id="%2$s" name="%3$s" value="%4$s"%5$s>',
			$show_url ? 'text' : 'hidden',
			esc_attr( $this->input_attr_id() ),
			esc_attr( $this->input_name() ),
			esc_attr( $url ),
			$this->attributes_html() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped key-by-key in attributes_html().
		);

		printf(
			'<input type="hidden" class="wml-file__id" name="%1$s" value="%2$s">',
			esc_attr( $id_name ),
			esc_attr( $attach_id > 0 ? (string) $attach_id : '' )
		);

		printf(
			'<button type="button" class="wml-button-secondary wml-file__choose">%s</button>',
			esc_html( $this->button_label() )
		);
		printf(
			'<button type="button" class="wml-button-secondary wml-file__remove"%s aria-label="%s"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>',
			'' === $url ? ' style="display:none"' : '',
			esc_attr__( 'Remove', 'wp-mylinks' )
		);

		echo '</div>';

		echo '<div class="wml-file__preview">';
		if ( $attach_id > 0 ) {
			echo wp_get_attachment_image( $attach_id, $preview_size );
		} elseif ( '' !== $url ) {
			printf(
				'<img src="%s" alt="" style="max-width:150px;height:auto;">',
				esc_url( $url )
			);
		}
		echo '</div>';

		echo '</div>';
	}

	/**
	 * The posted name of the `{id}_id` sibling (bracketed inside groups).
	 *
	 * @return string
	 */
	protected function supporting_input_name() {
		$name = $this->input_name();
		if ( false !== strpos( $name, '[' ) ) {
			// Append the id suffix inside the last bracket segment.
			return preg_replace( '/\]\z/', '_id]', $name );
		}
		return $name . '_id';
	}

	/**
	 * Attachment ID for preview: the stored `{id}_id` sibling for standalone
	 * fields, or the row-provided one for group renders (set via _id_value).
	 *
	 * @return int
	 */
	protected function current_attachment_id() {
		if ( isset( $this->args['_id_value'] ) ) {
			return absint( $this->args['_id_value'] );
		}
		if ( isset( $this->args['_post_id'] ) ) {
			return absint( get_post_meta( (int) $this->args['_post_id'], $this->id() . '_id', true ) );
		}
		return 0;
	}

	/**
	 * Standalone render needs the post id to read the `_id` sibling.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function render( $post_id ) {
		$this->args['_post_id'] = (int) $post_id;
		parent::render( $post_id );
	}

	/**
	 * URL sanitization identical to text_url's (CMB2 routes file values
	 * through text_url()).
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	protected function sanitize_default( $value ) {
		if ( empty( $value ) ) {
			// CMB2's get_default() returns false when unset — see the note in
			// Wp_Mylinks_Field_Text_Url; group rows store this value verbatim.
			return null === $this->args['default'] ? false : $this->args['default'];
		}
		$orig_scheme = wp_parse_url( (string) $value, PHP_URL_SCHEME );
		$value       = esc_url_raw( (string) $value, $this->args['protocols'] );
		if ( null === $orig_scheme ) {
			$value = set_url_scheme( $value, 'https' );
		}
		return $value;
	}

	/**
	 * Standalone save: URL under the field id, attachment ID under `{id}_id`.
	 * Mirrors CMB2_Sanitize::file() / _save_file_id_value(): emptying the URL
	 * removes both rows; a URL without a posted ID is resolved back to an
	 * attachment where possible. The ID passes through absint() — CMB2 stored
	 * the posted value raw here, but only ever posted numeric IDs from its own
	 * hidden input, so real data is unaffected and garbage now dies.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Slashed request data.
	 * @return void
	 */
	public function save( $post_id, array $data ) {
		$raw = isset( $data[ $this->id() ] ) ? $data[ $this->id() ] : null;
		$url = $this->sanitize( $raw );

		$id_key = $this->id() . '_id';
		$id_val = isset( $data[ $id_key ] ) ? absint( $data[ $id_key ] ) : 0;

		if ( self::is_empty_value( $url ) ) {
			delete_post_meta( $post_id, $this->id() );
			delete_post_meta( $post_id, $id_key );
			return;
		}

		if ( 0 === $id_val ) {
			$id_val = (int) attachment_url_to_postid( (string) $url );
		}

		update_post_meta( $post_id, $this->id(), $url );
		if ( $id_val > 0 ) {
			update_post_meta( $post_id, $id_key, $id_val );
		} else {
			delete_post_meta( $post_id, $id_key );
		}
	}

	/**
	 * Group form of the value: CMB2's _get_group_file_value_array() shape,
	 * consumed by the group field's save loop.
	 *
	 * @param mixed $raw_url   Raw URL from the row.
	 * @param mixed $posted_id Raw `{sub}_id` from the row.
	 * @return array{value:mixed,supporting_field_value:mixed,supporting_field_id:string}
	 */
	public function group_value_array( $raw_url, $posted_id ) {
		$id_val = absint( $posted_id );
		return array(
			'value'                  => $this->sanitize( $raw_url ),
			'supporting_field_value' => $id_val > 0 ? $id_val : '',
			'supporting_field_id'    => $this->id() . '_id',
		);
	}
}
