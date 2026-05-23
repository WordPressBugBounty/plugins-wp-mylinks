<?php

/**
 * The base template for the MyLinks page.
 *
 * Standalone HTML document — bypasses the active theme entirely. The mylink
 * post type is intentionally rendered without wp_head() / wp_footer() so that
 * unrelated theme and plugin output cannot leak into the bio page. Plugins
 * that legitimately need to inject content can hook into:
 *
 *   - 'wp_mylinks_head'        — inside <head>, after the plugin's own assets
 *   - 'wp_mylinks_body_open'   — immediately after <body>, before content
 *   - 'wp_mylinks_before_links' / 'wp_mylinks_after_links' — around the link list
 *   - 'wp_mylinks_footer'      — inside <footer>, before the closing tag
 *
 * Output strategy: PHP control structures are placed at column 0 so their
 * leading whitespace doesn't leak into the rendered HTML. All output is
 * indented with two-space steps for clean source-view output.
 *
 * @link       https://walterpinem.me/
 * @since      1.0.0
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/public
 * @author     Walter Pinem <hello@walterpinem.me>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;
$post_id = (int) get_the_ID();

/**
 * KSES allowlist for user-supplied script blobs (analytics, header, footer).
 * These come from the plugin's settings page, which only manage_options
 * users can save, but we still constrain the tag/attribute set as defense
 * in depth.
 */
$wp_mylinks_allowed_script_tags = array(
	'script'   => array(
		'type'        => array(),
		'src'         => array(),
		'async'       => array(),
		'defer'       => array(),
		'crossorigin' => array(),
		'integrity'   => array(),
		'nonce'       => array(),
		'id'          => array(),
		'data-*'      => true,
	),
	'noscript' => array(),
	'div'      => array( 'id' => array(), 'class' => array(), 'style' => array() ),
	'span'     => array( 'id' => array(), 'class' => array(), 'style' => array() ),
	'iframe'   => array(
		'src'             => array(),
		'width'           => array(),
		'height'          => array(),
		'frameborder'     => array(),
		'allow'           => array(),
		'allowfullscreen' => array(),
		'loading'         => array(),
		'title'           => array(),
		'referrerpolicy'  => array(),
		'sandbox'         => array(),
	),
	'a'        => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
	'img'      => array( 'src' => array(), 'alt' => array(), 'width' => array(), 'height' => array(), 'loading' => array() ),
	'p'        => array(),
	'br'       => array(),
	'strong'   => array(),
	'em'       => array(),
);

/**
 * Resolve a value with the standard fallback chain:
 *   per-post meta → global option → ultimate fallback.
 */
$wp_mylinks_resolve = static function ( $post_meta_key, $option_key, $fallback = '' ) use ( $post_id ) {
	$value = $post_id ? get_post_meta( $post_id, $post_meta_key, true ) : '';
	if ( '' !== $value && null !== $value ) {
		return $value;
	}
	$value = get_option( $option_key );
	if ( '' !== $value && null !== $value && false !== $value ) {
		return $value;
	}
	return $fallback;
};

/**
 * Build the social-icon row markup. Returns an empty string when there are
 * no configured socials, so we never emit a stray empty <div>.
 *
 * Combines the hardcoded 8 platforms with the "Additional Social Platforms"
 * repeater (CMB2 group, new in 1.0.8). Output is one flat row of icons in
 * the order: hardcoded 8 first, then repeater rows in the order saved.
 *
 * @param string $indent  Whitespace to prepend to each line for tidy output.
 * @param int    $post_id Current post ID (for the additional repeater meta).
 * @return string HTML markup, or '' if no socials are configured.
 */
