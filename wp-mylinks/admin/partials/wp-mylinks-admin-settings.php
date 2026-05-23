<?php
/**
 * Create the plugin Settings page.
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
 * Used internally on the Welcome and Support tabs via [donate].
 *
 * @param array       $atts    Shortcode attributes (unused).
 * @param string|null $content Shortcode content (unused).
 * @return string
 */
function wp_mylinks_donate_button_shortcode( $atts, $content = null ) {
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
 * Register settings for the Global and Custom Script tabs.
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
	register_setting( 'mylinks-global', 'wp_mylinks_delete_data_on_uninstall', array( 'sanitize_callback' => 'wp_mylinks_sanitize_yes_or_empty' ) );

	// Schema.org JSON-LD (1.0.8+).
	register_setting( 'mylinks-global', 'wp_mylinks_enable_schema', array( 'sanitize_callback' => 'wp_mylinks_sanitize_yes_or_empty' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_schema_type', array( 'sanitize_callback' => 'wp_mylinks_sanitize_schema_type' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_enable_profilepage', array( 'sanitize_callback' => 'wp_mylinks_sanitize_yes_or_empty' ) );

	// Open Graph / Twitter Card (1.0.8+).
	register_setting( 'mylinks-global', 'wp_mylinks_enable_og', array( 'sanitize_callback' => 'wp_mylinks_sanitize_yes_or_empty' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_og_image', array( 'sanitize_callback' => 'esc_url_raw' ) );
	register_setting( 'mylinks-global', 'wp_mylinks_twitter_handle', array( 'sanitize_callback' => 'wp_mylinks_sanitize_twitter_handle' ) );

	// Custom-script settings — accept raw script/style content by design.
	// We register an explicit no-op sanitize_callback so Plugin Check passes;
	// the manage_options capability gates who can save these in the first place.
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
 * Sanitize raw script / style content for the Custom Script tab.
 *
 * The Custom Script feature is intentionally raw — admins paste analytics
 * snippets, GTM containers, Facebook Pixel scripts, custom CSS, etc. Saving
 * is gated by the `manage_options` capability, and rendering at the front
 * end runs the value through `wp_kses()` against an explicit allowlist (see
 * `$wp_mylinks_allowed_script_tags` in the public template).
 *
 * This sanitizer therefore only:
 *   - rejects non-string values,
 *   - normalizes line endings,
 *   - strips low-byte / null characters that would only cause trouble.
 *
 * @since 1.0.8
 *
 * @param mixed $value Submitted value.
 * @return string
 */
if ( ! function_exists( 'wp_mylinks_sanitize_raw_script' ) ) {
	function wp_mylinks_sanitize_raw_script( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}
		// Strip only NULL bytes and other low-byte control chars (keep \n, \r, \t).
		$value = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value );
		// Normalize line endings to LF.
		$value = str_replace( array( "\r\n", "\r" ), "\n", (string) $value );
		return $value;
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

/**
 * Render the Settings page (all tabs).
 */
function wp_mylinks_admin_page() {
	// Resolve the active tab against an allowlist.
	$allowed_tabs = array( 'welcome', 'global', 'script', 'tutorial_support' );
	$active_tab   = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'welcome'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab navigation.
	if ( ! in_array( $active_tab, $allowed_tabs, true ) ) {
		$active_tab = 'welcome';
	}

	$base_url = admin_url( 'edit.php?post_type=mylink&page=welcome' );
	?>
	<div class="wrap wp_mylinks_pluginpage_title">
		<h1><?php esc_html_e( 'WP MyLinks', 'wp-mylinks' ); ?></h1>
		<hr>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( $base_url ); ?>" class="nav-tab <?php echo 'welcome' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Welcome', 'wp-mylinks' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'global', $base_url ) ); ?>" class="nav-tab <?php echo 'global' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Global', 'wp-mylinks' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'script', $base_url ) ); ?>" class="nav-tab <?php echo 'script' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Custom Script', 'wp-mylinks' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'tutorial_support', $base_url ) ); ?>" class="nav-tab <?php echo 'tutorial_support' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Support', 'wp-mylinks' ); ?></a>
		</h2>

		<?php
		switch ( $active_tab ) {
			case 'script':
				wp_mylinks_render_script_tab();
				break;
			case 'global':
				wp_mylinks_render_global_tab();
				break;
			case 'tutorial_support':
				wp_mylinks_render_support_tab();
				break;
			case 'welcome':
			default:
				wp_mylinks_render_welcome_tab();
				break;
		}
		?>
	</div>
	<?php
}

/**
 * Render the Custom Script tab.
 */
function wp_mylinks_render_script_tab() {
	?>
	<!-- Custom Script & Style -->
	<form method="post" action="options.php">
		<?php
		settings_errors();
		settings_fields( 'mylinks-custom-scripts' );
		do_settings_sections( 'mylinks-custom-scripts' );
		?>
		<h1 class="section_wp_mylinks"><?php esc_html_e( 'Custom Scripts & Styles', 'wp-mylinks' ); ?></h1>
		<p><?php esc_html_e( 'Add custom scripts and styles for the MyLinks page.', 'wp-mylinks' ); ?><br /></p>
		<hr>

		<h2 class="section_wp_mylinks"><?php esc_html_e( 'Analytics Tracking Scripts', 'wp-mylinks' ); ?></h2>
		<p><?php esc_html_e( 'Track how the MyLinks page performs with Google Analytics and any other analytics scripts.', 'wp-mylinks' ); ?><br /></p>
		<table class="form-table">
			<tbody>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="mylinks_misc" for="wp_mylinks_analytics"><b><?php esc_html_e( 'Analytics Script', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<textarea id="wp_mylinks_analytics" name="wp_mylinks_analytics" class="mylinks_input_textarea" rows="5"><?php echo esc_textarea( get_option( 'wp_mylinks_analytics' ) ); ?></textarea>
						<p class="input-description">
							<?php
							echo wp_kses(
								__( 'Please include the <code>&lt;script&gt;</code>...<code>&lt;/script&gt;</code> tags.', 'wp-mylinks' ),
								array( 'code' => array() )
							);
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<hr>

		<h2 class="section_wp_mylinks"><?php esc_html_e( 'Custom Scripts', 'wp-mylinks' ); ?></h2>
		<p>
			<?php
			echo wp_kses(
				__( 'You can put about anything you want from Google Tag Manager to Facebook Pixel script in the header and footer sections of the<br> MyLinks page.', 'wp-mylinks' ),
				array( 'br' => array() )
			);
			?>
			<br />
		</p>
		<table class="form-table">
			<tbody>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="mylinks_misc" for="wp_mylinks_header_script"><b><?php esc_html_e( 'Header', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<textarea id="wp_mylinks_header_script" name="wp_mylinks_header_script" class="mylinks_input_textarea" rows="5"><?php echo esc_textarea( get_option( 'wp_mylinks_header_script' ) ); ?></textarea>
						<p class="input-description">
							<?php
							echo wp_kses(
								__( 'Anything you put here will be included in <code>&lt;head&gt;</code>. Please include <code>&lt;script&gt;</code> etc.', 'wp-mylinks' ),
								array( 'code' => array() )
							);
							?>
						</p>
					</td>
				</tr>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="mylinks_misc" for="wp_mylinks_open_body_script"><b><?php esc_html_e( 'After Body Tag', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<textarea id="wp_mylinks_open_body_script" name="wp_mylinks_open_body_script" class="mylinks_input_textarea" rows="5"><?php echo esc_textarea( get_option( 'wp_mylinks_open_body_script' ) ); ?></textarea>
						<p class="input-description">
							<?php
							echo wp_kses(
								__( 'Inserted script will be placed after the opening <code>&lt;body&gt;</code> tag. Please include the <code>&lt;script&gt;</code>...<code>&lt;/script&gt;</code> tags', 'wp-mylinks' ),
								array( 'code' => array() )
							);
							?>
						</p>
					</td>
				</tr>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="mylinks_misc" for="wp_mylinks_footer_script"><b><?php esc_html_e( 'Footer', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<textarea id="wp_mylinks_footer_script" name="wp_mylinks_footer_script" class="mylinks_input_textarea" rows="5"><?php echo esc_textarea( get_option( 'wp_mylinks_footer_script' ) ); ?></textarea>
						<p class="input-description">
							<?php
							echo wp_kses(
								__( 'Anything you put here will be placed just before <code>&lt;/body&gt;</code>. Please include <code>&lt;script&gt;</code> etc.', 'wp-mylinks' ),
								array( 'code' => array() )
							);
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<hr>

		<h2 class="section_wp_mylinks"><?php esc_html_e( 'Custom Styles', 'wp-mylinks' ); ?></h2>
		<p>
			<?php esc_html_e( 'You can set custom styles for the MyLinks page.', 'wp-mylinks' ); ?>
			<br />
		</p>
		<table class="form-table">
			<tbody>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="mylinks_misc" for="wp_mylinks_custom_css"><b><?php esc_html_e( 'Custom CSS', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<textarea id="wp_mylinks_custom_css" name="wp_mylinks_custom_css" class="mylinks_input_textarea" rows="5"><?php echo esc_textarea( get_option( 'wp_mylinks_custom_css' ) ); ?></textarea>
						<p class="input-description">
							<?php
							echo wp_kses(
								__( 'Add your custom css code <b>without</b> the <code>&lt;style&gt;</code> tag.', 'wp-mylinks' ),
								array(
									'code' => array(),
									'b'    => array(),
								)
							);
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<hr>

		<h2 class="section_wp_mylinks"><?php esc_html_e( 'Dequeue Other Scripts and Styles', 'wp-mylinks' ); ?></h2>
		<p>
			<?php
			echo wp_kses(
				__( '<strong>Experimental!</strong> Some plugins might add additional scripts and styles into the MyLink page, which could result in display issues. By activating this feature, this plugin will forcibly remove all scripts and styles added by other plugins.', 'wp-mylinks' ),
				array( 'strong' => array() )
			);
			?>
			<br />
		</p>
		<p>
			<?php esc_html_e( 'Should you encounter issues like images not displaying properly, missing images, or styling problems, you may need to enable this feature.', 'wp-mylinks' ); ?>
			<br />
		</p>
		<table class="form-table">
			<tbody>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="wp_mylinks_dequeue" for="wp_mylinks_dequeue"><b><?php esc_html_e( 'Dequeue', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<input type="checkbox" id="wp_mylinks_dequeue" name="wp_mylinks_dequeue" class="my_links_checkbox" value="yes" <?php checked( get_option( 'wp_mylinks_dequeue' ), 'yes' ); ?>>
						<?php esc_html_e( 'Dequeue All Scripts and Styles', 'wp-mylinks' ); ?>
						<br>
						<p class="input-description">
							<?php esc_html_e( "This will dequeue other plugins' scripts and styles only on MyLink page.", 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<hr>
		<?php submit_button(); ?>
	</form>
	<!-- End - Custom Scripts & Styles -->
	<?php
}

/**
 * Render the Global tab.
 */
function wp_mylinks_render_global_tab() {
	$theme_options = (string) get_option( 'mylinks_theme' );
	$themes        = wp_mylinks_theme_callback();
	// Drop the 'none' option from the global theme list (only valid per-post).
	unset( $themes['none'] );
	?>
	<!-- Global Configurations -->
	<form method="post" action="options.php">
		<?php
		settings_errors();
		settings_fields( 'mylinks-global' );
		do_settings_sections( 'mylinks-global' );
		?>
		<h1 class="section_wp_mylinks"><?php esc_html_e( 'Global Configurations', 'wp-mylinks' ); ?></h1>
		<p><?php esc_html_e( 'Unless set on individual MyLinks page, below configurations will be implemented globally.', 'wp-mylinks' ); ?><br /></p>
		<hr>

		<h2 class="section_wp_mylinks"><?php esc_html_e( 'Display Settings', 'wp-mylinks' ); ?></h2>
		<p><?php esc_html_e( 'Determine how you want the MyLinks page to look like globally.', 'wp-mylinks' ); ?><br /></p>
		<table class="form-table">
			<tbody>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="mylinks_theme" for="mylinks_theme"><b><?php esc_html_e( 'Global Theme', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<select id="mylinks_theme" name="mylinks_theme" class="mylinks_input_select">
							<?php foreach ( $themes as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $theme_options, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="input-description"><?php esc_html_e( 'Set the theme for the MyLinks page.', 'wp-mylinks' ); ?></p>
					</td>
				</tr>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="wp_mylinks_credits" for="wp_mylinks_credits"><b><?php esc_html_e( 'Show Some ❤ to Support Me?', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<input type="checkbox" id="wp_mylinks_credits" name="wp_mylinks_credits" class="my_links_checkbox" value="yes" <?php checked( get_option( 'wp_mylinks_credits' ), 'yes' ); ?>>
						<?php esc_html_e( 'Yes, I Definitely Want to Support You', 'wp-mylinks' ); ?>
						<br>
						<p class="input-description">
							<?php esc_html_e( 'This will add credits on the footer of MyLinks page.', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="wp_mylinks_hide_notice" for="wp_mylinks_hide_notice"><b><?php esc_html_e( 'Hide Admin Notice?', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<input type="checkbox" id="wp_mylinks_hide_notice" name="wp_mylinks_hide_notice" class="my_links_checkbox" value="yes" <?php checked( get_option( 'wp_mylinks_hide_notice' ), 'yes' ); ?>>
						<?php esc_html_e( "Everything's Alright, Hide Notice Now", 'wp-mylinks' ); ?>
						<br>
						<p class="input-description">
							<?php esc_html_e( 'This will hide admin notice to flush your permalinks.', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="wp_mylinks_delete_data_on_uninstall" for="wp_mylinks_delete_data_on_uninstall"><b><?php esc_html_e( 'Delete Plugin Data on Uninstall?', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<input type="checkbox" id="wp_mylinks_delete_data_on_uninstall" name="wp_mylinks_delete_data_on_uninstall" class="my_links_checkbox" value="yes" <?php checked( get_option( 'wp_mylinks_delete_data_on_uninstall' ), 'yes' ); ?>>
						<?php esc_html_e( 'Yes, Delete All Plugin Data on Uninstall', 'wp-mylinks' ); ?>
						<br>
						<p class="input-description">
							<?php esc_html_e( 'When enabled, deleting the plugin from the Plugins screen will remove all WP MyLinks settings and oEmbed caches. MyLink posts and their content are preserved by default. Leave this unchecked to keep your settings if you ever uninstall and reinstall.', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<hr>

		<table class="form-table">
			<tbody>
				<h2 class="section_wp_mylinks"><?php esc_html_e( 'Setup Global Meta Tags', 'wp-mylinks' ); ?></h2>
				<p>
					<?php
					echo wp_kses(
						__( 'Meta tags for the MyLinks page, will be shown both on search engine result and browser tab. If you use Yoast SEO or built-in<br> <strong>Setup Meta Tags</strong> form and already set both the <code>meta title</code> and <code>description</code> on MyLinks post editor, they will be used<br> instead of below values.', 'wp-mylinks' ),
						array(
							'strong' => array(),
							'code'   => array(),
							'br'     => array(),
						)
					);
					?>
					<br />
				</p>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="mylinks_meta_title" for="mylinks_meta_title"><b><?php esc_html_e( 'Meta Title', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<input type="text" id="mylinks_meta_title" name="mylinks_meta_title" class="mylinks_input_text" value="<?php echo esc_attr( get_option( 'mylinks_meta_title' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Your MyLinks Title | Your Site Title', 'wp-mylinks' ); ?>">
						<p class="input-description">
							<?php esc_html_e( 'Set the meta title for the MyLinks page.', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="mylinks_meta_description" for="mylinks_meta_description"><b><?php esc_html_e( 'Meta Description', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<textarea id="mylinks_meta_description" name="mylinks_meta_description" class="mylinks_input_textarea" rows="5"><?php echo esc_textarea( get_option( 'mylinks_meta_description' ) ); ?></textarea>
						<p class="input-description">
							<?php esc_html_e( 'Set the meta description of the MyLinks page.', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="mylinks_upload_favicon" for="mylinks_upload_favicon"><b><?php esc_html_e( 'Custom Favicon', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<input id="mylinks_upload_favicon" class="wp-mylinks-uploader-url" type="text" name="mylinks_upload_favicon" value="<?php echo esc_attr( get_option( 'mylinks_upload_favicon' ) ); ?>" />
						<input id="upload_image_button" type="button" class="button-primary" value="<?php esc_attr_e( 'Choose Favicon', 'wp-mylinks' ); ?>" />
						<p class="input-description">
							<?php esc_html_e( 'Set a favicon for the MyLinks page.', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="wp_mylinks_noindex" for="wp_mylinks_noindex"><b>
							<?php echo wp_kses( __( 'Set to <code>noindex</code>?', 'wp-mylinks' ), array( 'code' => array() ) ); ?>
						</b></label>
					</th>
					<td>
						<input type="checkbox" id="wp_mylinks_noindex" name="wp_mylinks_noindex" class="my_links_checkbox" value="yes" <?php checked( get_option( 'wp_mylinks_noindex' ), 'yes' ); ?>>
						<?php echo wp_kses( __( 'Yes, Set to <code>noindex</code>', 'wp-mylinks' ), array( 'code' => array() ) ); ?>
						<br>
						<p class="input-description">
							<?php esc_html_e( 'This will prevent MyLinks page from being indexed on search engine.', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="wp_mylinks_nofollow" for="wp_mylinks_nofollow"><b>
							<?php echo wp_kses( __( 'Set to <code>nofollow</code>?', 'wp-mylinks' ), array( 'code' => array() ) ); ?>
						</b></label>
					</th>
					<td>
						<input type="checkbox" id="wp_mylinks_nofollow" name="wp_mylinks_nofollow" class="my_links_checkbox" value="yes" <?php checked( get_option( 'wp_mylinks_nofollow' ), 'yes' ); ?>>
						<?php echo wp_kses( __( 'Yes, Set to <code>nofollow</code>', 'wp-mylinks' ), array( 'code' => array() ) ); ?>
						<br>
						<p class="input-description">
							<?php esc_html_e( 'This will ban crawlers to follow all the links on the MyLinks page.', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<hr>

		<h2 class="section_wp_mylinks"><?php esc_html_e( 'Schema.org Structured Data', 'wp-mylinks' ); ?></h2>
		<p>
			<?php
			echo wp_kses(
				__( 'Output <code>Person</code> or <code>Organization</code> JSON-LD in the <code>&lt;head&gt;</code> of every MyLinks page so search engines can identify you. If Yoast SEO is active and emitting its own schema, this will defer to Yoast automatically. You can override the type per page on the MyLink editor.', 'wp-mylinks' ),
				array( 'code' => array() )
			);
			?>
			<br />
		</p>
		<table class="form-table">
			<tbody>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="wp_mylinks_enable_schema" for="wp_mylinks_enable_schema"><b><?php esc_html_e( 'Enable JSON-LD Schema?', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<input type="checkbox" id="wp_mylinks_enable_schema" name="wp_mylinks_enable_schema" class="my_links_checkbox" value="yes" <?php checked( get_option( 'wp_mylinks_enable_schema' ), 'yes' ); ?>>
						<?php esc_html_e( 'Yes, Output Schema.org JSON-LD on All MyLinks Pages', 'wp-mylinks' ); ?>
						<br>
						<p class="input-description">
							<?php esc_html_e( 'When enabled, every MyLinks page will include structured data describing you (name, image, description, social profiles).', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="wp_mylinks_schema_type" for="wp_mylinks_schema_type"><b><?php esc_html_e( 'Default Schema Type', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<?php $schema_type = (string) get_option( 'wp_mylinks_schema_type', 'Person' ); ?>
						<select id="wp_mylinks_schema_type" name="wp_mylinks_schema_type" class="mylinks_input_select">
							<option value="Person" <?php selected( $schema_type, 'Person' ); ?>><?php esc_html_e( 'Person (default)', 'wp-mylinks' ); ?></option>
							<option value="Organization" <?php selected( $schema_type, 'Organization' ); ?>><?php esc_html_e( 'Organization', 'wp-mylinks' ); ?></option>
						</select>
						<p class="input-description">
							<?php esc_html_e( 'Use Person for personal bio pages. Use Organization for businesses, brands, or restaurants. Each MyLink page can override this individually.', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="wp_mylinks_enable_profilepage" for="wp_mylinks_enable_profilepage"><b><?php esc_html_e( 'Wrap in ProfilePage Schema?', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<input type="checkbox" id="wp_mylinks_enable_profilepage" name="wp_mylinks_enable_profilepage" class="my_links_checkbox" value="yes" <?php checked( get_option( 'wp_mylinks_enable_profilepage' ), 'yes' ); ?>>
						<?php esc_html_e( 'Yes, Also Emit ProfilePage Schema Wrapper', 'wp-mylinks' ); ?>
						<br>
						<p class="input-description">
							<?php esc_html_e( 'When enabled, the JSON-LD output is structured as a graph containing both the Person/Organization and a ProfilePage wrapper that points to it. This is the pattern used by major social profile pages (X, GitHub, LinkedIn) and helps search engines and AI tools recognize this URL as a canonical profile page.', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<hr>

		<h2 class="section_wp_mylinks"><?php esc_html_e( 'Open Graph & Twitter Card', 'wp-mylinks' ); ?></h2>
		<p>
			<?php
			echo wp_kses(
				__( 'Output <code>og:*</code> and <code>twitter:*</code> meta tags so social-media share previews look correct. If Yoast SEO is active and handling Open Graph itself, this will defer to Yoast automatically. You can override the share image per page on the MyLink editor.', 'wp-mylinks' ),
				array( 'code' => array() )
			);
			?>
			<br />
		</p>
		<table class="form-table">
			<tbody>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="wp_mylinks_enable_og" for="wp_mylinks_enable_og"><b><?php esc_html_e( 'Enable Open Graph & Twitter Card?', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<input type="checkbox" id="wp_mylinks_enable_og" name="wp_mylinks_enable_og" class="my_links_checkbox" value="yes" <?php checked( get_option( 'wp_mylinks_enable_og' ), 'yes' ); ?>>
						<?php esc_html_e( 'Yes, Output OG & Twitter Tags on All MyLinks Pages', 'wp-mylinks' ); ?>
						<br>
						<p class="input-description">
							<?php esc_html_e( 'When enabled, share previews on Facebook, X (Twitter), LinkedIn, and Discord will show your MyLink page properly.', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="wp_mylinks_og_image" for="wp_mylinks_og_image"><b><?php esc_html_e( 'Default Share Image', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<input id="wp_mylinks_og_image" type="text" name="wp_mylinks_og_image" class="wp-mylinks-uploader-url" value="<?php echo esc_attr( get_option( 'wp_mylinks_og_image' ) ); ?>" />
						<input id="wp_mylinks_og_image_button" type="button" class="button button-secondary wp-mylinks-og-image-uploader" value="<?php esc_attr_e( 'Choose Image', 'wp-mylinks' ); ?>" />
						<p class="input-description">
							<?php esc_html_e( 'Recommended size: 1200×630 pixels. If left empty, each MyLink page will fall back to its avatar (which may appear cropped on social previews).', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wp_mylinks_options">
					<th scope="row">
						<label class="wp_mylinks_twitter_handle" for="wp_mylinks_twitter_handle"><b><?php esc_html_e( 'Twitter / X Handle', 'wp-mylinks' ); ?></b></label>
					</th>
					<td>
						<input type="text" id="wp_mylinks_twitter_handle" name="wp_mylinks_twitter_handle" class="mylinks_input_text" value="<?php echo esc_attr( get_option( 'wp_mylinks_twitter_handle' ) ); ?>" placeholder="@yourhandle">
						<p class="input-description">
							<?php esc_html_e( 'Optional. Used as twitter:site and twitter:creator. The @ is added automatically if missing.', 'wp-mylinks' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<hr>
		<?php submit_button(); ?>
	</form>
	<?php
}

/**
 * Render the Tutorial & Support tab.
 */
function wp_mylinks_render_support_tab() {
	// Author / project URLs (tracked).
	$walterpinem_me_url   = wpmylinks_url( 'https://walterpinem.me/' );
	$walterpinem_com_url  = wpmylinks_url( 'https://walterpinem.com/' );
	$onlinestorekit_url   = wpmylinks_url( 'https://www.onlinestorekit.com/' );
	$free_tools_url       = wpmylinks_url( 'https://walterpinem.me/projects/tools/' );
	$contact_url          = wpmylinks_url( 'https://walterpinem.me/projects/contact/' );

	// External URLs we don't track (third-party platforms).
	$video_tutorial_url = 'https://www.youtube.com/watch?v=WK03GS5rM0Q&list=PLwazGJFvaLnCZrBRuDeDsbkpjjOPKz4pC';
	$review_url         = 'https://wordpress.org/support/plugin/wp-mylinks/reviews/?rate=5#new-post';
	?>
	<!-- Tutorial & Support tab -->
	<div class="wrap">
		<div class="feature-section one-col wrap about-wrap">
			<div class="about-text">
				<h4>
					<?php
					echo wp_kses(
						__( '<strong>WP MyLinks</strong> is Waiting for Your Feedback', 'wp-mylinks' ),
						array( 'strong' => array() )
					);
					?>
				</h4>
			</div>
			<div class="indo-about-description">
				<?php
				echo wp_kses(
					__( "<strong>WP MyLinks</strong> is my fourth plugin and it's open source. I acknowledge that there are still a lot to fix, here and there, that's why I really need your feedback. <br>Send a feedback through some of below options to contact me:", 'wp-mylinks' ),
					array(
						'strong' => array(),
						'br'     => array(),
					)
				);
				?>
			</div>

			<table class="tg" style="table-layout: fixed; width: 100%;">
				<colgroup>
					<col style="width: 80px">
					<col style="width: 500px">
				</colgroup>
				<tr>
					<th class="tg-kiyi"><?php esc_html_e( 'Author:', 'wp-mylinks' ); ?></th>
					<th class="tg-fymr"><?php esc_html_e( 'Walter Pinem', 'wp-mylinks' ); ?></th>
				</tr>
				<tr>
					<td class="tg-kiyi"><?php esc_html_e( 'Website:', 'wp-mylinks' ); ?></td>
					<td class="tg-fymr">
						<a href="<?php echo esc_url( $walterpinem_me_url ); ?>" title="<?php esc_attr_e( 'Visit walterpinem.me', 'wp-mylinks' ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'walterpinem.me', 'wp-mylinks' ); ?>
						</a>
					</td>
				</tr>
				<tr>
					<td class="tg-kiyi"></td>
					<td class="tg-fymr">
						<a href="<?php echo esc_url( $walterpinem_com_url ); ?>" title="<?php esc_attr_e( 'Visit walterpinem.com', 'wp-mylinks' ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'walterpinem.com', 'wp-mylinks' ); ?>
						</a>
					</td>
				</tr>
				<tr>
					<td class="tg-kiyi"></td>
					<td class="tg-fymr">
						<a href="<?php echo esc_url( $onlinestorekit_url ); ?>" title="<?php esc_attr_e( 'Online Store Kit', 'wp-mylinks' ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Online Store Kit', 'wp-mylinks' ); ?>
						</a>
					</td>
				</tr>
				<tr>
					<td class="tg-kiyi"></td>
					<td class="tg-fymr">
						<a href="<?php echo esc_url( $free_tools_url ); ?>" title="<?php esc_attr_e( '100+ Free Online Tools', 'wp-mylinks' ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( '100+ Free Online Tools', 'wp-mylinks' ); ?>
						</a>
					</td>
				</tr>
				<tr>
					<td class="tg-kiyi"><?php esc_html_e( 'More:', 'wp-mylinks' ); ?></td>
					<td class="tg-fymr">
						<a href="<?php echo esc_url( $video_tutorial_url ); ?>" title="<?php esc_attr_e( 'Complete YouTube Tutorial', 'wp-mylinks' ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Video Tutorial', 'wp-mylinks' ); ?>
						</a>
					</td>
				</tr>
				<tr>
					<td class="tg-kiyi" rowspan="2"></td>
					<td class="tg-fymr">
						<a href="<?php echo esc_url( $contact_url ); ?>" title="<?php esc_attr_e( 'Support & Feature Request', 'wp-mylinks' ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Support & Feature Request', 'wp-mylinks' ); ?>
						</a>
					</td>
				</tr>
				<tr>
					<td class="tg-fymr">
						<a href="<?php echo esc_url( $review_url ); ?>" title="<?php esc_attr_e( 'Leave a Review', 'wp-mylinks' ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Leave a Review', 'wp-mylinks' ); ?>
						</a>
					</td>
				</tr>
			</table>

			<br>
			<hr>

			<?php echo do_shortcode( '[donate]' ); ?>

			<?php wp_mylinks_render_credit_line(); ?>
		</div>
	</div>
	<?php
}

/**
 * Render the Welcome tab.
 */
function wp_mylinks_render_welcome_tab() {
	$images_url = plugin_dir_url( dirname( __FILE__ ) ) . 'images/';

	// All these go through wpmylinks_url() because they point at the author's
	// own properties and benefit from the tracking parameters.
	$inquiry_url = wpmylinks_url( 'https://walterpinem.me/projects/customization-service/' );
	?>
	<!-- Begin creating plugin admin page -->
	<div class="wrap">
		<div class="feature-section one-col wrap about-wrap">
			<div class="mylinks-title">
				<h2>
					<?php
					echo wp_kses(
						__( 'Thank You For Using<br> WP MyLinks', 'wp-mylinks' ),
						array( 'br' => array() )
					);
					?>
				</h2>
				<img src="<?php echo esc_url( $images_url . 'wp-mylinks.png' ); ?>" alt="<?php esc_attr_e( 'WP MyLinks', 'wp-mylinks' ); ?>" />
			</div>

			<div class="feature-section one-col about-text">
				<h3><?php esc_html_e( 'Build Fully Customizable Micro Landing Pages For Your Brand!', 'wp-mylinks' ); ?></h3>
			</div>
			<div class="feature-section one-col indo-about-description">
				<?php
				echo wp_kses(
					__( "<strong>WP MyLinks</strong> can help you create a micro landing page that contains all the links you want to share to your audience with the tool you're currently using and the domain name that reflects your own brand. Share one single link for everything!", 'wp-mylinks' ),
					array( 'strong' => array() )
				);
				?>
			</div>
			<div class="clear"></div>
			<hr />

			<div class="feature-section one-col">
				<h3 style="text-align: center;"><?php esc_html_e( 'Watch the Complete Overview and Tutorial', 'wp-mylinks' ); ?></h3>
				<div class="headline-feature feature-video">
					<div class="embed-container">
						<iframe src="https://www.youtube.com/embed/?listType=playlist&list=PLwazGJFvaLnCZrBRuDeDsbkpjjOPKz4pC" frameborder="0" allowfullscreen title="<?php esc_attr_e( 'WP MyLinks Tutorial', 'wp-mylinks' ); ?>"></iframe>
					</div>
				</div>
			</div>
			<div class="clear"></div>
			<hr />

			<div class="feature-section one-col">
				<div class="indo-get-started">
					<h3><?php esc_html_e( "Let's Get Started", 'wp-mylinks' ); ?></h3>
					<ul>
						<li><strong><?php esc_html_e( 'Step #1:', 'wp-mylinks' ); ?></strong>
							<?php
							echo wp_kses(
								__( 'Build your very first micro landing page on <a href="post-new.php?post_type=mylink" target="_blank"><strong>New MyLink</strong></a> page.', 'wp-mylinks' ),
								array(
									'a'      => array(
										'href'   => array(),
										'target' => array(),
									),
									'strong' => array(),
								)
							);
							?>
						</li>
						<li><strong><?php esc_html_e( 'Step #2:', 'wp-mylinks' ); ?></strong> <?php esc_html_e( 'Setup your profile including avatar, description, and social media links.', 'wp-mylinks' ); ?></li>
						<li><strong><?php esc_html_e( 'Step #3:', 'wp-mylinks' ); ?></strong> <?php esc_html_e( 'Add unlimited number of links you want to share to your audience.', 'wp-mylinks' ); ?></li>
						<li><strong><?php esc_html_e( 'Step #4:', 'wp-mylinks' ); ?></strong> <?php esc_html_e( 'Choose a theme that matches your personal or business brand.', 'wp-mylinks' ); ?></li>
						<li><strong><?php esc_html_e( 'Step #5:', 'wp-mylinks' ); ?></strong>
							<?php
							echo wp_kses(
								__( 'Setup global settings for your micro landing pages on <a href="edit.php?post_type=mylink&page=welcome&tab=global" target="_blank"><strong>Global Configurations</strong></a> setting panel.', 'wp-mylinks' ),
								array(
									'a'      => array(
										'href'   => array(),
										'target' => array(),
									),
									'strong' => array(),
								)
							);
							?>
						</li>
						<li><strong><?php esc_html_e( 'Step #6:', 'wp-mylinks' ); ?></strong>
							<?php
							echo wp_kses(
								__( 'Add custom styles and scripts to your micro landing pages on <a href="edit.php?post_type=mylink&page=welcome&tab=script" target="_blank"><strong>Custom Script</strong></a> setting panel.', 'wp-mylinks' ),
								array(
									'a'      => array(
										'href'   => array(),
										'target' => array(),
									),
									'strong' => array(),
								)
							);
							?>
						</li>
						<li><strong><?php esc_html_e( 'Step #7:', 'wp-mylinks' ); ?></strong>
							<?php
							echo wp_kses(
								__( '<strong>Have an inquiry?</strong> Find out how to reach out to me on <a href="edit.php?post_type=mylink&page=welcome&tab=tutorial_support" target="_blank"><strong>Support</strong></a> panel.', 'wp-mylinks' ),
								array(
									'a'      => array(
										'href'   => array(),
										'target' => array(),
									),
									'strong' => array(),
								)
							);
							?>
						</li>
					</ul>
					<hr />
					<p><?php esc_html_e( 'If you encounter 404 page not found issue, please follow the steps below:', 'wp-mylinks' ); ?></p>
					<ol>
						<li>
							<?php
							echo wp_kses(
								__( 'Go to <b>Settings</b> => <a href="options-permalink.php"><b>Permalinks</b></a> page.', 'wp-mylinks' ),
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
								__( 'Click the <b>Save Changes</b> button without having to change anything.', 'wp-mylinks' ),
								array( 'b' => array() )
							);
							?>
						</li>
						<li><?php esc_html_e( 'Recheck your MyLink page. The issue will most likely disappear.', 'wp-mylinks' ); ?></li>
					</ol>
					<p><?php esc_html_e( 'If the problem persists, you might also want to make sure that you are using pretty permalinks (Post name), but be careful! Changing your current permalinks structure to another will affect your entire URLs, and will be very bad for SEO!', 'wp-mylinks' ); ?></p>
				</div>
			</div>
			<hr>

			<div class="feature-section two-col">
				<div class="col">
					<img src="<?php echo esc_url( $images_url . 'unlimited.png' ); ?>" alt="" />
					<h3><?php esc_html_e( 'Unlimited Landing Pages', 'wp-mylinks' ); ?></h3>
					<p><?php esc_html_e( 'Build unlimited number of micro landing pages that host unlimited number of links.', 'wp-mylinks' ); ?></p>
				</div>
				<div class="col">
					<img src="<?php echo esc_url( $images_url . 'one-link.png' ); ?>" alt="" />
					<h3><?php esc_html_e( 'One Link for Everything', 'wp-mylinks' ); ?></h3>
					<p><?php esc_html_e( 'Every created micro landing page will have one link that you can share anywhere on your networks.', 'wp-mylinks' ); ?></p>
				</div>
			</div>

			<div class="feature-section two-col">
				<div class="col">
					<img src="<?php echo esc_url( $images_url . 'own-brand.png' ); ?>" alt="" />
					<h3><?php esc_html_e( 'Use Your Own Brand', 'wp-mylinks' ); ?></h3>
					<p><?php esc_html_e( 'You already have your own brand through a domain name. Use it on your micro landing page and get boosted!', 'wp-mylinks' ); ?></p>
				</div>
				<div class="col">
					<img src="<?php echo esc_url( $images_url . 'custom-themes.png' ); ?>" alt="" />
					<h3><?php esc_html_e( '15+ Themes to Choose From', 'wp-mylinks' ); ?></h3>
					<p><?php esc_html_e( 'Choose a theme that can represent your brand or taste. Or you can also add custom CSS to use your own.', 'wp-mylinks' ); ?></p>
				</div>
			</div>

			<div class="feature-section two-col">
				<div class="col">
					<img src="<?php echo esc_url( $images_url . 'custom-scripts.png' ); ?>" alt="" />
					<h3><?php esc_html_e( 'Custom Scripts & Styles', 'wp-mylinks' ); ?></h3>
					<p><?php esc_html_e( 'Track how every landing page performs easily with Google Analytics, Facebook Pixel etc or customize the look. You have the options.', 'wp-mylinks' ); ?></p>
				</div>
				<div class="col">
					<img src="<?php echo esc_url( $images_url . 'documentation.png' ); ?>" alt="" />
					<h3><?php esc_html_e( 'Comprehensive Documentation', 'wp-mylinks' ); ?></h3>
					<p><?php esc_html_e( 'You will not be left alone. My complete documentation or tutorial will always help and support all your needs to get started.', 'wp-mylinks' ); ?></p>
				</div>
			</div>

			<br>
			<hr>

			<?php echo do_shortcode( '[donate]' ); ?>

			<?php wp_mylinks_render_credit_line(); ?>
		</div>
	</div>
	<br>
	<?php
}
