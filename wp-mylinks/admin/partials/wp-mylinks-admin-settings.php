<?php
/**
 * Create the plugin Settings page.
 *
 * Reshaped to the Online Store Kit house standard in 1.1.0: gold hero band,
 * dashicon tab bar, card stacks on the wml-* component vocabulary. Every
 * option name, settings group, and sanitize callback is unchanged from 1.0.8,
 * and the `?tab=` keys are stable so existing deep links keep resolving.
 *
 * @link       https://walterpinem.me/
 * @since      1.0.0
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/admin/partials
 * @author     Walter Pinem <hello@walterpinem.me>
 * @copyright  Copyright (c) 2020-2026, Walter Pinem, Seni Berpikir
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Donate button shortcode.
 *
 * Used internally on the Welcome and Support tabs via [donate]. The shortcode
 * arguments are unused and deliberately not declared.
 *
 * @return string
 */
function wp_mylinks_donate_button_shortcode() {
	ob_start();
	?>
	<div class="donate-container" style="text-align:center;">
		<p><?php esc_html_e( 'To keep this plugin free, I spent cups of coffee building it. If you love and find it really useful for you or your business, you can always', 'wp-mylinks' ); ?></p>
		<a href="https://www.paypal.me/WalterPinem" target="_blank" rel="noopener">
			<button type="button" class="donatebutton">☕ <?php esc_html_e( 'Buy Me a Coffee', 'wp-mylinks' ); ?></button>
		</a>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'donate', 'wp_mylinks_donate_button_shortcode' );

/**
 * Register the plugin Settings page.
 */
function wp_mylinks_create_admin_page() {
	add_submenu_page(
		'edit.php?post_type=mylink',
		__( 'WP MyLinks Settings', 'wp-mylinks' ),
		__( 'Settings', 'wp-mylinks' ),
		'manage_options',
		'welcome',
		'wp_mylinks_admin_page',
		26
	);
	add_action( 'admin_init', 'wp_mylinks_register_settings' );
}
add_action( 'admin_menu', 'wp_mylinks_create_admin_page' );

/**
 * Register settings for the General and Scripts tabs.
 */
