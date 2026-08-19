<?php
/**
 * Metabox definitions for the mylink and mylinks-collection post types.
 *
 * Ported verbatim from the CMB2 registrations in 1.0.8 to the native fields
 * framework in 1.1.0 — every field id, meta key, and sanitization behavior is
 * unchanged, so existing content round-trips byte-identically (verified by
 * _dev/tests/test-fields-storage-contract.php).
 *
 * @link       https://walterpinem.me/
 * @since      1.1.0
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Register all metaboxes with the native fields framework.
 *
 * Hooked to init (the old code used cmb2_admin_init) so labels are
 * translated after the textdomain loads. Extensions can add or amend boxes
 * via the `wp_mylinks_meta_boxes` filter.
 *
 * @since 1.1.0
 * @return void
 */
function wp_mylinks_register_metaboxes() {

	// -------------------------------------------------------------------
	// 1. MyLinks Profile.
	// -------------------------------------------------------------------
	wp_mylinks_register_metabox(
		array(
			'id'           => mylinks_prefix('form'),
			'title'        => __('MyLinks Profile', 'wp-mylinks'),
			'object_types' => array( 'mylink' ),
			'context'      => 'normal',
			'priority'     => 'high',
			'fields'       => array(
				array(
					'name'         => __('Avatar', 'wp-mylinks'),
					'desc'         => __('Upload an avatar, preferably square in size.', 'wp-mylinks'),
					'id'           => mylinks_prefix('avatar'),
					'type'         => 'file',
					'before_row'   => __('Build a profile section for your MyLinks page.', 'wp-mylinks'),
					'options'      => array(
						'url' => false,
					),
					'text'         => array(
						'add_upload_file_text' => __('Choose Avatar', 'wp-mylinks'),
					),
					'query_args'   => array(
						'type' => 'image',
					),
					'preview_size' => 'thumbnail',
				),
				array(
					'name'    => __('Avatar Style', 'wp-mylinks'),
					'id'      => mylinks_prefix('avatar-style'),
					'desc'    => __('Choose the avatar style.', 'wp-mylinks'),
					'type'    => 'radio_inline',
					'options' => array(
						'story-like'  => __('Story-Like Border', 'wp-mylinks'),
						'shadow'      => __('Shadowy', 'wp-mylinks'),
						'plain'       => __('Plain', 'wp-mylinks'),
						'transparent' => __('Transparent', 'wp-mylinks'),
					),
					'default' => 'story-like',
				),
				array(
					'name'       => __('Theme', 'wp-mylinks'),
					'desc'       => __('Choose a theme that matches your brand, or select <code>None</code> to use the global theme from <a href="edit.php?post_type=mylink&page=welcome&tab=global" target="_blank"><strong>Global Configurations</strong></a>.', 'wp-mylinks'),
					'id'         => mylinks_prefix('theme'),
					'type'       => 'select',
					'default'    => 'default',
					'attributes' => array(
						'class' => 'mylinks-input-select',
					),
					'options_cb' => 'wp_mylinks_theme_callback',
				),
				array(
					'name'       => __('Name', 'wp-mylinks'),
					'desc'       => __('Add your name or username that will be shown under your avatar.', 'wp-mylinks'),
					'id'         => mylinks_prefix('name'),
					'type'       => 'text',
					'classes'    => 'wml-field--wide',
					'attributes' => array(
						'class' => 'mylinks-input-text',
					),
				),
				array(
					'name'    => __('Description', 'wp-mylinks'),
					'desc'    => __('Add your profile description that will be shown under your name. Compatible with HTML.', 'wp-mylinks'),
					'id'      => mylinks_prefix('description'),
					'type'    => 'wysiwyg',
					'options' => array(
						'wpautop'       => true,
						'media_buttons' => false,
						'textarea_rows' => get_option('default_post_edit_rows', 5),
						'teeny'         => true,
					),
				),
				array(
					'name'       => __('Page Background', 'wp-mylinks'),
					'desc'       => __('Overrides the theme\'s page background.', 'wp-mylinks'),
					'id'         => mylinks_prefix('accent-bg'),
					'type'       => 'colorpicker',
					'before_row' => __('<strong>Accent Colors</strong> — optional overrides applied on top of the selected theme, for this page only. Leave a color empty to keep the theme\'s own. Site-wide defaults live on <a href="edit.php?post_type=mylink&page=welcome&tab=global" target="_blank"><strong>Settings → General</strong></a>.', 'wp-mylinks'),
				),
				array(
					'name' => __('Button Background', 'wp-mylinks'),
					'desc' => __('Overrides the link buttons\' background.', 'wp-mylinks'),
					'id'   => mylinks_prefix('accent-button-bg'),
					'type' => 'colorpicker',
				),
				array(
					'name' => __('Button Text', 'wp-mylinks'),
					'desc' => __('Overrides the link buttons\' text color.', 'wp-mylinks'),
					'id'   => mylinks_prefix('accent-button-text'),
					'type' => 'colorpicker',
				),
				array(
					'name' => __('Text Color', 'wp-mylinks'),
					'desc' => __('Overrides the name and description color.', 'wp-mylinks'),
					'id'   => mylinks_prefix('accent-text'),
					'type' => 'colorpicker',
				),
				array(
					'name'            => __('Font Family', 'wp-mylinks'),
					'id'              => mylinks_prefix('font-family'),
					'type'            => 'text',
					'classes'         => 'wml-field--wide',
					'sanitization_cb' => 'wp_mylinks_sanitize_font_family',
					'before_row'      => __('<strong>Display Options</strong> — font and search settings for this page.', 'wp-mylinks'),
					'desc'            => __('Optional. A font already available on your site, e.g. <code>Poppins, sans-serif</code>. WP MyLinks does not load a web font for you (to keep your visitors\' data private) — add one through your theme or the <strong>Custom CSS</strong> field, then name it here. Leave empty to use the theme font.', 'wp-mylinks'),
					'attributes'      => array(
						'placeholder' => 'Poppins, sans-serif',
						'class'       => 'mylinks-input-text',
					),
				),
				array(
					'name'    => __('Search / Filter Bar', 'wp-mylinks'),
					'id'      => mylinks_prefix('enable-search'),
					'type'    => 'radio_inline',
					'desc'    => __('Show a search box above the links so visitors can filter a long list by typing. Recommended for pages with many links.', 'wp-mylinks'),
					'options' => array(
						'yes' => __('Show', 'wp-mylinks'),
						'no'  => __('Hide', 'wp-mylinks'),
					),
					'default' => 'no',
				),
			),
		)
	);

	// -------------------------------------------------------------------
	// 2. Social Media (hardcoded 8 platforms).
	// -------------------------------------------------------------------
	$social_fields = array(
		array(
			'name'    => __('Position', 'wp-mylinks'),
			'id'      => mylinks_prefix('social-media-position'),
			'desc'    => __('Choose the Social Media profile icons position.', 'wp-mylinks'),
			'type'    => 'radio_inline',
			'options' => array(
				'top'    => __('Top', 'wp-mylinks'),
				'bottom' => __('Bottom', 'wp-mylinks'),
			),
			'default' => 'top',
		),
	);

	$social_platforms = array(
		'facebook'  => __('Facebook', 'wp-mylinks'),
		'twitter'   => __('Twitter', 'wp-mylinks'),
		'linkedin'  => __('Linkedin', 'wp-mylinks'),
		'instagram' => __('Instagram', 'wp-mylinks'),
		'youtube'   => __('Youtube', 'wp-mylinks'),
		'pinterest' => __('Pinterest', 'wp-mylinks'),
		'tiktok'    => __('TikTok', 'wp-mylinks'),
		'discord'   => __('Discord', 'wp-mylinks'),
	);

	$first = true;
	foreach ( $social_platforms as $platform_key => $platform_label ) {
		$url_field = array(
			/* translators: %s: social platform name */
			'name'       => sprintf( __('%s URL', 'wp-mylinks'), $platform_label ),
			/* translators: %s: social platform name */
			'desc'       => sprintf( __('Add your %s profile.', 'wp-mylinks'), $platform_label ),
			'id'         => mylinks_prefix( $platform_key . '-url' ),
			'type'       => 'text_url',
			'attributes' => array(
				'class' => 'mylinks-input-url',
			),
		);
		if ( $first ) {
			$url_field['before_row'] = __('Setup social links that will appear under your profile section.', 'wp-mylinks');
			$first                   = false;
		}
		$social_fields[] = $url_field;

		$social_fields[] = array(
			/* translators: %s: social platform name */
			'name'         => sprintf( __('%s Icon', 'wp-mylinks'), $platform_label ),
			/* translators: %s: social platform name */
			'desc'         => sprintf( __('Optional. Upload your own square icon to replace the default %s icon.', 'wp-mylinks'), $platform_label ),
			'id'           => mylinks_prefix( $platform_key . '-icon' ),
			'type'         => 'file',
			'options'      => array(
				'url' => true,
			),
			'text'         => array(
				'add_upload_file_text' => __('Choose Icon', 'wp-mylinks'),
			),
			'query_args'   => array(
				'type' => 'image',
			),
			'preview_size' => 'thumbnail',
		);
	}

	wp_mylinks_register_metabox(
		array(
			'id'           => mylinks_prefix('form-social'),
			'title'        => __('Social Media', 'wp-mylinks'),
			'object_types' => array( 'mylink' ),
			'context'      => 'normal',
			'priority'     => 'high',
			'fields'       => $social_fields,
		)
	);

	// -------------------------------------------------------------------
	// 3. Additional Social Platforms (repeater, 1.0.8+).
	// -------------------------------------------------------------------
	wp_mylinks_register_metabox(
		array(
			'id'           => mylinks_prefix('form-social-additional'),
			'title'        => __('Additional Social Platforms', 'wp-mylinks'),
			'object_types' => array( 'mylink' ),
			'context'      => 'normal',
			'priority'     => 'high',
			'fields'       => array(
				array(
					'id'      => mylinks_prefix('additional-socials'),
					'type'    => 'group',
					'desc'    => __('Add any social platform that is not in the list above (e.g. Threads, Bluesky, GitHub, Mastodon, WhatsApp, Telegram, Signal). Each row will appear in the same icon row as the platforms above.', 'wp-mylinks'),
					'options' => array(
						'group_title'    => __('Platform {#}', 'wp-mylinks'),
						'add_button'     => __('Add Another Platform', 'wp-mylinks'),
						'remove_button'  => __('Remove Platform', 'wp-mylinks'),
						'sortable'       => true,
						'remove_confirm' => __('Remove this social platform?', 'wp-mylinks'),
					),
					'fields'  => array(
						array(
							'name' => __('Platform Name', 'wp-mylinks'),
							'desc' => __('Used as the alt text and accessibility label (e.g. "Threads", "Bluesky", "GitHub").', 'wp-mylinks'),
							'id'   => 'name',
							'type' => 'text',
						),
						array(
							'name'      => __('Profile URL', 'wp-mylinks'),
							'desc'      => __('Include the http:// or https:// protocol.', 'wp-mylinks'),
							'id'        => 'url',
							'type'      => 'text_url',
							'protocols' => array( 'http', 'https', 'mailto', 'tel' ),
						),
						array(
							'name'         => __('Icon', 'wp-mylinks'),
							'desc'         => __('Optional. Upload a square icon (PNG or SVG, ~64×64). If empty, the icon class below is used, otherwise a generic globe icon.', 'wp-mylinks'),
							'id'           => 'icon',
							'type'         => 'file',
							'options'      => array(
								'url' => true,
							),
							'text'         => array(
								'add_upload_file_text' => __('Choose Icon', 'wp-mylinks'),
							),
							'query_args'   => array(
								'type' => 'image',
							),
							'preview_size' => 'thumbnail',
						),
						array(
							'name'            => __('Icon Class', 'wp-mylinks'),
							'desc'            => __('Optional alternative to uploading an image. Enter an icon class from an icon font your site already loads, e.g. <code>fa-brands fa-github</code> or <code>bi bi-github</code>. The icon appears only if that icon font is loaded by your theme or another plugin — WP MyLinks does not load one for you. An uploaded image above takes priority.', 'wp-mylinks'),
							'id'              => 'icon-class',
							'type'            => 'text',
							'sanitization_cb' => 'wp_mylinks_sanitize_icon_classes',
							'attributes'      => array(
								'placeholder' => 'fa-brands fa-github',
							),
						),
					),
				),
			),
		)
	);

	// -------------------------------------------------------------------
	// 4. Links (the main repeater).
	// -------------------------------------------------------------------
	$collection_url = esc_url( admin_url('edit.php?post_type=mylinks-collection') );

	wp_mylinks_register_metabox(
		array(
			'id'           => mylinks_prefix('form-links'),
			'title'        => __('Links', 'wp-mylinks'),
			'object_types' => array( 'mylink' ),
			'context'      => 'normal',
			'priority'     => 'high',
			'fields'       => array(
				array(
					'id'      => mylinks_prefix('links'),
					'type'    => 'group',
					'desc'    => __('Add links, or use a row as a section heading or an HTML block. Rows are sortable — drag to reorder. Add unlimited rows with the "Add Another Link" button.', 'wp-mylinks'),
					'options' => array(
						'group_title'    => __('Row {#}', 'wp-mylinks'),
						'add_button'     => __('Add Another Link', 'wp-mylinks'),
						'remove_button'  => __('Remove Row', 'wp-mylinks'),
						'sortable'       => true,
						'remove_confirm' => __('Really? Remove This Row?', 'wp-mylinks'),
					),
					'fields'  => array(
						array(
							'name'    => __('Row Type', 'wp-mylinks'),
							'id'      => 'row-type',
							'type'    => 'radio_inline',
							'desc'    => __('A normal <strong>Link</strong>, a <strong>Heading</strong> that groups the links below it (uses the Title field), or an <strong>HTML Block</strong> for a form, table, or embed.', 'wp-mylinks'),
							'options' => array(
								'link'    => __('Link', 'wp-mylinks'),
								'heading' => __('Heading', 'wp-mylinks'),
								'html'    => __('HTML Block', 'wp-mylinks'),
							),
							'default' => 'link',
						),
						array(
							'name' => __('Title', 'wp-mylinks'),
							'desc' => __('The link label, or the heading text when Row Type is Heading.', 'wp-mylinks'),
							'id'   => 'title',
							'type' => 'text',
						),
						array(
							'name'            => __('HTML Block', 'wp-mylinks'),
							'id'              => 'html-block',
							'type'            => 'textarea_small',
							'classes'         => 'wml-field--wide',
							'sanitization_cb' => 'wp_mylinks_sanitize_raw_code',
							'desc'            => __('Used only when Row Type is <strong>HTML Block</strong>. Paste a newsletter form, a table, or an embed. Raw scripts and iframes are kept only for users allowed to post unfiltered HTML; other users get a safe subset.', 'wp-mylinks'),
							'attributes'      => array(
								'rows' => 4,
							),
						),
						array(
							'name'       => __('Link URL', 'wp-mylinks'),
							'id'         => 'select_url',
							'type'       => 'pw_select',
							'desc'       => sprintf(
								/* translators: %s: MyLinks Collection page URL */
								__('Select a link from the list you have previously prepared on <a href="%s" target="_blank"><strong>Collection</strong></a> page, existing posts or pages.', 'wp-mylinks'),
								$collection_url
							),
							'options_cb' => 'wp_mylinks_get_post_options',
							// Post types come from the Tools tab "Link URL Sources"
							// setting (Collections always included); see
							// wp_mylinks_get_link_post_types(). data-wml-ajax turns
							// the Select2 into a server-side search so every chosen
							// post type is reachable, not just a preloaded page.
							'attributes' => array(
								'placeholder'   => __('Select a link...', 'wp-mylinks'),
								'data-wml-ajax' => 'link-search',
							),
						),
						array(
							'name'       => __('Selected URL', 'wp-mylinks'),
							'id'         => 'url',
							'type'       => 'text_url',
							'protocols'  => array( 'http', 'https', 'mailto' ),
							'desc'       => __('If you want to manually add a link or edit it, just do it here.', 'wp-mylinks'),
							'attributes' => array(
								'class' => 'mylinks-selected-url',
							),
						),
						array(
							'name'         => __('Image', 'wp-mylinks'),
							'id'           => 'image',
							'type'         => 'file',
							'preview_size' => 'thumbnail',
							'desc'         => __('Add featured image for your link, preferably square in size or use a landscape-sized image if you choose <b>Yes</b> for the <b>Use Card Layout for this Link?</b> option below.', 'wp-mylinks'),
							'options'      => array(
								'add_upload_file_text' => __('Featured Image', 'wp-mylinks'),
							),
						),
						array(
							'name'    => __('Use Card Layout for this Link?', 'wp-mylinks'),
							'id'      => 'card-layout',
							'desc'    => __('If <b>Yes</b> is chosen, the featured image you chose on the <b>Image</b> section above will be used as the background image of this link. <br/>Please make sure to fill all the <b>Title</b>, <b>URL</b>, <b>Image</b> fields above and <b>empty</b> the Youtube Video field below.', 'wp-mylinks'),
							'type'    => 'radio',
							'options' => array(
								'yes' => __('Yes', 'wp-mylinks'),
								'no'  => __('No', 'wp-mylinks'),
							),
							'default' => 'no',
						),
						array(
							'name' => __('Youtube Video', 'wp-mylinks'),
							'id'   => 'youtube-video',
							'type' => 'oembed',
							'desc' => __('Enter the full Youtube video URL. <b>Please do not fill</b> the other fields, make sure to only fill the Youtube URL field.', 'wp-mylinks'),
						),
						array(
							'name' => __('Media Embed', 'wp-mylinks'),
							'id'   => 'media-embed',
							'type' => 'oembed',
							'desc' => __('You can embed TikTok videos, Tweets, Spotify playlist, or any other media from the list of <a href="https://wordpress.org/documentation/article/embeds/#list-of-sites-you-can-embed-from" target="_blank" rel="noopener noreferrer">WordPress\'s supported sites</a>.', 'wp-mylinks'),
						),
					),
				),
			),
		)
	);

	// -------------------------------------------------------------------
	// 5. Meta Tags, Favicon, Schema, Share Image.
	// -------------------------------------------------------------------
	$global_settings_url = esc_url( admin_url('edit.php?post_type=mylink&page=welcome&tab=global') );

	$schema_before_row = sprintf(
		/* translators: 1: bolded "Enable JSON-LD Schema?" label, 2: bolded link to the global settings page */
		__('JSON-LD output is only emitted if the global %1$s option is on (%2$s). When Yoast SEO is active and emitting its own schema, WP MyLinks defers to Yoast automatically.', 'wp-mylinks'),
		'<strong>"' . esc_html__('Enable JSON-LD Schema?', 'wp-mylinks') . '"</strong>',
		'<a href="' . $global_settings_url . '" target="_blank" rel="noopener"><strong>' . esc_html__('Settings → MyLinks → Global', 'wp-mylinks') . '</strong></a>'
	);

	$og_before_row = sprintf(
		/* translators: 1: bolded "Enable Open Graph & Twitter Card?" label, 2: bolded link to the global settings page */
		__('Open Graph and Twitter Card output is only emitted if the global %1$s option is on (%2$s). When Yoast SEO is active and handling Open Graph, WP MyLinks defers to Yoast automatically.', 'wp-mylinks'),
		'<strong>"' . esc_html__('Enable Open Graph & Twitter Card?', 'wp-mylinks') . '"</strong>',
		'<a href="' . $global_settings_url . '" target="_blank" rel="noopener"><strong>' . esc_html__('Settings → MyLinks → Global', 'wp-mylinks') . '</strong></a>'
	);

	wp_mylinks_register_metabox(
		array(
			'id'           => mylinks_prefix('form-meta-tags'),
			'title'        => __('Setup Meta Tags & Favicon', 'wp-mylinks'),
			'object_types' => array( 'mylink' ),
			'context'      => 'normal',
			'priority'     => 'high',
			'fields'       => array(
				array(
					'name'       => __('Meta Title', 'wp-mylinks'),
					'desc'       => __('Set the meta title for this page.', 'wp-mylinks'),
					'id'         => mylinks_prefix('meta-title'),
					'type'       => 'text',
					'classes'    => 'wml-field--wide',
					'before_row' => __('If you use Yoast SEO, you can set the meta tags on Yoast SEO\'s meta box. Or you can also set global meta tags on<br> <a href="edit.php?post_type=mylink&page=welcome&tab=global" target="_blank"><strong>Global Configurations</strong></a>. You can also set custom favicon independently for this MyLinks page.', 'wp-mylinks'),
					'attributes' => array(
						'class' => 'mylinks-input-text',
					),
				),
				array(
					'name' => __('Meta Description', 'wp-mylinks'),
					'desc' => __('Set the meta description for this page.', 'wp-mylinks'),
					'id'   => mylinks_prefix('meta-description'),
					'type' => 'textarea_small',
				),
				array(
					'name'    => __('Set to Noindex?', 'wp-mylinks'),
					'desc'    => __('Override the noindex behavior for this page. Leave on "Inherit" to use the global setting (Settings → MyLinks → Global).', 'wp-mylinks'),
					'id'      => mylinks_prefix('noindex'),
					'type'    => 'radio_inline',
					'options' => array(
						''    => __('Inherit (use global)', 'wp-mylinks'),
						'yes' => __('Yes', 'wp-mylinks'),
						'no'  => __('No', 'wp-mylinks'),
					),
					'default' => '',
				),
				array(
					'name'    => __('Set to Nofollow?', 'wp-mylinks'),
					'desc'    => __('Override the nofollow behavior for this page. Leave on "Inherit" to use the global setting (Settings → MyLinks → Global).', 'wp-mylinks'),
					'id'      => mylinks_prefix('nofollow'),
					'type'    => 'radio_inline',
					'options' => array(
						''    => __('Inherit (use global)', 'wp-mylinks'),
						'yes' => __('Yes', 'wp-mylinks'),
						'no'  => __('No', 'wp-mylinks'),
					),
					'default' => '',
				),
				array(
					'name'         => __('Favicon', 'wp-mylinks'),
					'desc'         => __('Set custom favicon for this MyLinks page.', 'wp-mylinks'),
					'id'           => mylinks_prefix('single-favicon'),
					'type'         => 'file',
					'options'      => array(
						'url' => true,
					),
					'text'         => array(
						'add_upload_file_text' => __('Choose Favicon', 'wp-mylinks'),
					),
					'query_args'   => array(
						'type' => 'image',
					),
					'preview_size' => 'thumbnail',
				),
				array(
					'name'       => __('Schema Type', 'wp-mylinks'),
					'desc'       => __('Override the Schema.org JSON-LD type for this page. Leave on "Inherit" to use the global setting.', 'wp-mylinks'),
					'id'         => mylinks_prefix('schema-type'),
					'before_row' => $schema_before_row,
					'type'       => 'radio_inline',
					'options'    => array(
						''             => __('Inherit (use global)', 'wp-mylinks'),
						'Person'       => __('Person', 'wp-mylinks'),
						'Organization' => __('Organization', 'wp-mylinks'),
					),
					'default'    => '',
				),
				array(
					'name'         => __('Share Image (Open Graph)', 'wp-mylinks'),
					'desc'         => __('Recommended size: 1200×630 pixels. Used for Facebook, X (Twitter), LinkedIn, and Discord previews. If left empty, this page falls back to the global Default Share Image, then to the avatar.', 'wp-mylinks'),
					'id'           => mylinks_prefix('og-image'),
					'before_row'   => $og_before_row,
					'classes'      => 'wml-field--full',
					'type'         => 'file',
					'options'      => array(
						'url' => true,
					),
					'text'         => array(
						'add_upload_file_text' => __('Choose Share Image', 'wp-mylinks'),
					),
					'query_args'   => array(
						'type' => 'image',
					),
					'preview_size' => 'medium',
				),
			),
		)
	);

	// -------------------------------------------------------------------
	// 6. Custom Scripts & Styles.
	// -------------------------------------------------------------------
	wp_mylinks_register_metabox(
		array(
			'id'           => mylinks_prefix('form-custom-script-styles'),
			'title'        => __('Custom Scripts & Styles', 'wp-mylinks'),
			'object_types' => array( 'mylink' ),
			'context'      => 'normal',
			'priority'     => 'high',
			'fields'       => array(
				array(
					'name'            => __('Header Script', 'wp-mylinks'),
					'desc'            => __('Anything you put here will be included inside <code>&lt;head&gt;</code>. Please include <code>&lt;script&gt;</code> etc.', 'wp-mylinks'),
					'id'              => mylinks_prefix('mylinks-single-custom-header-script'),
					'type'            => 'textarea_small',
					'before_row'      => __('Add custom scripts and styles independently for this MyLinks page. Or you can leave these fields blank and set it on<br> <a href="edit.php?post_type=mylink&page=welcome&tab=script" target="_blank"><strong>Global Configurations</strong></a> instead.', 'wp-mylinks'),
					'sanitization_cb' => 'wp_mylinks_sanitization_func',
				),
				array(
					'name'            => __('Footer Script', 'wp-mylinks'),
					'desc'            => __('Anything you put here will be placed just before <code>&lt;/body&gt;</code>. Please include <code>&lt;script&gt;</code> etc.', 'wp-mylinks'),
					'id'              => mylinks_prefix('mylinks-single-custom-footer-script'),
					'type'            => 'textarea_small',
					'sanitization_cb' => 'wp_mylinks_sanitization_func',
				),
				array(
					'name' => __('Custom Styles', 'wp-mylinks'),
					'desc' => __('Add your custom css code without the <code>&lt;style&gt;</code> tag.', 'wp-mylinks'),
					'id'   => mylinks_prefix('mylinks-single-custom-styles'),
					'type' => 'textarea_small',
				),
			),
		)
	);

	// -------------------------------------------------------------------
	// 7. Collection link.
	// -------------------------------------------------------------------
	wp_mylinks_register_metabox(
		array(
			'id'           => mylinks_collection('form'),
			'title'        => esc_html__('Link', 'wp-mylinks'),
			'object_types' => array( 'mylinks-collection' ),
			'context'      => 'normal',
			'priority'     => 'high',
			'fields'       => array(
				array(
					'name'       => __('Link', 'wp-mylinks'),
					'desc'       => __('Include the http:// or https:// protocol, including trailing slash (/) if applicable.', 'wp-mylinks'),
					'id'         => mylinks_collection('link_collection'),
					'type'       => 'text_url',
					'before_row' => __('If you have a link that you will reuse later, add it here so upon building your MyLink page, this link will be searchable.', 'wp-mylinks'),
					'attributes' => array(
						'class' => 'mylinks-input-url',
					),
				),
			),
		)
	);
}
add_action( 'init', 'wp_mylinks_register_metaboxes' );

/**
 * Drop core's Custom Fields metabox from the plugin's editors.
 *
 * The mylink CPT only supports 'title'; the raw postmeta editor invites users
 * to hand-edit the structured meta the plugin owns, so it has no place here.
 *
 * @since 1.1.0
 * @return void
 */
function wp_mylinks_remove_core_metaboxes() {
	remove_meta_box( 'postcustom', 'mylink', 'normal' );
	remove_meta_box( 'postcustom', 'mylinks-collection', 'normal' );
}
add_action( 'add_meta_boxes', 'wp_mylinks_remove_core_metaboxes', 20 );