$wp_mylinks_build_socials = static function ( $indent = '    ', $post_id = 0 ) {
	$post_id = (int) $post_id;
	$items   = array();

	// 1) Hardcoded 8 platforms — preserved exactly as before.
	$platforms     = array( 'facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'pinterest', 'tiktok', 'discord' );
	$plugin_images = plugins_url( '/public/images/', dirname( __DIR__ ) );

	foreach ( $platforms as $platform ) {
		list( $url, $icon ) = wp_mylinks_get_social_meta( $platform );
		if ( empty( $url ) ) {
			continue;
		}
		$icon_url = ! empty( $icon ) ? $icon : $plugin_images . $platform . '.png';

		$aria_label = sprintf(
			/* translators: %s: social platform name (Twitter, Facebook, etc.) */
			__( 'Visit our %s profile (opens in a new tab)', 'wp-mylinks' ),
			ucfirst( $platform )
		);

		$items[] = sprintf(
			'%1$s  <a href="%2$s" target="_blank" rel="noopener noreferrer nofollow" class="user-profile-link" aria-label="%5$s">' . "\n"
			. '%1$s    <img class="mylinks-social-icons" width="32" height="32" src="%3$s" alt="%4$s">' . "\n"
			. '%1$s  </a>',
			$indent,
			esc_url( $url ),
			esc_url( $icon_url ),
			esc_attr( ucfirst( $platform ) ),
			esc_attr( $aria_label )
		);
	}

	// 2) Additional Social Platforms repeater (1.0.8+).
	if ( $post_id > 0 && function_exists( 'wp_mylinks_collect_socials' ) ) {
		$additional = get_post_meta( $post_id, mylinks_prefix( 'additional-socials' ), true );
		if ( is_array( $additional ) ) {
			$fallback_icon = $plugin_images . 'globe.svg';
			foreach ( $additional as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$name = isset( $row['name'] ) ? trim( (string) $row['name'] ) : '';
				$url  = isset( $row['url'] ) ? trim( (string) $row['url'] ) : '';
				$icon = isset( $row['icon'] ) ? trim( (string) $row['icon'] ) : '';
				if ( '' === $url || '' === $name ) {
					continue;
				}
				$icon_url = '' !== $icon ? $icon : $fallback_icon;

				$aria_label = sprintf(
					/* translators: %s: social platform name (Twitter, Facebook, etc.) */
					__( 'Visit our %s profile (opens in a new tab)', 'wp-mylinks' ),
					$name
				);

				$items[] = sprintf(
					'%1$s  <a href="%2$s" target="_blank" rel="noopener noreferrer nofollow" class="user-profile-link" aria-label="%5$s">' . "\n"
					. '%1$s    <img class="mylinks-social-icons" width="32" height="32" src="%3$s" alt="%4$s">' . "\n"
					. '%1$s  </a>',
					$indent,
					esc_url( $url ),
					esc_url( $icon_url ),
					esc_attr( $name ),
					esc_attr( $aria_label )
				);
			}
		}
	}

	if ( empty( $items ) ) {
		return '';
	}

	return $indent . '<div class="user-profile">' . "\n"
		. implode( "\n", $items ) . "\n"
		. $indent . '</div>' . "\n";
};

// -----------------------------------------------------------------------------
// SEO meta resolution
// -----------------------------------------------------------------------------

$yoast_seo_active = function_exists( 'wp_mylinks_isYoastActive' ) && wp_mylinks_isYoastActive();

if ( $yoast_seo_active ) {
	$meta_title       = $post_id ? (string) get_post_meta( $post_id, '_yoast_wpseo_title', true ) : '';
	$meta_description = $post_id ? (string) get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ) : '';
} else {
	$meta_title       = $wp_mylinks_resolve( mylinks_prefix( 'meta-title' ), 'mylinks_meta_title', '' );
	$meta_description = $wp_mylinks_resolve( mylinks_prefix( 'meta-description' ), 'mylinks_meta_description', '' );
}
if ( '' === $meta_title ) {
	$meta_title = (string) get_the_title();
}

$set_noindex  = $wp_mylinks_resolve( mylinks_prefix( 'noindex' ), 'wp_mylinks_noindex', '' );
$set_nofollow = $wp_mylinks_resolve( mylinks_prefix( 'nofollow' ), 'wp_mylinks_nofollow', '' );
$noindex      = ( 'yes' === $set_noindex ) ? 'noindex' : 'index';
$nofollow     = ( 'yes' === $set_nofollow ) ? 'nofollow' : 'follow';