function wp_mylinks_register_settings() {
	// Global settings (with sanitization callbacks for safety).
	register_setting( 'mylinks-global', 'mylinks_theme', array( 'sanitize_callback' => 'sanitize_key' ) );
	register_setting( 'mylinks-global', 'mylinks_meta_title', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'mylinks-global', 'mylinks_meta_description', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
	register_setting( 'mylinks-global', 'mylinks_upload_favicon', array( 'sanitize_callback' => 'esc_url_raw' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_nofollow', array( 'sanitize_callback' => 'wp_mylinks_sanitize_yes_or_empty' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_noindex', array( 'sanitize_callback' => 'wp_mylinks_sanitize_yes_or_empty' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_credits', array( 'sanitize_callback' => 'wp_mylinks_sanitize_yes_or_empty' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_hide_notice', array( 'sanitize_callback' => 'wp_mylinks_sanitize_yes_or_empty' ) );

	// Tools tab group. The uninstall flag lives in its own group so saving the
	// General form (which posts its whole group) can never silently reset it.
	register_setting( 'mylinks-tools', 'wp_mylinks_delete_data_on_uninstall', array( 'sanitize_callback' => 'wp_mylinks_sanitize_yes_or_empty' ) );

	// Link URL sources (Tools tab). Its own group so saving it never disturbs
	// the uninstall flag above. Array of post-type slugs; empty = Collections
	// only. Type 'array' keeps the value an array even when every box is
	// unchecked (posted as absent).
	register_setting(
		'mylinks-link-sources',
		'wp_mylinks_link_post_types',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'wp_mylinks_sanitize_post_types',
		)
	);

	// Accent colors (F8, 1.1.0) — global defaults; each MyLink can override.
	register_setting( 'mylinks-global', 'wp_mylinks_accent_bg', array( 'sanitize_callback' => 'wp_mylinks_sanitize_hex' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_accent_button_bg', array( 'sanitize_callback' => 'wp_mylinks_sanitize_hex' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_accent_button_text', array( 'sanitize_callback' => 'wp_mylinks_sanitize_hex' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_accent_text', array( 'sanitize_callback' => 'wp_mylinks_sanitize_hex' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_font_family', array( 'sanitize_callback' => 'wp_mylinks_sanitize_font_family' ) );

	// Schema.org JSON-LD (1.0.8+).
	register_setting( 'mylinks-global', 'wp_mylinks_enable_schema', array( 'sanitize_callback' => 'wp_mylinks_sanitize_yes_or_empty' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_schema_type', array( 'sanitize_callback' => 'wp_mylinks_sanitize_schema_type' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_enable_profilepage', array( 'sanitize_callback' => 'wp_mylinks_sanitize_yes_or_empty' ) );

	// Open Graph / Twitter Card (1.0.8+).
	register_setting( 'mylinks-global', 'wp_mylinks_enable_og', array( 'sanitize_callback' => 'wp_mylinks_sanitize_yes_or_empty' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_og_image', array( 'sanitize_callback' => 'esc_url_raw' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_twitter_handle', array( 'sanitize_callback' => 'wp_mylinks_sanitize_twitter_handle' ) );

	// Custom-script settings — accept raw script/style content, but only from
	// users WordPress trusts with it. wp_mylinks_sanitize_raw_script delegates
	// to the shared capability gate (wp_mylinks_sanitize_raw_code): verbatim for
	// unfiltered_html holders, wp_kses_post for everyone else (notably multisite
	// site admins, who have manage_options but not unfiltered_html).
	register_setting( 'mylinks-custom-scripts', 'wp_mylinks_analytics', array( 'sanitize_callback' => 'wp_mylinks_sanitize_raw_script' ) );
	register_setting( 'mylinks-custom-scripts', 'wp_mylinks_header_script', array( 'sanitize_callback' => 'wp_mylinks_sanitize_raw_script' ) );
	register_setting( 'mylinks-custom-scripts', 'wp_mylinks_open_body_script', array( 'sanitize_callback' => 'wp_mylinks_sanitize_raw_script' ) );
	register_setting( 'mylinks-custom-scripts', 'wp_mylinks_footer_script', array( 'sanitize_callback' => 'wp_mylinks_sanitize_raw_script' ) );
	register_setting( 'mylinks-custom-scripts', 'wp_mylinks_custom_css', array( 'sanitize_callback' => 'wp_mylinks_sanitize_raw_script' ) );
	register_setting( 'mylinks-custom-scripts', 'wp_mylinks_dequeue', array( 'sanitize_callback' => 'wp_mylinks_sanitize_yes_or_empty' ) );
}

/**
 * Sanitize a "yes" / empty checkbox-style option.
 *
 * @param mixed $value Submitted value.
 * @return string 'yes' or empty string.
 */
if ( ! function_exists( 'wp_mylinks_sanitize_yes_or_empty' ) ) {
	function wp_mylinks_sanitize_yes_or_empty( $value ) {
		return ( 'yes' === $value ) ? 'yes' : '';
	}
}

/**
 * Sanitize a hex color option: `#rrggbb` (or `#rgb`), else empty string.
 *
 * @since 1.1.0
 *
 * @param mixed $value Submitted value.
 * @return string
 */
if ( ! function_exists( 'wp_mylinks_sanitize_hex' ) ) {
	function wp_mylinks_sanitize_hex( $value ) {
		$color = sanitize_hex_color( trim( (string) $value ) );
		return is_string( $color ) ? $color : '';
	}
}

/**
 * Sanitize raw script / style content for the Scripts tab.
 *
 * The custom-scripts feature is intentionally raw — admins paste analytics
 * snippets, GTM containers, Facebook Pixel scripts, custom CSS, etc. Reaching
 * the Scripts tab requires `manage_options`, but that is NOT sufficient to
 * store raw `<script>`: on multisite even site Administrators lack
 * `unfiltered_html`. So this delegates to the shared capability gate
 * (`wp_mylinks_sanitize_raw_code`), which returns the value verbatim only for
 * users who hold `unfiltered_html` and runs everyone else through
 * `wp_kses_post()`. The front-end `wp_kses()` allowlist still permits
 * `<script>` because values stored by trusted users must render — the trust
 * decision is made here, at save time, not at output.
 *
 * @since 1.0.8
 *
 * @param mixed $value Submitted value.
 * @return string
 */
if ( ! function_exists( 'wp_mylinks_sanitize_raw_script' ) ) {
	function wp_mylinks_sanitize_raw_script( $value ) {
		return wp_mylinks_sanitize_raw_code( $value );
	}
}

/**
 * NOTE: The previous version called register_deactivation_hook() here to delete
 * every plugin option on deactivation. That destroyed user data permanently and
 * has been removed. Deactivation is handled in includes/class-wp-mylinks-deactivator.php
 * and only flushes rewrite rules now. True uninstall is handled by uninstall.php.
 */

/**
 * Render the "Created with ❤️ and ☕…" credit line shared by Welcome and
 * Support tabs. Wraps the author URL with wpmylinks_url() so it carries the
 * standard tracking parameters.
 */
function wp_mylinks_render_credit_line() {
	$author_url = function_exists( 'wpmylinks_url' )
		? wpmylinks_url( 'https://walterpinem.me' )
		: esc_url( 'https://walterpinem.me' );

	$message = sprintf(
		/* translators: %s: link to the author's website */
		__( 'Created with ❤️ and ☕ in Jakarta, Indonesia by %s', 'wp-mylinks' ),
		'<a href="' . esc_url( $author_url ) . '" target="_blank" rel="noopener"><strong>Walter Pinem</strong></a>'
	);
	?>
	<p style="text-align:center;">
		<?php
		echo wp_kses(
			$message,
			array(
				'a'      => array(
					'href'   => array(),
					'target' => array(),
					'rel'    => array(),
				),
				'strong' => array(),
			)
		);
		?>
	</p>
	<?php
}

// ---------------------------------------------------------------------------
// House-shape building blocks (1.1.0).
// ---------------------------------------------------------------------------

/**
 * Open a settings card: surface, header with dashicon + title, optional
 * description, and the content well.
 *
 * @param string $icon  Dashicon slug without the "dashicons-" prefix.
 * @param string $title Card title (translated by the caller).
 * @param string $desc  Optional description. May contain limited HTML.
 * @return void
 */
function wp_mylinks_card_open( $icon, $title, $desc = '', $wide = false ) {
	?>
	<div class="wml-settings-card<?php echo $wide ? ' wml-card--wide' : ''; ?>">
		<div class="wml-card-header">
			<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
			<h2><?php echo esc_html( $title ); ?></h2>
		</div>
		<div class="wml-card-body">
			<?php if ( '' !== $desc ) : ?>
				<p class="wml-card-description">
					<?php
					echo wp_kses(
						$desc,
						array(
							'code'   => array(),
							'strong' => array(),
							'b'      => array(),
							'a'      => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					);
					?>
				</p>
			<?php endif; ?>
	<?php
}

/**
 * Close a settings card.
 *
 * @return void
 */
function wp_mylinks_card_close() {
	echo '</div></div>';
}

/**
 * Render a toggle row for a yes/empty checkbox option.
 *
 * The input stays a real checkbox (value "yes", same name as before) so the
 * save path is unchanged; the track is presentation only.
 *
 * @param string $option Option name (also used as the element id).
 * @param string $label  Toggle label text.
 * @param string $desc   Help text. May contain limited HTML.
 * @return void
 */
function wp_mylinks_toggle_row( $option, $label, $desc = '' ) {
	?>
	<div class="wml-field wml-field--toggle">
		<label class="wml-toggle" for="<?php echo esc_attr( $option ); ?>">
			<input type="checkbox" id="<?php echo esc_attr( $option ); ?>" name="<?php echo esc_attr( $option ); ?>" value="yes" <?php checked( get_option( $option ), 'yes' ); ?>>
			<span class="wml-toggle__track" aria-hidden="true"></span>
			<span class="wml-toggle__text">
				<strong><?php echo wp_kses( $label, array( 'code' => array() ) ); ?></strong>
				<?php if ( '' !== $desc ) : ?>
					<small><?php echo wp_kses( $desc, array( 'code' => array() ) ); ?></small>
				<?php endif; ?>
			</span>
		</label>
	</div>
	<?php
}

/**
 * Render a raw-script textarea row for the Scripts tab.
 *
 * @param string $option Option name (also used as the element id).
 * @param string $label  Field label.
 * @param string $desc   Help text. May contain limited HTML.
 * @return void
 */
function wp_mylinks_script_row( $option, $label, $desc = '', $rows = 8 ) {
	?>
	<div class="wml-field">
		<label class="wml-field__label" for="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $label ); ?></label>
		<textarea id="<?php echo esc_attr( $option ); ?>" name="<?php echo esc_attr( $option ); ?>" class="wml-field__input wml-code" rows="<?php echo esc_attr( $rows ); ?>" spellcheck="false" wrap="off"><?php echo esc_textarea( get_option( $option ) ); ?></textarea>
		<?php if ( '' !== $desc ) : ?>
			<p class="wml-field__help">
			<?php
			echo wp_kses(
				$desc,
				array(
					'code' => array(),
					'b'    => array(),
				)
			);
			?>
										</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The save bar with the primary submit button.
 *
 * @return void
 */
function wp_mylinks_save_bar() {
	?>
	<div class="wml-settings-footer">
		<button type="submit" class="wml-button-primary"><?php esc_html_e( 'Save Changes', 'wp-mylinks' ); ?></button>
	</div>
	<?php
}

/**
 * The WP MyLinks brand mark for the settings header.
 *
 * This is the ONE inline SVG the house standard permits: a product's own mark
 * is identity, not iconography, and no dashicon can stand in for it — every
 * other symbol on the screen stays a dashicon. Ported from
 * `_dev/wporg-assets/icon.svg` (an avatar circle over three link bars): the
 * yellow background square is dropped because the CSS plate
 * (`.wml-settings-icon`, an ink tile) IS the background, and the shapes are
 * recoloured to brand yellow — which measures ~10:1 on ink but only ~1.4:1 on
 * white, so it is a background colour everywhere except on the ink plate. The
 * colours are baked in, not inherited. Emitted through wp_kses (see
 * wp_mylinks_brand_mark_tags) so the markup is fixed and auditable.
 *
 * @since 1.1.0
 *
 * @return string SVG markup.
 */
function wp_mylinks_brand_mark() {
	return implode(
		'',
		array(
			'<svg class="wml-brand-mark" viewBox="0 0 256 256" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">',
			// Avatar head.
			'<circle cx="128" cy="76" r="34" fill="#ffd957" />',
			// Three stacked link bars.
			'<rect x="54" y="128" width="148" height="30" rx="15" fill="#ffd957" />',
			'<rect x="54" y="168" width="148" height="30" rx="15" fill="#ffd957" />',
			'<rect x="54" y="208" width="148" height="30" rx="15" fill="#ffd957" />',
			'</svg>',
		)
	);
}

/**
 * wp_kses allowlist for the brand mark.
 *
 * Attribute names MUST be lowercase: wp_kses lowercases them before matching,
 * so a `viewBox` entry would never match and the mark would lose its viewBox
 * and render at intrinsic size. The lowercase `viewbox` it emits is re-adjusted
 * back to `viewBox` by the HTML parser for inline SVG, so it renders correctly.
 *
 * @since 1.1.0
 *
 * @return array<string,array<string,bool>>
 */
function wp_mylinks_brand_mark_tags() {
	return array(
		'svg'    => array(
			'class'       => true,
			'viewbox'     => true,
			'aria-hidden' => true,
			'focusable'   => true,
			'xmlns'       => true,
		),
		'circle' => array(
			'cx'   => true,
			'cy'   => true,
			'r'    => true,
			'fill' => true,
		),
		'rect'   => array(
			'x'      => true,
			'y'      => true,
			'width'  => true,
			'height' => true,
			'rx'     => true,
			'fill'   => true,
		),
	);
}

/**
 * Render the Settings page (all tabs).
 */
function wp_mylinks_admin_page() {
	// Tab key => [ dashicon, label, optional render callable ]. Support stays
	// last (house convention); keys are stable since 1.0.x so bookmarks and
	// deep links keep resolving.
	$tabs = array(
		'welcome'          => array( 'admin-home', __( 'Welcome', 'wp-mylinks' ) ),
		'global'           => array( 'admin-generic', __( 'General', 'wp-mylinks' ) ),
		'script'           => array( 'editor-code', __( 'Scripts', 'wp-mylinks' ) ),
		'tools'            => array( 'admin-tools', __( 'Tools', 'wp-mylinks' ) ),
		'tutorial_support' => array( 'sos', __( 'Support', 'wp-mylinks' ) ),
	);

	/**
	 * Filter the settings-page tabs.
	 *
	 * Each entry is keyed by its `?tab=` slug (sanitize_key form) and holds
	 * `array( dashicon-slug, label, render-callable )`; the callable is
	 * required for added tabs and ignored for the built-in four. This is the
	 * registration surface Pro uses for its settings tabs — by house
	 * convention, keep added tabs before 'tutorial_support'.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string,array> $tabs Tab definitions.
	 */
	$tabs = apply_filters( 'wp_mylinks_settings_tabs', $tabs );
	$tabs = is_array( $tabs ) ? $tabs : array();

	// Resolve the active tab against the (filtered) allowlist.
	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'welcome'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab navigation.
	if ( ! isset( $tabs[ $active_tab ] ) ) {
		$active_tab = 'welcome';
	}

	$base_url = admin_url( 'edit.php?post_type=mylink&page=welcome' );

	$version = defined( 'WP_MYLINKS_VERSION' ) ? WP_MYLINKS_VERSION : '';
	?>
	<div class="wrap wml-settings-wrap">

		<?php // Core relocates admin notices after this marker — above the header. ?>
		<hr class="wp-header-end" style="margin:0;border:0;">

		<div class="wml-settings-header">
			<div class="wml-settings-header-content">
				<div class="wml-settings-header-left">
					<?php // The plugin's own glyph. To go back to the generic WordPress icon, swap the echo for: <span class="dashicons dashicons-admin-links" aria-hidden="true"></span> ?>
					<div class="wml-settings-icon"><?php echo wp_kses( wp_mylinks_brand_mark(), wp_mylinks_brand_mark_tags() ); ?></div>
					<div>
						<h1><?php esc_html_e( 'WP MyLinks Settings', 'wp-mylinks' ); ?></h1>
						<p class="wml-settings-subtitle"><?php esc_html_e( 'Self-hosted link-in-bio pages on your own domain', 'wp-mylinks' ); ?></p>
					</div>
				</div>
				<?php if ( '' !== $version ) : ?>
					<span class="wml-version-badge">v<?php echo esc_html( $version ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<nav class="wml-tabs" aria-label="<?php esc_attr_e( 'WP MyLinks settings tabs', 'wp-mylinks' ); ?>">
			<?php foreach ( $tabs as $tab_key => $tab ) : ?>
				<?php
				$tab_url   = 'welcome' === $tab_key ? $base_url : add_query_arg( 'tab', $tab_key, $base_url );
				$is_active = ( $tab_key === $active_tab );
				?>
				<a href="<?php echo esc_url( $tab_url ); ?>"
					class="wml-tabs__tab<?php echo $is_active ? ' wml-tabs__tab--active' : ''; ?>"
					<?php echo $is_active ? 'aria-current="page"' : ''; ?>>
					<span class="dashicons dashicons-<?php echo esc_attr( $tab[0] ); ?>" aria-hidden="true"></span><?php echo esc_html( $tab[1] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<div class="wml-settings-content">
			<?php
			switch ( $active_tab ) {
				case 'script':
					wp_mylinks_render_script_tab();
					break;
				case 'tools':
					wp_mylinks_render_tools_tab();
					break;
				case 'global':
					wp_mylinks_render_global_tab();
					break;
				case 'tutorial_support':
					wp_mylinks_render_support_tab();
					break;
				case 'welcome':
					wp_mylinks_render_welcome_tab();
					break;
				default:
					// A filter-registered tab: its definition carries the renderer.
					if ( isset( $tabs[ $active_tab ][2] ) && is_callable( $tabs[ $active_tab ][2] ) ) {
						call_user_func( $tabs[ $active_tab ][2] );
					} else {
						wp_mylinks_render_welcome_tab();
					}
					break;
			}
			?>
		</div>
	</div>
	<?php
}

/**
 * Render the Welcome tab: Quick Start card + "ways to use" checklist card
 * (the house Welcome-tab pattern), the video tutorial, and the permalink
 * troubleshooting card.
 */
function wp_mylinks_render_welcome_tab() {
	$new_mylink_url = esc_url( admin_url( 'post-new.php?post_type=mylink' ) );
	$general_url    = esc_url( admin_url( 'edit.php?post_type=mylink&page=welcome&tab=global' ) );
	?>
	<div class="wml-settings-grid wml-settings-grid--2col">

		<?php wp_mylinks_card_open( 'controls-play', __( 'Quick Start', 'wp-mylinks' ) ); ?>
			<p><?php esc_html_e( 'Thank you for choosing WP MyLinks — self-hosted link-in-bio pages on your own domain. Your first page is five steps away:', 'wp-mylinks' ); ?></p>
			<ol class="wml-steps">
				<li><?php esc_html_e( 'Go to MyLinks → Add New MyLink', 'wp-mylinks' ); ?></li>
				<li><?php esc_html_e( 'Set up your profile: avatar, name, description, and social links', 'wp-mylinks' ); ?></li>
				<li><?php esc_html_e( 'Add unlimited links you want to share with your audience', 'wp-mylinks' ); ?></li>
				<li><?php esc_html_e( 'Choose one of the built-in themes to match your brand', 'wp-mylinks' ); ?></li>
				<li><?php esc_html_e( 'Publish, then paste your new URL into every bio', 'wp-mylinks' ); ?></li>
			</ol>
			<p><a href="<?php echo $new_mylink_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?>" class="wml-button-primary"><?php esc_html_e( 'Create your first MyLink', 'wp-mylinks' ); ?></a></p>
		<?php wp_mylinks_card_close(); ?>

		<?php wp_mylinks_card_open( 'yes-alt', __( 'Ways to Use WP MyLinks', 'wp-mylinks' ) ); ?>
			<ul class="wml-check">
				<li><span class="dashicons dashicons-yes" aria-hidden="true"></span><?php esc_html_e( 'Unlimited bio pages — one per brand, campaign, or team member', 'wp-mylinks' ); ?></li>
				<li><span class="dashicons dashicons-yes" aria-hidden="true"></span><?php esc_html_e( 'Your own domain: yoursite.com/me instead of a rented short link', 'wp-mylinks' ); ?></li>
				<li><span class="dashicons dashicons-yes" aria-hidden="true"></span><?php esc_html_e( 'Buttons, image cards, YouTube, TikTok, Spotify, and more', 'wp-mylinks' ); ?></li>
				<li><span class="dashicons dashicons-yes" aria-hidden="true"></span><?php esc_html_e( 'SEO built in: Schema.org JSON-LD, Open Graph, and Twitter Cards', 'wp-mylinks' ); ?></li>
				<li><span class="dashicons dashicons-yes" aria-hidden="true"></span><?php esc_html_e( 'Use a MyLink page as your site’s front page', 'wp-mylinks' ); ?></li>
			</ul>
			<p><a href="<?php echo $general_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?>" class="wml-button-secondary"><?php esc_html_e( 'Open General settings', 'wp-mylinks' ); ?></a></p>
		<?php wp_mylinks_card_close(); ?>

		<?php wp_mylinks_card_open( 'video-alt3', __( 'Watch the Complete Overview and Tutorial', 'wp-mylinks' ) ); ?>
			<div class="wml-embed">
				<iframe src="https://www.youtube.com/embed/?listType=playlist&list=PLwazGJFvaLnCZrBRuDeDsbkpjjOPKz4pC" title="<?php esc_attr_e( 'WP MyLinks Tutorial', 'wp-mylinks' ); ?>" allowfullscreen></iframe>
			</div>
		<?php wp_mylinks_card_close(); ?>

		<?php
		wp_mylinks_card_open(
			'sos',
			__( 'Seeing a 404 on your MyLink page?', 'wp-mylinks' ),
			__( 'This is the most common first-run hiccup, and the fix takes ten seconds.', 'wp-mylinks' )
		);
		?>
			<ol class="wml-steps">
				<li>
					<?php
					echo wp_kses(
						__( 'Go to <b>Settings</b> → <a href="options-permalink.php"><b>Permalinks</b></a>.', 'wp-mylinks' ),
						array(
							'b' => array(),
							'a' => array( 'href' => array() ),
						)
					);
					?>
				</li>
				<li>
					<?php
					echo wp_kses(
						__( 'Click the <b>Save Changes</b> button without changing anything.', 'wp-mylinks' ),
						array( 'b' => array() )
					);
					?>
				</li>
				<li><?php esc_html_e( 'Reload your MyLink page. The issue will most likely disappear.', 'wp-mylinks' ); ?></li>
			</ol>
			<p class="wml-field__help"><?php esc_html_e( 'If the problem persists, make sure you are using pretty permalinks (Post name). Careful: changing an established permalink structure affects all your URLs and is bad for SEO.', 'wp-mylinks' ); ?></p>
		<?php wp_mylinks_card_close(); ?>

	</div>

	<?php echo do_shortcode( '[donate]' ); ?>
	<?php wp_mylinks_render_credit_line(); ?>
	<?php
}

/**
 * Render the General tab (settings group: mylinks-global).
 */
function wp_mylinks_render_global_tab() {
	$theme_options = (string) get_option( 'mylinks_theme' );
	$themes        = wp_mylinks_theme_callback();
	// Drop the 'none' option from the global theme list (only valid per-post).
	unset( $themes['none'] );
	?>
	<form method="post" action="options.php">
		<?php
		settings_errors();
		settings_fields( 'mylinks-global' );
		do_settings_sections( 'mylinks-global' );

		echo '<div class="wml-settings-grid">';

		wp_mylinks_card_open(
			'admin-appearance',
			__( 'Display', 'wp-mylinks' ),
			__( 'Unless overridden on an individual MyLink page, these settings apply globally.', 'wp-mylinks' )
		);
		?>
			<div class="wml-field">
				<label class="wml-field__label" for="mylinks_theme"><?php esc_html_e( 'Global Theme', 'wp-mylinks' ); ?></label>
				<select id="mylinks_theme" name="mylinks_theme">
					<?php foreach ( $themes as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $theme_options, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="wml-field__help"><?php esc_html_e( 'Set the theme for the MyLinks page.', 'wp-mylinks' ); ?></p>
			</div>
			<?php
			$accent_options = array(
				'wp_mylinks_accent_bg'          => __( 'Accent: Page Background', 'wp-mylinks' ),
				'wp_mylinks_accent_button_bg'   => __( 'Accent: Button Background', 'wp-mylinks' ),
				'wp_mylinks_accent_button_text' => __( 'Accent: Button Text', 'wp-mylinks' ),
				'wp_mylinks_accent_text'        => __( 'Accent: Text Color', 'wp-mylinks' ),
			);
			?>
			<div class="wml-field">
				<span class="wml-field__label"><?php esc_html_e( 'Accent Colors', 'wp-mylinks' ); ?></span>
				<p class="wml-field__help" style="margin-block-end:10px;"><?php esc_html_e( 'Optional overrides applied on top of every theme, site-wide. Leave a color empty to keep each theme as designed. Individual MyLink pages can override these.', 'wp-mylinks' ); ?></p>
				<?php foreach ( $accent_options as $accent_option => $accent_label ) : ?>
					<div class="wml-field">
						<label class="wml-field__label" for="<?php echo esc_attr( $accent_option ); ?>"><?php echo esc_html( $accent_label ); ?></label>
						<input type="text" class="wml-color" id="<?php echo esc_attr( $accent_option ); ?>" name="<?php echo esc_attr( $accent_option ); ?>" value="<?php echo esc_attr( get_option( $accent_option, '' ) ); ?>">
					</div>
				<?php endforeach; ?>
			</div>
			<div class="wml-field">
				<label class="wml-field__label" for="wp_mylinks_font_family"><?php esc_html_e( 'Font Family', 'wp-mylinks' ); ?></label>
				<input type="text" id="wp_mylinks_font_family" name="wp_mylinks_font_family" class="wml-field__input" value="<?php echo esc_attr( get_option( 'wp_mylinks_font_family', '' ) ); ?>" placeholder="Poppins, sans-serif">
				<p class="wml-field__help">
					<?php
					echo wp_kses(
						__( 'Optional site-wide font for every MyLinks page. Name a font already available on your site, e.g. <code>Poppins, sans-serif</code>. WP MyLinks does not fetch fonts from third parties, to keep your visitors\' data private — load a web font through your theme or the <strong>Custom Styles</strong> field on the Scripts tab, then name it here. Individual pages can override this.', 'wp-mylinks' ),
						array(
							'code'   => array(),
							'strong' => array(),
						)
					);
					?>
				</p>
			</div>
			<?php
			wp_mylinks_toggle_row(
				'wp_mylinks_credits',
				__( 'Yes, I Definitely Want to Support You', 'wp-mylinks' ),
				__( 'This will add credits on the footer of MyLinks page.', 'wp-mylinks' )
			);
			wp_mylinks_toggle_row(
				'wp_mylinks_hide_notice',
				__( "Everything's Alright, Hide Notice Now", 'wp-mylinks' ),
				__( 'This will hide admin notice to flush your permalinks.', 'wp-mylinks' )
			);
			wp_mylinks_card_close();

			wp_mylinks_card_open(
				'search',
				__( 'Meta Tags', 'wp-mylinks' ),
				__( 'Shown on search engine results and the browser tab. If you use Yoast SEO or the per-page <strong>Setup Meta Tags</strong> form and already set both the <code>meta title</code> and <code>description</code> on a MyLink, those win over these values.', 'wp-mylinks' )
			);
			?>
			<div class="wml-field">
				<label class="wml-field__label" for="mylinks_meta_title"><?php esc_html_e( 'Meta Title', 'wp-mylinks' ); ?></label>
				<input type="text" id="mylinks_meta_title" name="mylinks_meta_title" class="wml-field__input" value="<?php echo esc_attr( get_option( 'mylinks_meta_title' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Your MyLinks Title | Your Site Title', 'wp-mylinks' ); ?>">
				<p class="wml-field__help"><?php esc_html_e( 'Set the meta title for the MyLinks page.', 'wp-mylinks' ); ?></p>
			</div>
			<div class="wml-field">
				<label class="wml-field__label" for="mylinks_meta_description"><?php esc_html_e( 'Meta Description', 'wp-mylinks' ); ?></label>
				<textarea id="mylinks_meta_description" name="mylinks_meta_description" class="wml-field__input" rows="4"><?php echo esc_textarea( get_option( 'mylinks_meta_description' ) ); ?></textarea>
				<p class="wml-field__help"><?php esc_html_e( 'Set the meta description of the MyLinks page.', 'wp-mylinks' ); ?></p>
			</div>
			<div class="wml-field">
				<label class="wml-field__label" for="mylinks_upload_favicon"><?php esc_html_e( 'Custom Favicon', 'wp-mylinks' ); ?></label>
				<div class="wml-input-row">
					<input id="mylinks_upload_favicon" class="wml-field__input wp-mylinks-uploader-url" type="text" name="mylinks_upload_favicon" value="<?php echo esc_attr( get_option( 'mylinks_upload_favicon' ) ); ?>" />
					<input id="upload_image_button" type="button" class="wml-button-secondary" value="<?php esc_attr_e( 'Choose Favicon', 'wp-mylinks' ); ?>" />
				</div>
				<p class="wml-field__help"><?php esc_html_e( 'Set a favicon for the MyLinks page.', 'wp-mylinks' ); ?></p>
			</div>
			<?php
			wp_mylinks_toggle_row(
				'wp_mylinks_noindex',
				__( 'Yes, Set to <code>noindex</code>', 'wp-mylinks' ),
				__( 'This will prevent MyLinks page from being indexed on search engine.', 'wp-mylinks' )
			);
			wp_mylinks_toggle_row(
				'wp_mylinks_nofollow',
				__( 'Yes, Set to <code>nofollow</code>', 'wp-mylinks' ),
				__( 'This will ban crawlers to follow all the links on the MyLinks page.', 'wp-mylinks' )
			);
			wp_mylinks_card_close();

			wp_mylinks_card_open(
				'media-code',
				__( 'Schema.org Structured Data', 'wp-mylinks' ),
				__( 'Output <code>Person</code> or <code>Organization</code> JSON-LD in the <code>&lt;head&gt;</code> of every MyLinks page so search engines can identify you. If Yoast SEO is active and emitting its own schema, this defers to Yoast automatically. You can override the type per page on the MyLink editor.', 'wp-mylinks' )
			);
			wp_mylinks_toggle_row(
				'wp_mylinks_enable_schema',
				__( 'Yes, Output Schema.org JSON-LD on All MyLinks Pages', 'wp-mylinks' ),
				__( 'When enabled, every MyLinks page will include structured data describing you (name, image, description, social profiles).', 'wp-mylinks' )
			);
			$schema_type = (string) get_option( 'wp_mylinks_schema_type', 'Person' );
			?>
			<div class="wml-field">
				<label class="wml-field__label" for="wp_mylinks_schema_type"><?php esc_html_e( 'Default Schema Type', 'wp-mylinks' ); ?></label>
				<select id="wp_mylinks_schema_type" name="wp_mylinks_schema_type">
					<option value="Person" <?php selected( $schema_type, 'Person' ); ?>><?php esc_html_e( 'Person (default)', 'wp-mylinks' ); ?></option>
					<option value="Organization" <?php selected( $schema_type, 'Organization' ); ?>><?php esc_html_e( 'Organization', 'wp-mylinks' ); ?></option>
				</select>
				<p class="wml-field__help"><?php esc_html_e( 'Use Person for personal bio pages. Use Organization for businesses, brands, or restaurants. Each MyLink page can override this individually.', 'wp-mylinks' ); ?></p>
			</div>
			<?php
			wp_mylinks_toggle_row(
				'wp_mylinks_enable_profilepage',
				__( 'Yes, Also Emit ProfilePage Schema Wrapper', 'wp-mylinks' ),
				__( 'When enabled, the JSON-LD output is structured as a graph containing both the Person/Organization and a ProfilePage wrapper that points to it. This is the pattern used by major social profile pages (X, GitHub, LinkedIn) and helps search engines and AI tools recognize this URL as a canonical profile page.', 'wp-mylinks' )
			);
			wp_mylinks_card_close();

			wp_mylinks_card_open(
				'share',
				__( 'Open Graph & Twitter Card', 'wp-mylinks' ),
				__( 'Output <code>og:*</code> and <code>twitter:*</code> meta tags so social-media share previews look correct. If Yoast SEO is active and handling Open Graph itself, this defers to Yoast automatically. You can override the share image per page on the MyLink editor.', 'wp-mylinks' )
			);
			wp_mylinks_toggle_row(
				'wp_mylinks_enable_og',
				__( 'Yes, Output OG & Twitter Tags on All MyLinks Pages', 'wp-mylinks' ),
				__( 'When enabled, share previews on Facebook, X (Twitter), LinkedIn, and Discord will show your MyLink page properly.', 'wp-mylinks' )
			);
			?>
			<div class="wml-field">
				<label class="wml-field__label" for="wp_mylinks_og_image"><?php esc_html_e( 'Default Share Image', 'wp-mylinks' ); ?></label>
				<div class="wml-input-row">
					<input id="wp_mylinks_og_image" type="text" name="wp_mylinks_og_image" class="wml-field__input wp-mylinks-uploader-url" value="<?php echo esc_attr( get_option( 'wp_mylinks_og_image' ) ); ?>" />
					<input id="wp_mylinks_og_image_button" type="button" class="wml-button-secondary wp-mylinks-og-image-uploader" value="<?php esc_attr_e( 'Choose Image', 'wp-mylinks' ); ?>" />
				</div>
				<p class="wml-field__help"><?php esc_html_e( 'Recommended size: 1200×630 pixels. If left empty, each MyLink page will fall back to its avatar (which may appear cropped on social previews).', 'wp-mylinks' ); ?></p>
			</div>
			<div class="wml-field">
				<label class="wml-field__label" for="wp_mylinks_twitter_handle"><?php esc_html_e( 'Twitter / X Handle', 'wp-mylinks' ); ?></label>
				<input type="text" id="wp_mylinks_twitter_handle" name="wp_mylinks_twitter_handle" class="wml-field__input" value="<?php echo esc_attr( get_option( 'wp_mylinks_twitter_handle' ) ); ?>" placeholder="@yourhandle">
				<p class="wml-field__help"><?php esc_html_e( 'Optional. Used as twitter:site and twitter:creator. The @ is added automatically if missing.', 'wp-mylinks' ); ?></p>
			</div>
			<?php
			wp_mylinks_card_close();

			echo '</div>';
			wp_mylinks_save_bar();
			?>
	</form>
	<?php
}

/**
 * Render the Scripts tab (settings group: mylinks-custom-scripts).
 */
function wp_mylinks_render_script_tab() {
	?>
	<form method="post" action="options.php">
		<?php
		settings_errors();
		settings_fields( 'mylinks-custom-scripts' );
		do_settings_sections( 'mylinks-custom-scripts' );

		// On multisite, site administrators hold manage_options but not
		// unfiltered_html, so any <script> they paste here is stripped on save.
		// Say so plainly rather than let it vanish silently. Custom CSS is
		// unaffected. No-op on single-site, where admins hold the capability.
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			echo '<div class="notice notice-warning inline" style="margin:0 0 20px;"><p>';
			echo esc_html__( 'Your account can save custom CSS, but raw scripts require the unfiltered_html capability — on a multisite network only a network administrator has it, so any <script> tags you add here will be removed when saved.', 'wp-mylinks' );
			echo '</p></div>';
		}

		echo '<div class="wml-settings-grid wml-settings-grid--2col">';

		wp_mylinks_card_open(
			'chart-bar',
			__( 'Analytics Tracking Scripts', 'wp-mylinks' ),
			__( 'Track how the MyLinks page performs with Google Analytics and any other analytics scripts.', 'wp-mylinks' )
		);
		wp_mylinks_script_row(
			'wp_mylinks_analytics',
			__( 'Analytics Script', 'wp-mylinks' ),
			__( 'Please include the <code>&lt;script&gt;</code>...<code>&lt;/script&gt;</code> tags.', 'wp-mylinks' ),
			10
		);
		wp_mylinks_card_close();

		wp_mylinks_card_open(
			'admin-appearance',
			__( 'Custom Styles', 'wp-mylinks' ),
			__( 'You can set custom styles for the MyLinks page.', 'wp-mylinks' )
		);
		wp_mylinks_script_row(
			'wp_mylinks_custom_css',
			__( 'Custom CSS', 'wp-mylinks' ),
			__( 'Add your custom css code <b>without</b> the <code>&lt;style&gt;</code> tag.', 'wp-mylinks' ),
			10
		);
		wp_mylinks_card_close();

		wp_mylinks_card_open(
			'editor-code',
			__( 'Custom Scripts', 'wp-mylinks' ),
			__( 'You can put about anything you want from Google Tag Manager to Facebook Pixel script in the header and footer sections of the MyLinks page.', 'wp-mylinks' ),
			true
		);
		echo '<div class="wml-script-columns">';
		wp_mylinks_script_row(
			'wp_mylinks_header_script',
			__( 'Header', 'wp-mylinks' ),
			__( 'Anything you put here will be included in <code>&lt;head&gt;</code>. Please include <code>&lt;script&gt;</code> etc.', 'wp-mylinks' ),
			10
		);
		wp_mylinks_script_row(
			'wp_mylinks_open_body_script',
			__( 'After Body Tag', 'wp-mylinks' ),
			__( 'Inserted script will be placed after the opening <code>&lt;body&gt;</code> tag. Please include the <code>&lt;script&gt;</code>...<code>&lt;/script&gt;</code> tags', 'wp-mylinks' ),
			10
		);
		wp_mylinks_script_row(
			'wp_mylinks_footer_script',
			__( 'Footer', 'wp-mylinks' ),
			__( 'Anything you put here will be placed just before <code>&lt;/body&gt;</code>. Please include <code>&lt;script&gt;</code> etc.', 'wp-mylinks' ),
			10
		);
		echo '</div>';
		wp_mylinks_card_close();

		wp_mylinks_card_open(
			'admin-plugins',
			__( 'Dequeue Other Scripts and Styles', 'wp-mylinks' ),
			__( '<strong>Experimental!</strong> Some plugins might add additional scripts and styles into the MyLink page, which could result in display issues. By activating this feature, this plugin will forcibly remove all scripts and styles added by other plugins. Should you encounter issues like missing images or styling problems, you may need to enable this.', 'wp-mylinks' ),
			true
		);
		wp_mylinks_toggle_row(
			'wp_mylinks_dequeue',
			__( 'Dequeue All Scripts and Styles', 'wp-mylinks' ),
			__( "This will dequeue other plugins' scripts and styles only on MyLink page.", 'wp-mylinks' )
		);
		wp_mylinks_card_close();

		echo '</div>';
		wp_mylinks_save_bar();
		?>
	</form>
	<?php
}

/**
 * Render the Tools tab: import/export plus data management.
 *
 * Export and import post to admin-post.php (handlers in
 * Wp_Mylinks_Tools); the uninstall flag saves through its own settings
 * group (mylinks-tools) so the General form can never reset it.
 */
function wp_mylinks_render_tools_tab() {
	$mylinks = get_posts(
		array(
			'post_type'      => 'mylink',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	?>
	<div class="wml-settings-grid wml-settings-grid--2col">

		<?php
		wp_mylinks_card_open(
			'download',
			__( 'Export', 'wp-mylinks' ),
			__( 'Download your configuration as JSON files you can keep as backups or import on another site. Images are referenced by URL, not bundled.', 'wp-mylinks' )
		);
		?>
		<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wml-tool-form">
			<input type="hidden" name="action" value="wp_mylinks_export_settings">
			<?php wp_nonce_field( 'wp_mylinks_export' ); ?>
			<div class="wml-field">
				<span class="wml-field__label"><?php esc_html_e( 'Plugin Settings', 'wp-mylinks' ); ?></span>
				<p class="wml-field__help"><?php esc_html_e( 'Everything on the General, Scripts, and Tools tabs.', 'wp-mylinks' ); ?></p>
				<button type="submit" class="wml-button-secondary">
					<span class="dashicons dashicons-download" aria-hidden="true"></span>
					<?php esc_html_e( 'Export Settings', 'wp-mylinks' ); ?>
				</button>
			</div>
		</form>

		<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wml-tool-form">
			<input type="hidden" name="action" value="wp_mylinks_export_pages">
			<?php wp_nonce_field( 'wp_mylinks_export' ); ?>
			<div class="wml-field">
				<label class="wml-field__label" for="wml-export-status"><?php esc_html_e( 'MyLink Pages', 'wp-mylinks' ); ?></label>
				<p class="wml-field__help"><?php esc_html_e( 'Every page with its full configuration, filtered by status.', 'wp-mylinks' ); ?></p>
				<div class="wml-input-row">
					<select id="wml-export-status" name="status">
						<option value="any"><?php esc_html_e( 'All statuses', 'wp-mylinks' ); ?></option>
						<option value="publish"><?php esc_html_e( 'Published', 'wp-mylinks' ); ?></option>
						<option value="draft"><?php esc_html_e( 'Draft', 'wp-mylinks' ); ?></option>
						<option value="pending"><?php esc_html_e( 'Pending', 'wp-mylinks' ); ?></option>
						<option value="private"><?php esc_html_e( 'Private', 'wp-mylinks' ); ?></option>
					</select>
					<button type="submit" class="wml-button-secondary">
						<span class="dashicons dashicons-download" aria-hidden="true"></span>
						<?php esc_html_e( 'Export Pages', 'wp-mylinks' ); ?>
					</button>
				</div>
				<label class="wml-checkbox-row">
					<input type="checkbox" name="include_collections" value="1" checked>
					<?php esc_html_e( 'Include Link Collections', 'wp-mylinks' ); ?>
				</label>
			</div>
		</form>

		<?php if ( ! empty( $mylinks ) ) : ?>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wml-tool-form">
				<input type="hidden" name="action" value="wp_mylinks_export_page">
				<div class="wml-field">
					<label class="wml-field__label" for="wml-export-single"><?php esc_html_e( 'Single Page', 'wp-mylinks' ); ?></label>
					<p class="wml-field__help"><?php esc_html_e( 'One page with its full configuration. Also available on each MyLink editor screen.', 'wp-mylinks' ); ?></p>
					<div class="wml-input-row">
						<select id="wml-export-single" name="post_id" class="wml-export-single">
							<?php foreach ( $mylinks as $wml_post ) : ?>
								<option value="<?php echo esc_attr( $wml_post->ID ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_mylinks_export_page_' . $wml_post->ID ) ); ?>">
									<?php echo esc_html( $wml_post->post_title ? $wml_post->post_title : ( '#' . $wml_post->ID ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<button type="submit" class="wml-button-secondary">
							<span class="dashicons dashicons-download" aria-hidden="true"></span>
							<?php esc_html_e( 'Export Page', 'wp-mylinks' ); ?>
						</button>
					</div>
				</div>
			</form>
		<?php endif; ?>
		<?php wp_mylinks_card_close(); ?>

		<?php
		wp_mylinks_card_open(
			'upload',
			__( 'Import', 'wp-mylinks' ),
			__( 'Upload a WP MyLinks export file. Settings files overwrite the matching options; page files are always imported as <strong>new</strong> pages, so nothing existing is overwritten.', 'wp-mylinks' )
		);
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="wml-tool-form">
			<input type="hidden" name="action" value="wp_mylinks_import">
			<?php wp_nonce_field( 'wp_mylinks_import' ); ?>
			<div class="wml-field">
				<span class="wml-field__label"><?php esc_html_e( 'Export File (.json)', 'wp-mylinks' ); ?></span>
				<p class="wml-field__help"><?php esc_html_e( 'Accepts any WP MyLinks export: settings, pages, or a single page.', 'wp-mylinks' ); ?></p>
				<label class="wml-file-drop">
					<input type="file" id="wml-import-file" name="wp_mylinks_import_file" accept=".json,application/json" required>
					<span class="wml-file-drop__icon dashicons dashicons-upload" aria-hidden="true"></span>
					<span class="wml-file-drop__text">
						<strong><?php esc_html_e( 'Choose a .json export file', 'wp-mylinks' ); ?></strong>
						<small><?php esc_html_e( 'or drag and drop it here', 'wp-mylinks' ); ?></small>
					</span>
				</label>
				<button type="submit" class="wml-button-primary">
					<span class="dashicons dashicons-upload" aria-hidden="true"></span>
					<?php esc_html_e( 'Import', 'wp-mylinks' ); ?>
				</button>
			</div>
		</form>
		<?php wp_mylinks_card_close(); ?>

		<?php
		$wml_selectable = wp_mylinks_selectable_link_post_types();
		$wml_selected   = wp_mylinks_get_link_post_types();
		wp_mylinks_card_open(
			'admin-links',
			__( 'Link URL Sources', 'wp-mylinks' ),
			__( 'Choose which post types appear in the <strong>Link URL</strong> picker when you build a MyLink page. Link Collections are always available.', 'wp-mylinks' )
		);
		?>
		<form method="post" action="options.php" class="wml-tool-form">
			<?php settings_fields( 'mylinks-link-sources' ); ?>
			<div class="wml-field">
				<span class="wml-field__label"><?php esc_html_e( 'Included Post Types', 'wp-mylinks' ); ?></span>
				<p class="wml-field__help"><?php esc_html_e( 'New public post types registered by plugins or themes appear here automatically. Uncheck any you do not want to link to.', 'wp-mylinks' ); ?></p>
				<div class="wml-posttype-grid">
					<?php foreach ( $wml_selectable as $wml_slug => $wml_label ) : ?>
						<label class="wml-posttype">
							<input type="checkbox" name="wp_mylinks_link_post_types[]" value="<?php echo esc_attr( $wml_slug ); ?>" <?php checked( in_array( $wml_slug, $wml_selected, true ) ); ?>>
							<span class="wml-posttype__body">
								<span class="wml-posttype__name"><?php echo esc_html( $wml_label ); ?></span>
								<code class="wml-posttype__slug"><?php echo esc_html( $wml_slug ); ?></code>
							</span>
						</label>
					<?php endforeach; ?>
					<label class="wml-posttype wml-posttype--locked" title="<?php esc_attr_e( 'Link Collections are always available and cannot be turned off.', 'wp-mylinks' ); ?>">
						<input type="checkbox" checked disabled>
						<span class="wml-posttype__body">
							<span class="wml-posttype__name"><?php esc_html_e( 'Link Collections', 'wp-mylinks' ); ?></span>
							<code class="wml-posttype__slug">mylinks-collection</code>
						</span>
					</label>
				</div>
			</div>
			<button type="submit" class="wml-button-primary"><?php esc_html_e( 'Save Changes', 'wp-mylinks' ); ?></button>
		</form>
		<?php wp_mylinks_card_close(); ?>

		<?php
		wp_mylinks_card_open(
			'database',
			__( 'Data Management', 'wp-mylinks' ),
			__( 'What happens to your data when the plugin is removed.', 'wp-mylinks' )
		);
		?>
		<form method="post" action="options.php" class="wml-tool-form">
			<?php settings_fields( 'mylinks-tools' ); ?>
			<?php
			wp_mylinks_toggle_row(
				'wp_mylinks_delete_data_on_uninstall',
				__( 'Yes, Delete All Plugin Data on Uninstall', 'wp-mylinks' ),
				__( 'When enabled, deleting the plugin from the Plugins screen will remove all WP MyLinks settings and oEmbed caches. MyLink posts and their content are preserved by default. Leave this unchecked to keep your settings if you ever uninstall and reinstall.', 'wp-mylinks' )
			);
			?>
			<button type="submit" class="wml-button-primary"><?php esc_html_e( 'Save Changes', 'wp-mylinks' ); ?></button>
		</form>
		<?php wp_mylinks_card_close(); ?>

	</div>
	<?php
}

/**
 * Render the Support tab.
 */
function wp_mylinks_render_support_tab() {
	// Author / project URLs (tracked — our own properties only).
	$walterpinem_me_url  = wpmylinks_url( 'https://walterpinem.me/' );
	$walterpinem_com_url = wpmylinks_url( 'https://walterpinem.com/' );
	$onlinestorekit_url  = wpmylinks_url( 'https://www.onlinestorekit.com/' );
	$free_tools_url      = wpmylinks_url( 'https://walterpinem.me/projects/tools/' );
	$contact_url         = wpmylinks_url( 'https://www.onlinestorekit.com/support/' );

	// External URLs we don't track (third-party platforms).
	$video_tutorial_url = 'https://www.youtube.com/watch?v=WK03GS5rM0Q&list=PLwazGJFvaLnCZrBRuDeDsbkpjjOPKz4pC';
	$review_url         = 'https://wordpress.org/support/plugin/wp-mylinks/reviews/?rate=5#new-post';

	$links = array(
		array( $contact_url, __( 'Support & Feature Request', 'wp-mylinks' ) ),
		array( $video_tutorial_url, __( 'Video Tutorial', 'wp-mylinks' ) ),
		array( $review_url, __( 'Leave a Review', 'wp-mylinks' ) ),
		array( $walterpinem_me_url, __( 'walterpinem.me', 'wp-mylinks' ) ),
		array( $walterpinem_com_url, __( 'walterpinem.com', 'wp-mylinks' ) ),
		array( $onlinestorekit_url, __( 'Online Store Kit', 'wp-mylinks' ) ),
		array( $free_tools_url, __( '240+ Free Online Tools', 'wp-mylinks' ) ),
	);
	?>
	<div class="wml-settings-grid">

		<?php
		wp_mylinks_card_open(
			'sos',
			__( 'WP MyLinks is Waiting for Your Feedback', 'wp-mylinks' ),
			__( 'This plugin is open source and shaped by its users — feature requests, bug reports, and reviews all directly steer the roadmap.', 'wp-mylinks' )
		);
		?>
			<ul class="wml-check">
				<?php foreach ( $links as $link ) : ?>
					<li>
						<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
						<a href="<?php echo esc_url( $link[0] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $link[1] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="wml-field__help">
				<?php
				printf(
					/* translators: %s: author name */
					esc_html__( 'Author: %s', 'wp-mylinks' ),
					'<strong>Walter Pinem</strong>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup.
				);
				?>
			</p>
		<?php wp_mylinks_card_close(); ?>

		<?php wp_mylinks_card_open( 'heart', __( 'Support the Project', 'wp-mylinks' ) ); ?>
			<?php echo do_shortcode( '[donate]' ); ?>
			<?php wp_mylinks_render_credit_line(); ?>
		<?php wp_mylinks_card_close(); ?>

	</div>
	<?php
}
