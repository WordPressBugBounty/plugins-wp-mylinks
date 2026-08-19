<?php
/**
 * Tools: settings and pages import/export.
 *
 * One JSON format for three payloads — plugin settings, a set of MyLink
 * pages, or a single page. Exports are plain file downloads; imports are
 * admin-initiated uploads gated by capability + nonce. Media files are never
 * bundled: image fields travel as URLs (which keep working when importing
 * into the same site or any site that can reach them) plus attachment IDs
 * (which only resolve on the site they came from).
 *
 * @package    Wp_Mylinks
 * @since      1.1.0
 * @link       https://walterpinem.me/
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Import/export handlers, the editor side metabox, and the Tools tab helpers.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_Tools {

	/**
	 * Post types the pages exporter handles.
	 *
	 * @var string[]
	 */
	const POST_TYPES = array( 'mylink', 'mylinks-collection' );

	/**
	 * Hook everything in.
	 *
	 * @since 1.1.0
	 */
	public static function init() {
		add_action('admin_post_wp_mylinks_export_settings', array( __CLASS__, 'handle_export_settings' ));
		add_action('admin_post_wp_mylinks_export_pages', array( __CLASS__, 'handle_export_pages' ));
		add_action('admin_post_wp_mylinks_export_page', array( __CLASS__, 'handle_export_page' ));
		add_action('admin_post_wp_mylinks_import', array( __CLASS__, 'handle_import' ));
		add_action('wp_ajax_wp_mylinks_import_page', array( __CLASS__, 'handle_import_page_ajax' ));
		add_action('add_meta_boxes', array( __CLASS__, 'register_meta_box' ));
		add_action('admin_notices', array( __CLASS__, 'import_notices' ));
	}

	// -------------------------------------------------------------------------
	// Payload builders.
	// -------------------------------------------------------------------------

	/**
	 * The exportable/importable option names with their sanitizers.
	 *
	 * Mirrors the register_setting() calls (admin settings) — the environment
	 * stamps (version, installed_at) deliberately stay out: they describe this
	 * install, not the configuration.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string,callable>
	 */
	public static function settings_map() {
		return array(
			'mylinks_theme'                 => 'sanitize_key',
			'mylinks_meta_title'            => 'sanitize_text_field',
			'mylinks_meta_description'      => 'sanitize_textarea_field',
			'mylinks_upload_favicon'        => 'esc_url_raw',
			'wp_mylinks_nofollow'           => 'wp_mylinks_sanitize_yes_or_empty',
			'wp_mylinks_noindex'            => 'wp_mylinks_sanitize_yes_or_empty',
			'wp_mylinks_credits'            => 'wp_mylinks_sanitize_yes_or_empty',
			'wp_mylinks_hide_notice'        => 'wp_mylinks_sanitize_yes_or_empty',
			// wp_mylinks_delete_data_on_uninstall is deliberately NOT here: it is a
			// destructive, install-local preference. Porting it via an imported file
			// could silently arm data deletion on the target site.
			'wp_mylinks_accent_bg'          => 'wp_mylinks_sanitize_hex',
			'wp_mylinks_accent_button_bg'   => 'wp_mylinks_sanitize_hex',
			'wp_mylinks_accent_button_text' => 'wp_mylinks_sanitize_hex',
			'wp_mylinks_accent_text'        => 'wp_mylinks_sanitize_hex',
			'wp_mylinks_enable_schema'      => 'wp_mylinks_sanitize_yes_or_empty',
			'wp_mylinks_schema_type'        => 'wp_mylinks_sanitize_schema_type',
			'wp_mylinks_enable_profilepage' => 'wp_mylinks_sanitize_yes_or_empty',
			'wp_mylinks_enable_og'          => 'wp_mylinks_sanitize_yes_or_empty',
			'wp_mylinks_og_image'           => 'esc_url_raw',
			'wp_mylinks_twitter_handle'     => 'wp_mylinks_sanitize_twitter_handle',
			'wp_mylinks_analytics'          => 'wp_mylinks_sanitize_raw_script',
			'wp_mylinks_header_script'      => 'wp_mylinks_sanitize_raw_script',
			'wp_mylinks_open_body_script'   => 'wp_mylinks_sanitize_raw_script',
			'wp_mylinks_footer_script'      => 'wp_mylinks_sanitize_raw_script',
			'wp_mylinks_custom_css'         => 'wp_mylinks_sanitize_raw_script',
			'wp_mylinks_dequeue'            => 'wp_mylinks_sanitize_yes_or_empty',
			// wp_mylinks_link_post_types is deliberately NOT here: it lists
			// post-type slugs that are specific to this install's registered
			// CPTs, so porting it to another site is meaningless.
		);
	}

	/**
	 * Envelope shared by every export.
	 *
	 * @since 1.1.0
	 *
	 * @param string $type Payload type: settings|pages|page.
	 * @return array
	 */
	protected static function envelope( $type ) {
		return array(
			'plugin'      => 'wp-mylinks',
			'version'     => defined('WP_MYLINKS_VERSION') ? WP_MYLINKS_VERSION : '',
			'type'        => $type,
			'site'        => home_url(),
			'exported_at' => gmdate('Y-m-d H:i:s'),
		);
	}

	/**
	 * Serialize one post to its portable shape.
	 *
	 * @since 1.1.0
	 *
	 * @param WP_Post $post The post.
	 * @return array
	 */
	public static function serialize_post( $post ) {
		$meta = array();
		foreach ( get_post_meta($post->ID) as $key => $values ) {
			if ( ! self::is_plugin_meta_key($key) ) {
				continue;
			}
			$meta[ $key ] = maybe_unserialize($values[0]);
		}

		return array(
			'title'   => $post->post_title,
			'slug'    => $post->post_name,
			'status'  => $post->post_status,
			'type'    => $post->post_type,
			'content' => $post->post_content,
			'meta'    => $meta,
		);
	}

	/**
	 * Whether a meta key belongs to this plugin.
	 *
	 * @since 1.1.0
	 *
	 * @param string $key Meta key.
	 * @return bool
	 */
	protected static function is_plugin_meta_key( $key ) {
		return 0 === strpos($key, 'mylinks_') || 0 === strpos($key, 'wp_mylinks_');
	}

	// -------------------------------------------------------------------------
	// Export handlers.
	// -------------------------------------------------------------------------

	/**
	 * Download the plugin settings as JSON.
	 *
	 * @since 1.1.0
	 */
	public static function handle_export_settings() {
		if ( ! current_user_can('manage_options') ) {
			wp_die(esc_html__('You are not allowed to export WP MyLinks settings.', 'wp-mylinks'));
		}
		check_admin_referer('wp_mylinks_export');

		$settings = array();
		foreach ( array_keys(self::settings_map()) as $option ) {
			$value = get_option($option, null);
			if ( null !== $value && false !== $value ) {
				$settings[ $option ] = $value;
			}
		}

		$payload             = self::envelope('settings');
		$payload['settings'] = $settings;

		self::send_json_download($payload, 'wp-mylinks-settings-' . gmdate('Ymd') . '.json');
	}

	/**
	 * Download MyLink pages (optionally Collections) as JSON.
	 *
	 * @since 1.1.0
	 */
	public static function handle_export_pages() {
		if ( ! current_user_can('manage_options') ) {
			wp_die(esc_html__('You are not allowed to export MyLink pages.', 'wp-mylinks'));
		}
		check_admin_referer('wp_mylinks_export');

		$status  = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'any';
		$allowed = array( 'any', 'publish', 'draft', 'pending', 'private' );
		if ( ! in_array($status, $allowed, true) ) {
			$status = 'any';
		}

		$types = self::POST_TYPES;
		if ( empty($_GET['include_collections']) ) {
			$types = array( 'mylink' );
		}

		$posts = get_posts(
			array(
				'post_type'      => $types,
				'post_status'    => 'any' === $status ? array( 'publish', 'draft', 'pending', 'private' ) : $status,
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		$payload          = self::envelope('pages');
		$payload['pages'] = array_map(array( __CLASS__, 'serialize_post' ), $posts);

		self::send_json_download($payload, 'wp-mylinks-pages-' . gmdate('Ymd') . '.json');
	}

	/**
	 * Download a single page as JSON.
	 *
	 * @since 1.1.0
	 */
	public static function handle_export_page() {
		$post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
		$post    = $post_id ? get_post($post_id) : null;

		if ( ! $post || ! in_array($post->post_type, self::POST_TYPES, true) ) {
			wp_die(esc_html__('That MyLink page could not be found.', 'wp-mylinks'));
		}
		if ( ! current_user_can('edit_post', $post_id) ) {
			wp_die(esc_html__('You are not allowed to export this page.', 'wp-mylinks'));
		}
		check_admin_referer('wp_mylinks_export_page_' . $post_id);

		$payload          = self::envelope('page');
		$payload['pages'] = array( self::serialize_post($post) );

		self::send_json_download($payload, 'wp-mylinks-' . ( $post->post_name ? $post->post_name : $post->ID ) . '-' . gmdate('Ymd') . '.json');
	}

	/**
	 * Emit a JSON download and exit.
	 *
	 * @since 1.1.0
	 *
	 * @param array  $payload  Data to encode.
	 * @param string $filename Download filename.
	 */
	protected static function send_json_download( $payload, $filename ) {
		nocache_headers();
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $filename);
		echo wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON file download, not HTML.
		exit;
	}

	// -------------------------------------------------------------------------
	// Import.
	// -------------------------------------------------------------------------

	/**
	 * Read and validate an uploaded export file.
	 *
	 * @since 1.1.0
	 *
	 * @return array|WP_Error Decoded payload, or the reason it was rejected.
	 */
	protected static function read_upload() {
		if ( empty($_FILES['wp_mylinks_import_file']['tmp_name']) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by callers.
			return new WP_Error('no_file', __('No file was uploaded.', 'wp-mylinks'));
		}

		$file = $_FILES['wp_mylinks_import_file']; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- tmp_name is server-generated; contents are JSON-decoded and validated below.

		// Only trust a genuine PHP upload, and measure the real file — not the
		// client-supplied 'size' key, which is spoofable and absent on 0-byte
		// posts.
		if ( ! is_uploaded_file($file['tmp_name']) ) {
			return new WP_Error('no_file', __('No file was uploaded.', 'wp-mylinks'));
		}
		if ( (int) filesize($file['tmp_name']) > 8 * MB_IN_BYTES ) {
			return new WP_Error('too_large', __('The file is larger than 8 MB — that is not one of our exports.', 'wp-mylinks'));
		}

		$json = file_get_contents($file['tmp_name']); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading an uploaded tmp file.
		// Cap nesting depth so a crafted deep document cannot exhaust memory;
		// our own exports never nest beyond a few levels.
		$data = json_decode( (string) $json, true, 64);

		if ( ! is_array($data) || ! isset($data['plugin'], $data['type']) || 'wp-mylinks' !== $data['plugin'] ) {
			return new WP_Error('not_ours', __('This is not a WP MyLinks export file.', 'wp-mylinks'));
		}

		return $data;
	}

	/**
	 * Handle the Tools-tab import upload (settings or pages).
	 *
	 * @since 1.1.0
	 */
	public static function handle_import() {
		if ( ! current_user_can('manage_options') ) {
			wp_die(esc_html__('You are not allowed to import WP MyLinks data.', 'wp-mylinks'));
		}
		check_admin_referer('wp_mylinks_import');

		$data = self::read_upload();
		if ( is_wp_error($data) ) {
			self::redirect_back(array( 'wml-import-error' => $data->get_error_code() ));
		}

		if ( 'settings' === $data['type'] ) {
			$count = self::import_settings(isset($data['settings']) ? (array) $data['settings'] : array());
			self::redirect_back(
				array(
					'wml-import' => 'settings',
					'wml-count'  => $count,
				)
			);
		}

		if ( in_array($data['type'], array( 'pages', 'page' ), true) ) {
			$count = 0;
			foreach ( ( isset($data['pages']) ? (array) $data['pages'] : array() ) as $page ) {
				if ( self::import_page_as_new( (array) $page) ) {
					++$count;
				}
			}
			self::redirect_back(
				array(
					'wml-import' => 'pages',
					'wml-count'  => $count,
				)
			);
		}

		self::redirect_back(array( 'wml-import-error' => 'unknown_type' ));
	}

	/**
	 * Apply exported settings through each option's own sanitizer.
	 *
	 * @since 1.1.0
	 *
	 * @param array $settings Option name => value.
	 * @return int Options applied.
	 */
	protected static function import_settings( array $settings ) {
		$map   = self::settings_map();
		$kses  = ! current_user_can('unfiltered_html');
		$count = 0;

		foreach ( $map as $option => $sanitize ) {
			if ( ! array_key_exists($option, $settings) ) {
				continue;
			}
			$value = $settings[ $option ];
			if ( ! is_scalar($value) ) {
				continue;
			}
			$value = call_user_func($sanitize, (string) $value);
			if ( $kses && is_string($value) ) {
				$value = wp_kses_post($value);
			}
			update_option($option, $value);
			++$count;
		}

		return $count;
	}

	/**
	 * Import one serialized page as a new post.
	 *
	 * Always creates a new post — WordPress suffixes the slug if it is taken,
	 * so an import can never overwrite an existing page by accident.
	 *
	 * @since 1.1.0
	 *
	 * @param array $page Serialized page.
	 * @return int New post ID, or 0 on failure.
	 */
	protected static function import_page_as_new( array $page ) {
		$type = isset($page['type']) ? (string) $page['type'] : 'mylink';
		if ( ! in_array($type, self::POST_TYPES, true) ) {
			return 0;
		}

		$status  = isset($page['status']) ? (string) $page['status'] : 'draft';
		$allowed = array( 'publish', 'draft', 'pending', 'private' );
		if ( ! in_array($status, $allowed, true) ) {
			$status = 'draft';
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => $type,
				'post_status'  => $status,
				'post_title'   => isset($page['title']) ? sanitize_text_field( (string) $page['title']) : '',
				'post_name'    => isset($page['slug']) ? sanitize_title( (string) $page['slug']) : '',
				'post_content' => isset($page['content']) ? (string) $page['content'] : '',
			),
			false
		);

		if ( ! $post_id || is_wp_error($post_id) ) {
			return 0;
		}

		self::apply_meta($post_id, isset($page['meta']) ? (array) $page['meta'] : array());

		return (int) $post_id;
	}

	/**
	 * Write plugin meta onto a post.
	 *
	 * @since 1.1.0
	 *
	 * @param int   $post_id Target post.
	 * @param array $meta    Meta key => value (portable shape).
	 */
	protected static function apply_meta( $post_id, array $meta ) {
		$kses = ! current_user_can('unfiltered_html');

		foreach ( $meta as $key => $value ) {
			if ( ! is_string($key) || ! self::is_plugin_meta_key($key) ) {
				continue;
			}
			if ( $kses ) {
				$value = self::kses_deep($value);
			}
			// The JSON-decoded value is already unslashed, but update_post_meta()
			// unslashes again internally; re-slash so literal backslashes in the
			// imported value (custom CSS, regexes, paths) survive the round-trip.
			update_post_meta($post_id, $key, wp_slash($value));
		}
	}

	/**
	 * wp_kses_post over strings, recursively through arrays.
	 *
	 * @since 1.1.0
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	protected static function kses_deep( $value ) {
		if ( is_string($value) ) {
			return wp_kses_post($value);
		}
		if ( is_array($value) ) {
			return array_map(array( __CLASS__, 'kses_deep' ), $value);
		}
		return $value;
	}

	/**
	 * AJAX: import a single-page export INTO the page being edited.
	 *
	 * Replaces the page's plugin meta with the file's; the title, slug, and
	 * status of the target page are left alone ("configuration transplant").
	 *
	 * @since 1.1.0
	 */
	public static function handle_import_page_ajax() {
		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

		if ( ! $post_id || ! current_user_can('edit_post', $post_id) ) {
			wp_send_json_error(array( 'message' => __('You are not allowed to import into this page.', 'wp-mylinks') ), 403);
		}
		// Confine the transplant to the post types this feature owns — an
		// editor can hold edit_post on ordinary Pages too.
		if ( ! in_array(get_post_type($post_id), self::POST_TYPES, true) ) {
			wp_send_json_error(array( 'message' => __('That page is not a MyLink.', 'wp-mylinks') ), 400);
		}
		check_ajax_referer('wp_mylinks_import_page_' . $post_id);

		$data = self::read_upload();
		if ( is_wp_error($data) ) {
			wp_send_json_error(array( 'message' => $data->get_error_message() ), 400);
		}

		$pages = isset($data['pages']) ? (array) $data['pages'] : array();
		$page  = isset($pages[0]) ? (array) $pages[0] : array();
		$meta  = ( isset($page['meta']) && is_array($page['meta']) ) ? $page['meta'] : array();

		// The file's exported page type must match the target — never transplant
		// a Collection export onto a link page (or vice versa).
		if ( isset($page['type']) && get_post_type($post_id) !== $page['type'] ) {
			wp_send_json_error(array( 'message' => __('This export is for a different kind of page.', 'wp-mylinks') ), 400);
		}

		// Keep only this plugin's keys; refuse to proceed if there is nothing
		// applicable, so a malformed-but-valid file cannot wipe the page's
		// configuration via the clear step below.
		$applicable = array();
		foreach ( $meta as $key => $value ) {
			if ( is_string($key) && self::is_plugin_meta_key($key) ) {
				$applicable[ $key ] = $value;
			}
		}
		if ( ! in_array($data['type'], array( 'page', 'pages' ), true) || empty($applicable) ) {
			wp_send_json_error(array( 'message' => __('This file does not contain a MyLink page export.', 'wp-mylinks') ), 400);
		}

		// Clear existing plugin meta first so the result matches the file
		// exactly rather than merging two configurations.
		foreach ( array_keys(get_post_meta($post_id)) as $key ) {
			if ( self::is_plugin_meta_key($key) ) {
				delete_post_meta($post_id, $key);
			}
		}
		self::apply_meta($post_id, $applicable);

		wp_send_json_success(array( 'message' => __('Page configuration imported.', 'wp-mylinks') ));
	}

	/**
	 * Redirect back to the Tools tab with result args.
	 *
	 * @since 1.1.0
	 *
	 * @param array $args Query args to add.
	 */
	protected static function redirect_back( array $args ) {
		$url = add_query_arg($args, admin_url('edit.php?post_type=mylink&page=welcome&tab=tools'));
		wp_safe_redirect($url);
		exit;
	}

	/**
	 * Result notices on the settings screen after an import redirect.
	 *
	 * @since 1.1.0
	 */
	public static function import_notices() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only notice state set by our own redirect.
		if ( isset($_GET['wml-import']) ) {
			$what  = sanitize_key(wp_unslash($_GET['wml-import']));
			$count = isset($_GET['wml-count']) ? absint($_GET['wml-count']) : 0;
			if ( 'settings' === $what ) {
				/* translators: %d: number of settings. */
				$message = sprintf(_n('%d setting imported.', '%d settings imported.', $count, 'wp-mylinks'), $count);
			} else {
				/* translators: %d: number of pages. */
				$message = sprintf(_n('%d page imported.', '%d pages imported.', $count, 'wp-mylinks'), $count);
			}
			printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($message));
		}

		if ( isset($_GET['wml-import-error']) ) {
			$code     = sanitize_key(wp_unslash($_GET['wml-import-error']));
			$messages = array(
				'no_file'      => __('No file was uploaded.', 'wp-mylinks'),
				'too_large'    => __('The file is larger than 8 MB — that is not one of our exports.', 'wp-mylinks'),
				'not_ours'     => __('This is not a WP MyLinks export file.', 'wp-mylinks'),
				'unknown_type' => __('This export file has an unknown type.', 'wp-mylinks'),
			);
			$message  = isset($messages[ $code ]) ? $messages[ $code ] : __('The import failed.', 'wp-mylinks');
			printf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html($message));
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	// -------------------------------------------------------------------------
	// Editor side metabox.
	// -------------------------------------------------------------------------

	/**
	 * Register the Import/Export side box on the MyLink editor.
	 *
	 * @since 1.1.0
	 */
	public static function register_meta_box() {
		add_meta_box(
			'wp_mylinks_import_export',
			__('Import / Export', 'wp-mylinks'),
			array( __CLASS__, 'render_meta_box' ),
			'mylink',
			'side',
			'low'
		);
	}

	/**
	 * Render the Import/Export side box.
	 *
	 * @since 1.1.0
	 *
	 * @param WP_Post $post Post being edited.
	 */
	public static function render_meta_box( $post ) {
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p class="wml-field__help">' . esc_html__('Save this MyLink first to enable import and export.', 'wp-mylinks') . '</p>';
			return;
		}

		$export_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'  => 'wp_mylinks_export_page',
					'post_id' => $post->ID,
				),
				admin_url('admin-post.php')
			),
			'wp_mylinks_export_page_' . $post->ID
		);
		?>
		<div class="wml-import-export" data-wml-post-id="<?php echo esc_attr($post->ID); ?>" data-wml-nonce="<?php echo esc_attr(wp_create_nonce('wp_mylinks_import_page_' . $post->ID)); ?>">
			<a class="wml-button-secondary wml-import-export__export" href="<?php echo esc_url($export_url); ?>">
				<span class="dashicons dashicons-download" aria-hidden="true"></span>
				<?php esc_html_e('Export This Page', 'wp-mylinks'); ?>
			</a>
			<hr>
			<p class="wml-field__help"><?php esc_html_e('Import a single-page export to replace this page’s configuration. Title, slug, and status are kept.', 'wp-mylinks'); ?></p>
			<label class="wml-file-drop wml-file-drop--compact">
				<input type="file" class="wml-import-export__file" accept=".json,application/json">
				<span class="wml-file-drop__icon dashicons dashicons-upload" aria-hidden="true"></span>
				<span class="wml-file-drop__text">
					<strong><?php esc_html_e('Choose a .json file', 'wp-mylinks'); ?></strong>
					<small><?php esc_html_e('or drag and drop it here', 'wp-mylinks'); ?></small>
				</span>
			</label>
			<button type="button" class="wml-button-secondary wml-import-export__import">
				<span class="dashicons dashicons-upload" aria-hidden="true"></span>
				<?php esc_html_e('Import Into This Page', 'wp-mylinks'); ?>
			</button>
			<p class="wml-import-export__status wml-field__help" role="status" aria-live="polite"></p>
		</div>
		<?php
	}
}

Wp_Mylinks_Tools::init();