// -----------------------------------------------------------------------------
// Favicon, custom CSS, scripts, theme
// -----------------------------------------------------------------------------

$single_favicon = $post_id ? (string) get_post_meta( $post_id, mylinks_prefix( 'single-favicon' ), true ) : '';
$global_favicon = (string) get_option( 'mylinks_upload_favicon', '' );
$favicon        = '' !== $single_favicon ? $single_favicon : $global_favicon;

$analytics_script = (string) get_option( 'wp_mylinks_analytics', '' );
$header_script    = $wp_mylinks_resolve( mylinks_prefix( 'mylinks-single-custom-header-script' ), 'wp_mylinks_header_script', '' );
$body_script      = (string) get_option( 'wp_mylinks_open_body_script', '' );
$footer_script    = $wp_mylinks_resolve( mylinks_prefix( 'mylinks-single-custom-footer-script' ), 'wp_mylinks_footer_script', '' );
$custom_css       = $wp_mylinks_resolve( mylinks_prefix( 'mylinks-single-custom-styles' ), 'wp_mylinks_custom_css', '' );
$avatar_style     = $post_id ? (string) get_post_meta( $post_id, mylinks_prefix( 'avatar-style' ), true ) : '';

$theme_options = (string) get_option( 'mylinks_theme', 'default' );
$theme_key     = $post_id ? (string) get_post_meta( $post_id, mylinks_prefix( 'theme' ), true ) : '';
$body_theme    = ( 'none' === $theme_key || '' === $theme_key )
	? ( '' !== $theme_options ? $theme_options : 'default' )
	: $theme_key;

$social_position = $post_id ? (string) get_post_meta( $post_id, mylinks_prefix( 'social-media-position' ), true ) : '';
$play_icon_path  = plugins_url( 'public/images/play.png', WP_MYLINKS_FILE );

// Build the inline avatar style ahead of time so the <style> block stays clean.
$avatar_inline_css = '';
switch ( $avatar_style ) {
	case 'shadow':
		$avatar_inline_css = '.mylinks .avatar{box-shadow:0 1px 2px rgb(0 0 0 / 0.1),0 2px 4px rgb(0 0 0 / 0.1),0 4px 8px rgb(0 0 0 / 0.1),0 8px 16px rgb(0 0 0 / 0.1),0 16px 32px rgb(0 0 0 / 0.1),0 32px 64px rgb(0 0 0 / 0.1)}';
		break;
	case 'plain':
		$avatar_inline_css = '.mylinks .avatar{background:transparent}';
		break;
	case 'transparent':
		$avatar_inline_css = '.mylinks .avatar,.mylinks-body .avatar img{background:transparent !important}';
		break;
	default:
		$avatar_inline_css = '.mylinks .avatar{background:#fdf497;background:radial-gradient(circle at 30% 107%,#fdf497 0,#fdf497 5%,#fd5949 45%,#d6249f 60%,#8a3fb6 90%)}';
}

// Pre-render registered stylesheets/scripts into strings so we can place them
// cleanly inside <head> / before </body> with proper indentation.
wp_enqueue_style( 'mylinks-public-css' );
wp_enqueue_style( 'mylinks-youtube-css' );
wp_enqueue_script( 'mylinks-public-js' );

ob_start();
wp_styles()->do_item( 'mylinks-public-css' );
wp_styles()->do_item( 'mylinks-youtube-css' );
$plugin_styles_html = trim( ob_get_clean() );

ob_start();
wp_scripts()->do_item( 'mylinks-public-js' );
$plugin_script_html = trim( ob_get_clean() );

// Build a single consolidated inline <style> block.
$inline_css_chunks = array(
	'.youtube-player .play{background:url(' . esc_url( $play_icon_path ) . ') no-repeat;}',
	$avatar_inline_css,
);
if ( '' !== $custom_css ) {
	$inline_css_chunks[] = wp_strip_all_tags( $custom_css );
}
$inline_css_block = implode( "\n", array_filter( $inline_css_chunks ) );

