<?php
/**
 * oEmbed field.
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes/fields
 * @since      1.1.0
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * URL input with an embed preview. The stored value is the URL, sanitized
 * with wp_kses_post exactly as CMB2's oembed default did. The preview is
 * fetched lazily over AJAX (never during page render, so a slow provider
 * cannot stall the editor) and shares the frontend's transient cache
 * (`wp_mylinks_oembed_<md5>`), so previewing in the editor warms the
 * public page's cache.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_Field_Oembed extends Wp_Mylinks_Field {

	/**
	 * Render the input and the (initially empty) preview container.
	 *
	 * @param mixed $value Current value.
	 * @return void
	 */
	protected function render_control( $value ) {
		printf(
			'<input type="text" inputmode="url" class="wml-field__input wml-oembed__url" id="%1$s" name="%2$s" value="%3$s"%4$s>',
			esc_attr( $this->input_attr_id() ),
			esc_attr( $this->input_name() ),
			esc_attr( (string) $value ),
			$this->attributes_html() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped key-by-key in attributes_html().
		);
		echo '<div class="wml-oembed__preview" aria-live="polite"></div>';
	}

	/**
	 * CMB2 sanitized oembed through wp_kses_post.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	protected function sanitize_default( $value ) {
		return is_array( $value ) ? array_map( 'wp_kses_post', $value ) : wp_kses_post( (string) $value );
	}

	/**
	 * AJAX handler: resolve a URL to embed HTML, cached in the same
	 * transient the public template reads.
	 *
	 * Registered from the fields loader. Requires the fields AJAX nonce and
	 * an editing-capable user; the URL itself is validated by wp_oembed_get.
	 *
	 * @return void
	 */
	public static function ajax_preview() {
		check_ajax_referer( 'wp_mylinks_fields_ajax', 'nonce' );

		// Matches the page-level capability required to reach the editor where
		// this preview is used (the mylink post type maps to page caps).
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'wp-mylinks' ) ), 403 );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		if ( '' === $url ) {
			wp_send_json_error( array( 'message' => __( 'No URL given.', 'wp-mylinks' ) ), 400 );
		}

		// wp_oembed_get() returns provider HTML already run through core's
		// oEmbed filters; cache it under the same key the public template
		// reads so the editor preview warms the frontend cache.
		$cache_key  = 'wp_mylinks_oembed_' . md5( $url );
		$embed_html = get_transient( $cache_key );
		if ( false === $embed_html ) {
			$embed_html = wp_oembed_get( $url );
			if ( $embed_html && ! is_wp_error( $embed_html ) ) {
				set_transient( $cache_key, $embed_html, DAY_IN_SECONDS );
			}
		}

		if ( ! is_string( $embed_html ) || '' === $embed_html ) {
			wp_send_json_error( array( 'message' => __( 'No embeddable media found at that URL.', 'wp-mylinks' ) ) );
		}

		wp_send_json_success( array( 'html' => $embed_html ) );
	}
}
