<?php
/**
 * Metabox registrar for the WP MyLinks native fields framework.
 *
 * Boxes are registered as CMB2-shaped argument arrays and rendered/saved by
 * this class: one add_meta_boxes hookup, one save_post hookup, a nonce per
 * box, and a save core that is callable without HTTP so the storage-contract
 * test harness can drive it directly.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Collects box definitions and wires them into WordPress.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_Metabox {

	/**
	 * Registered box definitions, keyed by box id.
	 *
	 * @var array<string,array>
	 */
	protected static $boxes = array();

	/**
	 * Whether the WordPress hooks have been attached.
	 *
	 * @var bool
	 */
	protected static $hooked = false;

	/**
	 * Register a metabox definition.
	 *
	 * @param array $args {
	 *     Box arguments, mirroring CMB2's shape.
	 *
	 *     @type string   $id           Unique box id. Required.
	 *     @type string   $title        Box title (translated by the caller).
	 *     @type string[] $object_types Post types the box appears on.
	 *     @type string   $context      Metabox context. Default 'normal'.
	 *     @type string   $priority     Metabox priority. Default 'high'.
	 *     @type array[]  $fields       Field argument arrays (see Wp_Mylinks_Field).
	 * }
	 * @return void
	 */
	public static function register( array $args ) {
		if ( empty( $args['id'] ) ) {
			return;
		}
		self::$boxes[ (string) $args['id'] ] = wp_parse_args(
			$args,
			array(
				'title'        => '',
				'description'  => '',
				'object_types' => array( 'mylink' ),
				'context'      => 'normal',
				'priority'     => 'high',
				'fields'       => array(),
			)
		);
		self::hook();
	}

	/**
	 * Attach the WordPress hooks once.
	 *
	 * @return void
	 */
	protected static function hook() {
		if ( self::$hooked ) {
			return;
		}
		self::$hooked = true;
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 10, 1 );
		add_action( 'save_post', array( __CLASS__, 'handle_save' ), 10, 2 );
	}

	/**
	 * All registered boxes, filtered.
	 *
	 * @return array<string,array>
	 */
	public static function boxes() {
		/**
		 * Filter the registered WP MyLinks metabox definitions.
		 *
		 * This is the public registration surface for extensions (and the
		 * future Pro plugin): add, remove, or amend box argument arrays. Each
		 * entry is keyed by box id and holds the array shape documented on
		 * Wp_Mylinks_Metabox::register().
		 *
		 * @since 1.1.0
		 *
		 * @param array<string,array> $boxes Box definitions keyed by id.
		 */
		$boxes = apply_filters( 'wp_mylinks_meta_boxes', self::$boxes );
		return is_array( $boxes ) ? $boxes : array();
	}

	/**
	 * Map a field type string to its class.
	 *
	 * @param array $field_args Field arguments (needs 'type').
	 * @return Wp_Mylinks_Field|null Field instance, or null for unknown types.
	 */
	public static function make_field( array $field_args ) {
		$map = array(
			'text'           => 'Wp_Mylinks_Field_Text',
			'text_url'       => 'Wp_Mylinks_Field_Text_Url',
			'textarea_small' => 'Wp_Mylinks_Field_Textarea_Small',
			'select'         => 'Wp_Mylinks_Field_Select',
			'radio'          => 'Wp_Mylinks_Field_Radio',
			'radio_inline'   => 'Wp_Mylinks_Field_Radio',
			'wysiwyg'        => 'Wp_Mylinks_Field_Wysiwyg',
			'file'           => 'Wp_Mylinks_Field_File',
			'oembed'         => 'Wp_Mylinks_Field_Oembed',
			'group'          => 'Wp_Mylinks_Field_Group',
			'pw_select'      => 'Wp_Mylinks_Field_Select2',
			'colorpicker'    => 'Wp_Mylinks_Field_Color',
		);

		/**
		 * Filter the field type → class map.
		 *
		 * Lets extensions register additional field types. Classes must
		 * extend Wp_Mylinks_Field.
		 *
		 * @since 1.1.0
		 *
		 * @param array<string,string> $map Type string to class name.
		 */
		$map = apply_filters( 'wp_mylinks_field_types', $map );

		$type = isset( $field_args['type'] ) ? (string) $field_args['type'] : 'text';
		if ( ! isset( $map[ $type ] ) || ! is_subclass_of( $map[ $type ], 'Wp_Mylinks_Field' ) ) {
			return null;
		}
		$class = $map[ $type ];
		return new $class( $field_args );
	}

	/**
	 * Register the metaboxes with WordPress for the current post type.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public static function add_meta_boxes( $post_type ) {
		foreach ( self::boxes() as $box_id => $box ) {
			if ( ! in_array( $post_type, (array) $box['object_types'], true ) ) {
				continue;
			}
			add_meta_box(
				$box_id,
				$box['title'],
				array( __CLASS__, 'render_box' ),
				$post_type,
				$box['context'],
				$box['priority'],
				array( 'wml_box_id' => $box_id )
			);
		}
	}

	/**
	 * Render one box: nonce plus every field row.
	 *
	 * @param WP_Post $post     Post being edited.
	 * @param array   $box_meta Metabox callback args from add_meta_box().
	 * @return void
	 */
	public static function render_box( $post, $box_meta ) {
		$box_id = isset( $box_meta['args']['wml_box_id'] ) ? (string) $box_meta['args']['wml_box_id'] : '';
		$boxes  = self::boxes();
		if ( '' === $box_id || ! isset( $boxes[ $box_id ] ) ) {
			return;
		}

		wp_nonce_field( 'wp_mylinks_save_' . $box_id, 'wp_mylinks_' . $box_id . '_nonce' );

		if ( ! empty( $boxes[ $box_id ]['description'] ) ) {
			echo '<p class="wml-card-description">' . esc_html( $boxes[ $box_id ]['description'] ) . '</p>';
		}

		echo '<div class="wml-fields">';
		foreach ( $boxes[ $box_id ]['fields'] as $field_args ) {
			$field = self::make_field( (array) $field_args );
			if ( $field ) {
				$field->render( (int) $post->ID );
			}
		}
		echo '</div>';
	}

	/**
	 * save_post handler: guards, then per-box nonce check, then the save core.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public static function handle_save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( self::boxes() as $box_id => $box ) {
			if ( ! in_array( $post->post_type, (array) $box['object_types'], true ) ) {
				continue;
			}

			$nonce_key = 'wp_mylinks_' . $box_id . '_nonce';
			if ( ! isset( $_POST[ $nonce_key ] ) ) {
				// Box not present in this request (e.g. Quick Edit) — skip it.
				continue;
			}
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ $nonce_key ] ) ), 'wp_mylinks_save_' . $box_id ) ) {
				continue;
			}

			// Slash contract: fields sanitize the slashed values and pass them
			// still-slashed to update_post_meta(), which unslashes internally —
			// the same pipeline CMB2 used, byte-for-byte (see the contract test).
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			self::save_box_fields( $box, $post_id, $_POST );
		}
	}

	/**
	 * The save core: sanitize and persist every field of one box from a
	 * slashed data array. No nonce or capability logic here — the caller
	 * (handle_save, or the test harness) owns that.
	 *
	 * @param array $box     Box definition.
	 * @param int   $post_id Post ID.
	 * @param array $data    Slashed request data.
	 * @return void
	 */
	public static function save_box_fields( array $box, $post_id, array $data ) {
		foreach ( (array) $box['fields'] as $field_args ) {
			$field = self::make_field( (array) $field_args );
			if ( $field && '' !== $field->id() ) {
				$field->save( (int) $post_id, $data );
			}
		}
	}
}