// -----------------------------------------------------------------------------
// SEO output: Schema.org JSON-LD, Open Graph, Twitter Card (1.0.8+)
//
// All output is gated on the global enable toggle AND defers to Yoast SEO when
// Yoast is active and emitting the same kind of output. New options default
// to off so existing 1.0.7 installs upgrade silently.
// -----------------------------------------------------------------------------

$schema_script_tag = '';
$og_meta_block     = '';

// 1) Schema.org JSON-LD.
if ( 'yes' === get_option( 'wp_mylinks_enable_schema' ) ) {
	$skip_schema = function_exists( 'wp_mylinks_yoast_handles_schema' ) && wp_mylinks_yoast_handles_schema();
	if ( ! $skip_schema && function_exists( 'wp_mylinks_build_schema_json' ) ) {
		$schema_json = wp_mylinks_build_schema_json( $post_id );
		if ( is_string( $schema_json ) && '' !== $schema_json ) {
			$schema_script_tag = '  <script type="application/ld+json">' . "\n"
				. '    ' . $schema_json . "\n"
				. '  </script>';
		}
	}
}

// 2) Open Graph + Twitter Card meta tags.
if ( 'yes' === get_option( 'wp_mylinks_enable_og' ) ) {
	$skip_og = function_exists( 'wp_mylinks_yoast_handles_og' ) && wp_mylinks_yoast_handles_og();
	if ( ! $skip_og ) {
		$og_lines = array();

		$og_title = '' !== $meta_title ? $meta_title : (string) get_the_title();
		$og_desc  = $meta_description;
		$og_url   = $post_id > 0 ? (string) get_permalink( $post_id ) : '';
		$og_image = function_exists( 'wp_mylinks_resolve_og_image' )
			? wp_mylinks_resolve_og_image( $post_id )
			: '';
		$site_name = (string) get_bloginfo( 'name' );

		// Schema → og:type. Person / Organization → 'profile' is technically
		// the most accurate type per OGP spec, but Facebook actually expects
		// 'website' for bio-style pages and renders them better. Stick with
		// 'website' for the default OGP type; site owners can override via
		// the wp_mylinks_og_meta filter below.
		$og_type = 'website';

		$og_lines[] = sprintf( '  <meta property="og:type" content="%s">', esc_attr( $og_type ) );
		$og_lines[] = sprintf( '  <meta property="og:title" content="%s">', esc_attr( $og_title ) );
		if ( '' !== $og_desc ) {
			$og_lines[] = sprintf( '  <meta property="og:description" content="%s">', esc_attr( $og_desc ) );
		}
		if ( '' !== $og_url ) {
			$og_lines[] = sprintf( '  <meta property="og:url" content="%s">', esc_url( $og_url ) );
		}
		if ( '' !== $site_name ) {
			$og_lines[] = sprintf( '  <meta property="og:site_name" content="%s">', esc_attr( $site_name ) );
		}
		if ( '' !== $og_image ) {
			$og_lines[] = sprintf( '  <meta property="og:image" content="%s">', esc_url( $og_image ) );
			$og_lines[] = '  <meta property="og:image:width" content="1200">';
			$og_lines[] = '  <meta property="og:image:height" content="630">';
		}

		// Twitter Card.
		$twitter_card_type = '' !== $og_image ? 'summary_large_image' : 'summary';
		$og_lines[]        = sprintf( '  <meta name="twitter:card" content="%s">', esc_attr( $twitter_card_type ) );
		$og_lines[]        = sprintf( '  <meta name="twitter:title" content="%s">', esc_attr( $og_title ) );
		if ( '' !== $og_desc ) {
			$og_lines[] = sprintf( '  <meta name="twitter:description" content="%s">', esc_attr( $og_desc ) );
		}
		if ( '' !== $og_image ) {
			$og_lines[] = sprintf( '  <meta name="twitter:image" content="%s">', esc_url( $og_image ) );
		}

		$twitter_handle = (string) get_option( 'wp_mylinks_twitter_handle', '' );
		if ( '' !== $twitter_handle ) {
			$og_lines[] = sprintf( '  <meta name="twitter:site" content="%s">', esc_attr( $twitter_handle ) );
			$og_lines[] = sprintf( '  <meta name="twitter:creator" content="%s">', esc_attr( $twitter_handle ) );
		}

		/**
		 * Filter the OG/Twitter meta tag lines before they are joined and
		 * emitted. Each entry is a complete `<meta ...>` line, already escaped.
		 *
		 * @since 1.0.8
		 *
		 * @param string[] $og_lines Array of meta-tag HTML lines.
		 * @param int      $post_id  Current MyLink post ID.
		 */
		$og_lines = apply_filters( 'wp_mylinks_og_meta', $og_lines, $post_id );

		if ( ! empty( $og_lines ) && is_array( $og_lines ) ) {
			$og_meta_block = implode( "\n", array_filter( array_map( 'strval', $og_lines ) ) );
		}
	}
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="generator" content="WP MyLinks <?php echo esc_attr( WP_MYLINKS_VERSION ); ?>">
<?php if ( '' !== $favicon ) : ?>
  <link rel="icon" type="image/png" href="<?php echo esc_url( $favicon ); ?>">
<?php endif; ?>
  <title><?php echo esc_html( $meta_title ); ?></title>
  <meta name="description" content="<?php echo esc_attr( $meta_description ); ?>">
  <meta name="robots" content="<?php echo esc_attr( $noindex . ', ' . $nofollow ); ?>, max-image-preview:large, max-snippet:-1, max-video-preview:-1">

<?php if ( '' !== $plugin_styles_html ) : ?>
  <?php echo $plugin_styles_html . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP-generated stylesheet tags. ?>
<?php endif; ?>
  <style id="wp-mylinks-inline-css">
<?php echo $inline_css_block . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS sanitized via wp_strip_all_tags() before entering $inline_css_chunks; esc_html() would corrupt child selectors (> becomes &gt;). ?>
  </style>
<?php
if ( '' !== $og_meta_block ) {
	echo "\n" . $og_meta_block . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped above.
}
if ( '' !== $schema_script_tag ) {
	echo "\n" . $schema_script_tag . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON pre-built via wp_json_encode.
}
if ( '' !== $analytics_script ) {
	echo wp_kses( $analytics_script, $wp_mylinks_allowed_script_tags ) . "\n";
}
if ( '' !== $header_script ) {
	echo wp_kses( $header_script, $wp_mylinks_allowed_script_tags ) . "\n";
}

/**
 * Fires inside the <head> of the MyLinks template, after all plugin-controlled
 * assets and user-supplied scripts have been printed.
 *
 * @since 1.0.8
 *
 * @param int $post_id Current MyLink post ID.
 */
do_action( 'wp_mylinks_head', $post_id );
?>
</head>

<body class="mylinks-body <?php echo esc_attr( $body_theme ); ?>">
  <a class="screen-reader-text" href="#wp-mylinks-content"><?php esc_html_e( 'Skip to content', 'wp-mylinks' ); ?></a>
<?php
if ( '' !== $body_script ) {
	echo wp_kses( $body_script, $wp_mylinks_allowed_script_tags ) . "\n";
}

/**
 * Fires immediately after the opening <body> tag.
 *
 * @since 1.0.8
 *
 * @param int $post_id Current MyLink post ID.
 */
do_action( 'wp_mylinks_body_open', $post_id );


if ( ! have_posts() ) {
	echo "</body>\n</html>\n";
	return;
}

while ( have_posts() ) :
	the_post();

	// Refresh inside the loop in case we entered with a different ID.
	$post_id     = (int) get_the_ID();
	$avatar      = (string) get_post_meta( $post_id, mylinks_prefix( 'avatar' ), true );
	$name        = (string) get_post_meta( $post_id, mylinks_prefix( 'name' ), true );
	$description = (string) get_post_meta( $post_id, mylinks_prefix( 'description' ), true );

	// Build the link items as clean HTML chunks. This avoids the messy
	// alternative-syntax cascade that used to live inline in the template.
	$links_markup = '';
	$links        = get_post_meta( $post_id, mylinks_prefix( 'links' ), true );

	foreach ( (array) $links as $link ) {
		$title       = isset( $link['title'] ) ? (string) $link['title'] : '';
		$url         = isset( $link['url'] ) ? (string) $link['url'] : '';
		$image       = isset( $link['image'] ) ? (string) $link['image'] : '';
		$youtube_url = isset( $link['youtube-video'] ) ? (string) $link['youtube-video'] : '';
		$embed       = isset( $link['media-embed'] ) ? (string) $link['media-embed'] : '';
		$card_layout = isset( $link['card-layout'] ) && 'yes' === $link['card-layout'];

		// 1. YouTube placeholder.
		if ( '' !== $youtube_url && '' === $embed ) {
			$video_id = wp_mylinks_extract_youtube_id( $youtube_url );
			if ( '' === $video_id ) {
				continue;
			}
			$links_markup .= sprintf(
				'      <div class="link youtube-embed">' . "\n"
				. '        <div class="youtube-player" data-id="%s"></div>' . "\n"
				. '      </div>' . "\n",
				esc_attr( $video_id )
			);
			continue;
		}

		// 2. Generic oEmbed (TikTok, Spotify, Tweets, etc).
		if ( '' !== $embed && '' === $youtube_url ) {
			$cache_key  = 'wp_mylinks_oembed_' . md5( $embed );
			$embed_html = get_transient( $cache_key );
			if ( false === $embed_html ) {
				$embed_html = wp_oembed_get( $embed );
				if ( $embed_html && ! is_wp_error( $embed_html ) ) {
					$embed_html = wp_filter_oembed_result( $embed_html, $embed, array(), $post );
					set_transient( $cache_key, $embed_html, DAY_IN_SECONDS );
				}
			}

			if ( is_string( $embed_html ) && '' !== $embed_html ) {
				$inner = $embed_html;
			} elseif ( is_wp_error( $embed_html ) ) {
				$inner = esc_html__( 'Unable to embed the content. Reason: ', 'wp-mylinks' )
					. esc_html( $embed_html->get_error_message() );
			} else {
				$inner = esc_html__( 'Unable to embed the content.', 'wp-mylinks' );
			}

			$links_markup .= '      <div class="link media-embed-wrapper">' . "\n"
				. '        <div class="media-embed">' . "\n"
				. '          ' . $inner . "\n"
				. '        </div>' . "\n"
				. '      </div>' . "\n";
			continue;
		}

		// 3. Card layout (image + title + url, image used as background).
		if ( $card_layout && '' !== $title && '' !== $url && '' !== $image ) {
			/* translators: %s: link title */
			$aria_label = sprintf( __( '%s (opens in a new tab)', 'wp-mylinks' ), $title );
			$links_markup .= sprintf(
				'      <div class="card-wrapper">' . "\n"
				. '        <div class="mylink-card">' . "\n"
				. '          <a class="mylink-card-link link_count" href="%1$s" target="_blank" rel="noopener" aria-label="%4$s">' . "\n"
				. '            <img class="mylink-card-background" src="%2$s" alt="">' . "\n"
				. '            <div class="mylink-card-title-wrapper">' . "\n"
				. '              <h2 class="mylink-card-title">%3$s</h2>' . "\n"
				. '            </div>' . "\n"
				. '          </a>' . "\n"
				. '        </div>' . "\n"
				. '      </div>' . "\n",
				esc_url( $url ),
				esc_url( $image ),
				esc_html( $title ),
				esc_attr( $aria_label )
			);
			continue;
		}

		// 4. Plain link, no thumbnail.
		if ( '' === $image && '' !== $url && '' !== $title ) {
			/* translators: %s: link title */
			$aria_label = sprintf( __( '%s (opens in a new tab)', 'wp-mylinks' ), $title );
			$links_markup .= sprintf(
				'      <div class="link">' . "\n"
				. '        <a class="button link-without-image inline-photo show-on-scroll link_count" href="%1$s" target="_blank" rel="noopener" aria-label="%3$s">' . "\n"
				. '          <span class="link-text">%2$s</span>' . "\n"
				. '        </a>' . "\n"
				. '      </div>' . "\n",
				esc_url( $url ),
				esc_html( $title ),
				esc_attr( $aria_label )
			);
			continue;
		}

		// 5. Link with thumbnail.
		if ( '' !== $image && '' !== $url && '' !== $title ) {
			/* translators: %s: link title */
			$aria_label = sprintf( __( '%s (opens in a new tab)', 'wp-mylinks' ), $title );
			$links_markup .= sprintf(
				'      <div class="link">' . "\n"
				. '        <a class="button link-with-image inline-photo show-on-scroll link_count" href="%1$s" target="_blank" rel="noopener" aria-label="%4$s">' . "\n"
				. '          <div class="thumbnail-wrap">' . "\n"
				. '            <img class="link-image" src="%2$s" alt="">' . "\n"
				. '          </div>' . "\n"
				. '          <span class="link-text">%3$s</span>' . "\n"
				. '        </a>' . "\n"
				. '      </div>' . "\n",
				esc_url( $url ),
				esc_url( $image ),
				esc_html( $title ),
				esc_attr( $aria_label )
			);
		}
	}

	$top_socials    = ( 'top' === $social_position ) ? $wp_mylinks_build_socials( '    ', $post_id ) : '';
	$bottom_socials = ( 'bottom' === $social_position ) ? $wp_mylinks_build_socials( '  ', $post_id ) : '';
	?>

  <main class="mylinks" id="wp-mylinks-content" tabindex="-1">
    <div class="avatar">
<?php if ( '' !== $avatar ) : ?>
      <img width="140" height="140" src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_attr( '' !== $name ? $name : __( 'Profile picture', 'wp-mylinks' ) ); ?>">
<?php endif; ?>
    </div>

    <div class="name">
      <h1><?php echo esc_html( $name ); ?></h1>
    </div>

<?php if ( '' !== trim( $description ) ) : ?>
    <div class="description">
      <?php echo wp_kses_post( wpautop( $description ) ); ?>
    </div>
<?php endif; ?>

<?php
if ( '' !== $top_socials ) {
	echo $top_socials; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped in builder.
}

/**
 * Fires before the link list is rendered.
 *
 * @since 1.0.8
 *
 * @param int $post_id Current MyLink post ID.
 */
do_action( 'wp_mylinks_before_links', $post_id );
?>

    <div class="links">
<?php
if ( '' !== $links_markup ) {
	echo $links_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped in builder.
}
?>
    </div>

<?php
/**
 * Fires after the link list is rendered.
 *
 * @since 1.0.8
 *
 * @param int $post_id Current MyLink post ID.
 */
do_action( 'wp_mylinks_after_links', $post_id );
?>
  </main>

<?php
	wp_mylinks_track_mylink_page( $post_id );
endwhile;

if ( '' !== $bottom_socials ) {
	echo $bottom_socials; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped in builder.
}
?>

  <footer id="site-footer" class="mylinks-footer" role="contentinfo">
<?php if ( 'yes' === get_option( 'wp_mylinks_credits' ) ) : ?>
    <div class="wp-mylinks-credits">
      <?php
		echo wp_kses(
			__( 'Made with ❤️ and ☕ by <a href="https://walterpinem.me/" target="_blank" rel="noopener nofollow"><strong>Walter Pinem</strong></a>', 'wp-mylinks' ),
			array(
				'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
				'strong' => array(),
			)
		);
		?>
    </div>
<?php endif; ?>
<?php
if ( '' !== $footer_script ) {
	echo wp_kses( $footer_script, $wp_mylinks_allowed_script_tags ) . "\n";
}

/**
 * Fires inside the footer, before its closing tag.
 *
 * @since 1.0.8
 *
 * @param int $post_id Current MyLink post ID.
 */
do_action( 'wp_mylinks_footer', $post_id );
?>
  </footer>

<?php if ( '' !== $plugin_script_html ) : ?>
  <?php echo $plugin_script_html . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP-generated script tag. ?>
<?php endif; ?>
</body>
</html>
