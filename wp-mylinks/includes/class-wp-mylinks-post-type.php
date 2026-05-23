<?php

/**
 * Register post type and meta boxes.
 *
 * @link       https://walterpinem.me/
 * @since      1.0.0
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes
 * @author     Walter Pinem <hello@walterpinem.me>
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Register Custom Post Type for MyLink.
 *
 * IMPORTANT: We register with a real slug ('mylink'). Slug stripping is handled
 * by the rewrite layer in includes/class-wp-mylinks-rewrites.php. Using a real
 * slug here keeps WordPress's rewrite engine happy and avoids conflicts with
 * pages, posts, taxonomies, and pagination.
 *
 * @since 1.0.0
 */
function wp_mylinks_register_post_type()
{
	$labels = array(
		'name'                  => _x('MyLinks', 'Post Type General Name', 'wp-mylinks'),
		'singular_name'         => _x('MyLink', 'Post Type Singular Name', 'wp-mylinks'),
		'menu_name'             => _x('MyLinks', 'Admin Menu text', 'wp-mylinks'),
		'name_admin_bar'        => _x('MyLink', 'Add New on Toolbar', 'wp-mylinks'),
		'archives'              => __('MyLink Archives', 'wp-mylinks'),
		'attributes'            => __('MyLink Attributes', 'wp-mylinks'),
		'parent_item_colon'     => __('Parent MyLink:', 'wp-mylinks'),
		'all_items'             => __('All MyLinks', 'wp-mylinks'),
		'add_new_item'          => __('Add New MyLink', 'wp-mylinks'),
		'add_new'               => __('Add New', 'wp-mylinks'),
		'new_item'              => __('New MyLink', 'wp-mylinks'),
		'edit_item'             => __('Edit MyLink', 'wp-mylinks'),
		'update_item'           => __('Update MyLink', 'wp-mylinks'),
		'view_item'             => __('View MyLink', 'wp-mylinks'),
		'view_items'            => __('View MyLinks', 'wp-mylinks'),
		'search_items'          => __('Search MyLink', 'wp-mylinks'),
		'not_found'             => __('Not found', 'wp-mylinks'),
		'not_found_in_trash'    => __('Not found in Trash', 'wp-mylinks'),
		'featured_image'        => __('Featured Image', 'wp-mylinks'),
		'set_featured_image'    => __('Featured Image', 'wp-mylinks'),
		'remove_featured_image' => __('Remove featured image', 'wp-mylinks'),
		'use_featured_image'    => __('Use as featured image', 'wp-mylinks'),
		'insert_into_item'      => __('Insert into MyLink', 'wp-mylinks'),
		'uploaded_to_this_item' => __('Uploaded to this MyLink', 'wp-mylinks'),
		'items_list'            => __('MyLinks list', 'wp-mylinks'),
		'items_list_navigation' => __('MyLinks list navigation', 'wp-mylinks'),
		'filter_items_list'     => __('Filter MyLinks list', 'wp-mylinks'),
	);

	/**
	 * Filter the rewrite slug used by the mylink post type.
	 *
	 * Defaults to "mylink". The slug-stripping layer rewrites public URLs so
	 * visitors see /<post-name>/ regardless of this slug.
	 *
	 * @since 1.0.8
	 *
	 * @param string $slug The CPT rewrite slug.
	 */
	$cpt_slug = apply_filters('wp_mylinks_cpt_rewrite_slug', 'mylink');

	$rewrite = array(
		'slug'       => $cpt_slug,
		'with_front' => false,
		'pages'      => false,
		'feeds'      => false,
	);

	$args = array(
		'label'               => __('MyLink', 'wp-mylinks'),
		'description'         => __('Create micro landing pages that host all of your contents, products, etc to engage your audience and increase your brand awareness.', 'wp-mylinks'),
		'labels'              => $labels,
		'menu_icon'           => 'dashicons-editor-unlink',
		'supports'            => array('title'),
		'taxonomies'          => array(),
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 26,
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => false,
		'hierarchical'        => false,
		'exclude_from_search' => true,
		'show_in_rest'        => true,
		'query_var'           => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'page',
		'rewrite'             => $rewrite,
	);
	register_post_type('mylink', $args);
}
add_action('init', 'wp_mylinks_register_post_type', 0);

// Generate Metabox for MyLink post type.
add_action('cmb2_admin_init', 'wp_mylinks_register_cmb2_metaboxes');

/**
 * Define the metabox and field configurations.
 */
function wp_mylinks_register_cmb2_metaboxes()
{
	$cmb = new_cmb2_box(
		array(
			'id'           => mylinks_prefix('form'),
			'title'        => __('MyLinks Profile', 'wp-mylinks'),
			'object_types' => array('mylink'),
			'context'      => 'normal',
			'priority'     => 'high',
		)
	);
	$cmb->add_field(
		array(
			'name'         => __('Avatar', 'wp-mylinks'),
			'desc'         => __('Upload an avatar, preferably square in size.', 'wp-mylinks'),
			'id'           => mylinks_prefix('avatar'),
			'type'         => 'file',
			'before_row'   => __('Build a profile section for your MyLinks page.', 'wp-mylinks'),
			'options'      => array(
				'url' => false, // Hide the text input for the URL.
			),
			'text'         => array(
				'add_upload_file_text' => __('Choose Avatar', 'wp-mylinks'),
			),
			'query_args'   => array(
				'type' => 'image',
			),
			'preview_size' => 'thumbnail',
		)
	);
	$cmb->add_field(
		array(
			'name'             => __('Avatar Style', 'wp-mylinks'),
			'id'               => mylinks_prefix('avatar-style'),
			'desc'             => __('Choose the avatar style.', 'wp-mylinks'),
			'type'             => 'radio_inline',
			'show_option_none' => false,
			'options'          => array(
				'story-like'  => __('Story-Like Border', 'wp-mylinks'),
				'shadow'      => __('Shadowy', 'wp-mylinks'),
				'plain'       => __('Plain', 'wp-mylinks'),
				'transparent' => __('Transparent', 'wp-mylinks'),
			),
			'default'          => 'story-like',
		)
	);
	$cmb->add_field(
		array(
			'name'       => __('Name', 'wp-mylinks'),
			'desc'       => __('Add your name or username that will be shown under your avatar.', 'wp-mylinks'),
			'id'         => mylinks_prefix('name'),
			'type'       => 'text',
			'attributes' => array(
				'class' => 'mylinks-input-text',
			),
			'options'    => array(),
		)
	);
	$cmb->add_field(
		array(
			'name'       => __('Description', 'wp-mylinks'),
			'desc'       => __('Add your profile description that will be shown under your name. Compatible with HTML.', 'wp-mylinks'),
			'id'         => mylinks_prefix('description'),
			'type'       => 'wysiwyg',
			'options'    => array(
				'wpautop'       => true,
				'media_buttons' => false,
				'textarea_rows' => get_option('default_post_edit_rows', 5),
				'teeny'         => true,
			),
			'attributes' => array(
				'class' => 'mylinks-input-textarea',
			),
		)
	);
	$cmb->add_field(
		array(
			'name'             => __('Theme', 'wp-mylinks'),
			'desc'             => __('Choose a theme that matches your personal or business brand.', 'wp-mylinks'),
			'id'               => mylinks_prefix('theme'),
			'before_row'       => __('Determine how you want the MyLinks page to look like. You can set its theme individually here or select <code>None</code>to<br> apply global theme you set on <a href="edit.php?post_type=mylink&page=welcome&tab=global" target="_blank"><strong>Global Configurations</strong></a> page instead.', 'wp-mylinks'),
			'type'             => 'select',
			'show_option_none' => false,
			'default'          => 'default',
			'attributes'       => array(
				'class' => 'mylinks-input-select',
			),
			'options_cb'       => 'wp_mylinks_theme_callback',
		)
	);

	// Social Media Profiles.
	$cmb = new_cmb2_box(
		array(
			'id'           => mylinks_prefix('form-social'),
			'title'        => __('Social Media', 'wp-mylinks'),
			'object_types' => array('mylink'),
			'context'      => 'normal',
			'priority'     => 'high',
		)
	);
	$cmb->add_field(
		array(
			'name'             => __('Position', 'wp-mylinks'),
			'id'               => mylinks_prefix('social-media-position'),
			'desc'             => __('Choose the Social Media profile icons position.', 'wp-mylinks'),
			'type'             => 'radio_inline',
			'show_option_none' => false,
			'options'          => array(
				'top'    => __('Top', 'wp-mylinks'),
				'bottom' => __('Bottom', 'wp-mylinks'),
			),
			'default'          => 'top',
		)
	);

	// Helper text used by every social-icon picker (translated once).
	$choose_icon_text = __('Choose Icon', 'wp-mylinks');

	// Facebook.
	$cmb->add_field(
		array(
			'name'       => __('Facebook URL', 'wp-mylinks'),
			'desc'       => __('Add your Facebook profile.', 'wp-mylinks'),
			'id'         => mylinks_prefix('facebook-url'),
			'type'       => 'text_url',
			'before_row' => __('Setup social links that will appear under your profile section.', 'wp-mylinks'),
			'attributes' => array(
				'class' => 'mylinks-input-url',
			),
			'options'    => array(),
		)
	);
	$cmb->add_field(
		array(
			'name'         => __('Facebook Icon', 'wp-mylinks'),
			'desc'         => __('If you don\'t like the default icon please upload your own icon for Facebook, preferably square in size.', 'wp-mylinks'),
			'id'           => mylinks_prefix('facebook-icon'),
			'type'         => 'file',
			'options'      => array(
				'url' => true,
			),
			'text'         => array(
				'add_upload_file_text' => $choose_icon_text,
			),
			'query_args'   => array(
				'type' => 'image',
			),
			'preview_size' => 'thumbnail',
		)
	);
	// Twitter.
	$cmb->add_field(
		array(
			'name'       => __('Twitter URL', 'wp-mylinks'),
			'desc'       => __('Add your Twitter profile.', 'wp-mylinks'),
			'id'         => mylinks_prefix('twitter-url'),
			'type'       => 'text_url',
			'attributes' => array(
				'class' => 'mylinks-input-url',
			),
			'options'    => array(),
		)
	);
	$cmb->add_field(
		array(
			'name'         => __('Twitter Icon', 'wp-mylinks'),
			'desc'         => __('If you don\'t like the default icon please upload your own icon for Twitter, preferably square in size.', 'wp-mylinks'),
			'id'           => mylinks_prefix('twitter-icon'),
			'type'         => 'file',
			'options'      => array(
				'url' => true,
			),
			'text'         => array(
				'add_upload_file_text' => $choose_icon_text,
			),
			'query_args'   => array(
				'type' => 'image',
			),
			'preview_size' => 'thumbnail',
		)
	);
	// Linkedin.
	$cmb->add_field(
		array(
			'name'       => __('Linkedin URL', 'wp-mylinks'),
			'desc'       => __('Add your Linkedin profile.', 'wp-mylinks'),
			'id'         => mylinks_prefix('linkedin-url'),
			'type'       => 'text_url',
			'attributes' => array(
				'class' => 'mylinks-input-url',
			),
			'options'    => array(),
		)
	);
	$cmb->add_field(
		array(
			'name'         => __('Linkedin Icon', 'wp-mylinks'),
			'desc'         => __('If you don\'t like the default icon please upload your own icon for Linkedin, preferably square in size.', 'wp-mylinks'),
			'id'           => mylinks_prefix('linkedin-icon'),
			'type'         => 'file',
			'options'      => array(
				'url' => true,
			),
			'text'         => array(
				'add_upload_file_text' => $choose_icon_text,
			),
			'query_args'   => array(
				'type' => 'image',
			),
			'preview_size' => 'thumbnail',
		)
	);
	// Instagram.
	$cmb->add_field(
		array(
			'name'       => __('Instagram URL', 'wp-mylinks'),
			'desc'       => __('Add your Instagram profile.', 'wp-mylinks'),
			'id'         => mylinks_prefix('instagram-url'),
			'type'       => 'text_url',
			'attributes' => array(
				'class' => 'mylinks-input-url',
			),
			'options'    => array(),
		)
	);
	$cmb->add_field(
		array(
			'name'         => __('Instagram Icon', 'wp-mylinks'),
			'desc'         => __('If you don\'t like the default icon please upload your own icon for Instagram, preferably square in size.', 'wp-mylinks'),
			'id'           => mylinks_prefix('instagram-icon'),
			'type'         => 'file',
			'options'      => array(
				'url' => true,
			),
			'text'         => array(
				'add_upload_file_text' => $choose_icon_text,
			),
			'query_args'   => array(
				'type' => 'image',
			),
			'preview_size' => 'thumbnail',
		)
	);
	// Youtube.
	$cmb->add_field(
		array(
			'name'       => __('Youtube URL', 'wp-mylinks'),
			'desc'       => __('Add your Youtube profile.', 'wp-mylinks'),
			'id'         => mylinks_prefix('youtube-url'),
			'type'       => 'text_url',
			'attributes' => array(
				'class' => 'mylinks-input-url',
			),
			'options'    => array(),
		)
	);
	$cmb->add_field(
		array(
			'name'         => __('Youtube Icon', 'wp-mylinks'),
			'desc'         => __('If you don\'t like the default icon please upload your own icon for Youtube, preferably square in size.', 'wp-mylinks'),
			'id'           => mylinks_prefix('youtube-icon'),
			'type'         => 'file',
			'options'      => array(
				'url' => true,
			),
			'text'         => array(
				'add_upload_file_text' => $choose_icon_text,
			),
			'query_args'   => array(
				'type' => 'image',
			),
			'preview_size' => 'thumbnail',
		)
	);
	// Pinterest.
	$cmb->add_field(
		array(
			'name'       => __('Pinterest URL', 'wp-mylinks'),
			'desc'       => __('Add your Pinterest profile.', 'wp-mylinks'),
			'id'         => mylinks_prefix('pinterest-url'),
			'type'       => 'text_url',
			'attributes' => array(
				'class' => 'mylinks-input-url',
			),
			'options'    => array(),
		)
	);
	$cmb->add_field(
		array(
			'name'         => __('Pinterest Icon', 'wp-mylinks'),
			'desc'         => __('If you don\'t like the default icon please upload your own icon for Pinterest, preferably square in size.', 'wp-mylinks'),
			'id'           => mylinks_prefix('pinterest-icon'),
			'type'         => 'file',
			'options'      => array(
				'url' => true,
			),
			'text'         => array(
				'add_upload_file_text' => $choose_icon_text,
			),
			'query_args'   => array(
				'type' => 'image',
			),
			'preview_size' => 'thumbnail',
		)
	);
	// TikTok.
	$cmb->add_field(
		array(
			'name'       => __('TikTok URL', 'wp-mylinks'),
			'desc'       => __('Add your TikTok profile.', 'wp-mylinks'),
			'id'         => mylinks_prefix('tiktok-url'),
			'type'       => 'text_url',
			'attributes' => array(
				'class' => 'mylinks-input-url',
			),
			'options'    => array(),
		)
	);
	$cmb->add_field(
		array(
			'name'         => __('TikTok Icon', 'wp-mylinks'),
			'desc'         => __('If you don\'t like the default icon please upload your own icon for TikTok, preferably square in size.', 'wp-mylinks'),
			'id'           => mylinks_prefix('tiktok-icon'),
			'type'         => 'file',
			'options'      => array(
				'url' => true,
			),
			'text'         => array(
				'add_upload_file_text' => $choose_icon_text,
			),
			'query_args'   => array(
				'type' => 'image',
			),
			'preview_size' => 'thumbnail',
		)
	);
	// Discord.
	$cmb->add_field(
		array(
			'name'       => __('Discord URL', 'wp-mylinks'),
			'desc'       => __('Add your Discord profile.', 'wp-mylinks'),
			'id'         => mylinks_prefix('discord-url'),
			'type'       => 'text_url',
			'attributes' => array(
				'class' => 'mylinks-input-url',
			),
			'options'    => array(),
		)
	);
	$cmb->add_field(
		array(
			'name'         => __('Discord Icon', 'wp-mylinks'),
			'desc'         => __('If you don\'t like the default icon please upload your own icon for Discord, preferably square in size.', 'wp-mylinks'),
			'id'           => mylinks_prefix('discord-icon'),
			'type'         => 'file',
			'options'      => array(
				'url' => true,
			),
			'text'         => array(
				'add_upload_file_text' => $choose_icon_text,
			),
			'query_args'   => array(
				'type' => 'image',
			),
			'preview_size' => 'thumbnail',
		)
	);
	// END - Social Media Profiles.

	// Additional Social Platforms (1.0.8+) — additive repeater that supplements
	// the existing hardcoded 8 platforms above. Existing meta keys are not
	// touched, so users who upgrade keep their configured Facebook / Twitter /
	// etc. profiles intact.
	$cmb_socials_extra = new_cmb2_box(
		array(
			'id'           => mylinks_prefix('form-social-additional'),
			'title'        => __('Additional Social Platforms', 'wp-mylinks'),
			'object_types' => array('mylink'),
			'context'      => 'normal',
			'priority'     => 'high',
		)
	);
	$socials_group = $cmb_socials_extra->add_field(
		array(
			'id'          => mylinks_prefix('additional-socials'),
			'type'        => 'group',
			'description' => __('Add any social platform that is not in the list above (e.g. Threads, Bluesky, GitHub, Mastodon, WhatsApp, Telegram, Signal). Each row will appear in the same icon row as the platforms above.', 'wp-mylinks'),
			'options'     => array(
				'group_title'    => __('Platform {#}', 'wp-mylinks'),
				'add_button'     => __('Add Another Platform', 'wp-mylinks'),
				'remove_button'  => __('Remove Platform', 'wp-mylinks'),
				'sortable'       => true,
				'remove_confirm' => esc_html__('Remove this social platform?', 'wp-mylinks'),
			),
		)
	);
	$cmb_socials_extra->add_group_field(
		$socials_group,
		array(
			'name'        => __('Platform Name', 'wp-mylinks'),
			'description' => __('Used as the alt text and accessibility label (e.g. "Threads", "Bluesky", "GitHub").', 'wp-mylinks'),
			'id'          => 'name',
			'type'        => 'text',
			'attributes'  => array(
				'class' => 'mylinks-input-text',
			),
		)
	);
	$cmb_socials_extra->add_group_field(
		$socials_group,
		array(
			'name'        => __('Profile URL', 'wp-mylinks'),
			'description' => __('Include the http:// or https:// protocol.', 'wp-mylinks'),
			'id'          => 'url',
			'type'        => 'text_url',
			'protocols'   => array('http', 'https', 'mailto', 'tel'),
			'attributes'  => array(
				'class' => 'mylinks-input-url',
			),
		)
	);
	$cmb_socials_extra->add_group_field(
		$socials_group,
		array(
			'name'         => __('Icon', 'wp-mylinks'),
			'description'  => __('Optional. Upload a square icon (PNG or SVG, ~64×64). If left empty, a generic globe icon is used.', 'wp-mylinks'),
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
		)
	);

	// Links.
	$cmb         = new_cmb2_box(
		array(
			'id'           => mylinks_prefix('form-links'),
			'title'        => __('Links', 'wp-mylinks'),
			'object_types' => array('mylink'),
			'context'      => 'normal',
			'priority'     => 'high',
		)
	);
	$links_group = $cmb->add_field(
		array(
			'id'          => mylinks_prefix('links'),
			'type'        => 'group',
			'description' => __('Set a link and its title. You can add unlimited number of buttons to your liking by clicking "Add Another Link" button.', 'wp-mylinks'),
			'options'     => array(
				'group_title'    => __('Link {#}', 'wp-mylinks'),
				'add_button'     => __('Add Another Link', 'wp-mylinks'),
				'remove_button'  => __('Remove Link', 'wp-mylinks'),
				'sortable'       => true,
				'remove_confirm' => esc_html__('Really? Remove This Link?', 'wp-mylinks'),
			),
		)
	);
	$cmb->add_group_field(
		$links_group,
		array(
			'name'       => __('Title', 'wp-mylinks'),
			'id'         => 'title',
			'type'       => 'text',
			'attributes' => array(
				'class' => 'mylinks-input-text',
			),
		)
	);

	// Prepare the description with a dynamic URL.
	$collection_url = esc_url(admin_url('edit.php?post_type=mylinks-collection'));

	// Translators: %s is the URL to the MyLinks Collection page.
	$description = sprintf(
		/* translators: %s: MyLinks Collection page URL */
		__('Select a link from the list you have previously prepared on <a href="%s" target="_blank"><strong>Collection</strong></a> page, existing posts or pages.', 'wp-mylinks'),
		$collection_url
	);

	// Add group field.
	$cmb->add_group_field(
		$links_group,
		array(
			'name'              => __('Link URL', 'wp-mylinks'),
			'id'                => 'select_url',
			'type'              => 'pw_select',
			'description'       => $description,
			'options_cb'        => 'wp_mylinks_get_cmb2_post_options',
			'wp_query_args'     => array(
				'post_type' => array('page', 'post', 'mylinks-collection'),
			),
			'attributes'        => array(
				'class'                         => array('mylinks-input-select2'),
				'placeholder'                   => __('Select a link...', 'wp-mylinks'),
				'data-maximum-selection-length' => '2',
			),
			'select_all_button' => false,
		)
	);

	$cmb->add_group_field(
		$links_group,
		array(
			'name'        => __('Selected URL', 'wp-mylinks'),
			'id'          => 'url',
			'type'        => 'text_url',
			'protocols'   => array('http', 'https', 'mailto'), // Array of allowed protocols.
			'description' => __('If you want to manually add a link or edit it, just do it here.', 'wp-mylinks'),
			'attributes'  => array(
				'class' => 'mylinks-selected-url',
			),
		)
	);

	$cmb->add_group_field(
		$links_group,
		array(
			'name'         => __('Image', 'wp-mylinks'),
			'id'           => 'image',
			'type'         => 'file',
			'preview_size' => 'thumbnail',
			'description'  => __('Add featured image for your link, preferably square in size or use a landscape-sized image if you choose <b>Yes</b> for the <b>Use Card Layout for this Link?</b> option below.', 'wp-mylinks'),
			'options'      => array(
				'add_upload_file_text' => __('Featured Image', 'wp-mylinks'),
			),
		)
	);
	$cmb->add_group_field(
		$links_group,
		array(
			'name'             => __('Use Card Layout for this Link?', 'wp-mylinks'),
			'id'               => 'card-layout',
			'desc'             => __('If <b>Yes</b> is chosen, the featured image you chose on the <b>Image</b> section above will be used as the background image of this link. <br/>Please make sure to fill all the <b>Title</b>, <b>URL</b>, <b>Image</b> fields above and <b>empty</b> the Youtube Video field below.', 'wp-mylinks'),
			'type'             => 'radio',
			'show_option_none' => false,
			'options'          => array(
				'yes' => __('Yes', 'wp-mylinks'),
				'no'  => __('No', 'wp-mylinks'),
			),
			'default'          => 'no',
		)
	);
	$cmb->add_group_field(
		$links_group,
		array(
			'name'        => __('Youtube Video', 'wp-mylinks'),
			'id'          => 'youtube-video',
			'type'        => 'oembed',
			'attributes'  => array(
				'class' => 'mylinks-input-url',
			),
			'description' => __('Enter the full Youtube video URL. <b>Please do not fill</b> the other fields, make sure to only fill the Youtube URL field.', 'wp-mylinks'),
		)
	);
	$cmb->add_group_field(
		$links_group,
		array(
			'name'        => __('Media Embed', 'wp-mylinks'),
			'id'          => 'media-embed',
			'type'        => 'oembed',
			'attributes'  => array(
				'class' => 'mylinks-input-url',
			),
			'description' => __('You can embed TikTok videos, Tweets, Spotify playlist, or any other media from the list of <a href="https://wordpress.org/documentation/article/embeds/#list-of-sites-you-can-embed-from" target="_blank" rel="noopener noreferrer">WordPress\'s supported sites</a>.', 'wp-mylinks'),
		)
	);

	// Meta Tags.
	$cmb = new_cmb2_box(
		array(
			'id'           => mylinks_prefix('form-meta-tags'),
			'title'        => __('Setup Meta Tags & Favicon', 'wp-mylinks'),
			'description'  => __('If you use Yoast SEO, set the meta title on Yoast\'s meta box instead.', 'wp-mylinks'),
			'object_types' => array('mylink'),
			'context'      => 'normal',
			'priority'     => 'high',
		)
	);
	$cmb->add_field(
		array(
			'name'       => __('Meta Title', 'wp-mylinks'),
			'desc'       => __('Set the meta title for this page.', 'wp-mylinks'),
			'id'         => mylinks_prefix('meta-title'),
			'type'       => 'text',
			'before_row' => __('If you use Yoast SEO, you can set the meta tags on Yoast SEO\'s meta box. Or you can also set global meta tags on<br> <a href="edit.php?post_type=mylink&page=welcome&tab=global" target="_blank"><strong>Global Configurations</strong></a>. You can also set custom favicon independently for this MyLinks page.', 'wp-mylinks'),
			'attributes' => array(
				'class' => 'mylinks-input-text',
			),
			'options'    => array(),
		)
	);
	$cmb->add_field(
		array(
			'name'       => __('Meta Description', 'wp-mylinks'),
			'desc'       => __('Set the meta description for this page.', 'wp-mylinks'),
			'id'         => mylinks_prefix('meta-description'),
			'type'       => 'textarea_small',
			'attributes' => array(
				'class' => 'mylinks-input-textarea',
			),
			'options'    => array(),
		)
	);
	$cmb->add_field(
		array(
			'name'             => __('Set to Noindex?', 'wp-mylinks'),
			'desc'             => __('Override the noindex behavior for this page. Leave on "Inherit" to use the global setting (Settings → MyLinks → Global).', 'wp-mylinks'),
			'id'               => mylinks_prefix('noindex'),
			'type'             => 'radio_inline',
			'show_option_none' => false,
			'options'          => array(
				''    => __('Inherit (use global)', 'wp-mylinks'),
				'yes' => __('Yes', 'wp-mylinks'),
				'no'  => __('No', 'wp-mylinks'),
			),
			'default'          => '',
		)
	);
	$cmb->add_field(
		array(
			'name'             => __('Set to Nofollow?', 'wp-mylinks'),
			'desc'             => __('Override the nofollow behavior for this page. Leave on "Inherit" to use the global setting (Settings → MyLinks → Global).', 'wp-mylinks'),
			'id'               => mylinks_prefix('nofollow'),
			'type'             => 'radio_inline',
			'show_option_none' => false,
			'options'          => array(
				''    => __('Inherit (use global)', 'wp-mylinks'),
				'yes' => __('Yes', 'wp-mylinks'),
				'no'  => __('No', 'wp-mylinks'),
			),
			'default'          => '',
		)
	);
	$cmb->add_field(
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
		)
	);

	// Schema.org per-page override (1.0.8+).
	$schema_settings_url = esc_url( admin_url( 'edit.php?post_type=mylink&page=welcome&tab=global' ) );
	$schema_before_row   = wp_kses(
		sprintf(
			/* translators: 1: bolded "Enable JSON-LD Schema?" label, 2: bolded link to the global settings page */
			__( 'JSON-LD output is only emitted if the global %1$s option is on (%2$s). When Yoast SEO is active and emitting its own schema, WP MyLinks defers to Yoast automatically.', 'wp-mylinks' ),
			'<strong>"' . esc_html__( 'Enable JSON-LD Schema?', 'wp-mylinks' ) . '"</strong>',
			'<a href="' . $schema_settings_url . '" target="_blank" rel="noopener"><strong>' . esc_html__( 'Settings → MyLinks → Global', 'wp-mylinks' ) . '</strong></a>'
		),
		array(
			'strong' => array(),
			'a'      => array(
				'href'   => array(),
				'target' => array(),
				'rel'    => array(),
			),
		)
	);

	$cmb->add_field(
		array(
			'name'             => __('Schema Type', 'wp-mylinks'),
			'desc'             => __('Override the Schema.org JSON-LD type for this page. Leave on "Inherit" to use the global setting.', 'wp-mylinks'),
			'id'               => mylinks_prefix('schema-type'),
			'before_row'       => $schema_before_row,
			'type'             => 'radio_inline',
			'show_option_none' => false,
			'options'          => array(
				''             => __('Inherit (use global)', 'wp-mylinks'),
				'Person'       => __('Person', 'wp-mylinks'),
				'Organization' => __('Organization', 'wp-mylinks'),
			),
			'default'          => '',
		)
	);

	// Per-page Open Graph share image (1.0.8+).
	$og_settings_url = esc_url( admin_url( 'edit.php?post_type=mylink&page=welcome&tab=global' ) );
	$og_before_row   = wp_kses(
		sprintf(
			/* translators: 1: bolded "Enable Open Graph & Twitter Card?" label, 2: bolded link to the global settings page */
			__( 'Open Graph and Twitter Card output is only emitted if the global %1$s option is on (%2$s). When Yoast SEO is active and handling Open Graph, WP MyLinks defers to Yoast automatically.', 'wp-mylinks' ),
			'<strong>"' . esc_html__( 'Enable Open Graph & Twitter Card?', 'wp-mylinks' ) . '"</strong>',
			'<a href="' . $og_settings_url . '" target="_blank" rel="noopener"><strong>' . esc_html__( 'Settings → MyLinks → Global', 'wp-mylinks' ) . '</strong></a>'
		),
		array(
			'strong' => array(),
			'a'      => array(
				'href'   => array(),
				'target' => array(),
				'rel'    => array(),
			),
		)
	);

	$cmb->add_field(
		array(
			'name'         => __('Share Image (Open Graph)', 'wp-mylinks'),
			'desc'         => __('Recommended size: 1200×630 pixels. Used for Facebook, X (Twitter), LinkedIn, and Discord previews. If left empty, this page falls back to the global Default Share Image, then to the avatar.', 'wp-mylinks'),
			'id'           => mylinks_prefix('og-image'),
			'before_row'   => $og_before_row,
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
		)
	);

	// Additional Social Platforms (1.0.8+) — registered earlier (right after
	// the existing Social Media metabox) so it appears immediately under the
	// hardcoded 8 platforms in the post-edit screen. See the registration
	// block above for the full field definition.

	// Custom Script and Styles.
	$cmb = new_cmb2_box(
		array(
			'id'           => mylinks_prefix('form-custom-script-styles'),
			'title'        => __('Custom Scripts & Styles', 'wp-mylinks'),
			'description'  => __('Add custom script and styles independently for this MyLinks page.', 'wp-mylinks'),
			'object_types' => array('mylink'),
			'context'      => 'normal',
			'priority'     => 'high',
		)
	);
	$cmb->add_field(
		array(
			'name'            => __('Header Script', 'wp-mylinks'),
			'desc'            => __('Anything you put here will be included inside <code>&lt;head&gt;</code>. Please include <code>&lt;script&gt;</code> etc.', 'wp-mylinks'),
			'id'              => mylinks_prefix('mylinks-single-custom-header-script'),
			'type'            => 'textarea_small',
			'before_row'      => __('Add custom scripts and styles independently for this MyLinks page. Or you can leave these fields blank and set it on<br> <a href="edit.php?post_type=mylink&page=welcome&tab=script" target="_blank"><strong>Global Configurations</strong></a> instead.', 'wp-mylinks'),
			'attributes'      => array(
				'class' => 'mylinks-input-textarea',
			),
			'sanitization_cb' => 'wp_mylinks_sanitization_func',
			'options'         => array(),
		)
	);
	$cmb->add_field(
		array(
			'name'            => __('Footer Script', 'wp-mylinks'),
			'desc'            => __('Anything you put here will be placed just before <code>&lt;/body&gt;</code>. Please include <code>&lt;script&gt;</code> etc.', 'wp-mylinks'),
			'id'              => mylinks_prefix('mylinks-single-custom-footer-script'),
			'type'            => 'textarea_small',
			'attributes'      => array(
				'class' => 'mylinks-input-textarea',
			),
			'sanitization_cb' => 'wp_mylinks_sanitization_func',
			'options'         => array(),
		)
	);
	$cmb->add_field(
		array(
			'name'       => __('Custom Styles', 'wp-mylinks'),
			'desc'       => __('Add your custom css code without the <code>&lt;style&gt;</code> tag.', 'wp-mylinks'),
			'id'         => mylinks_prefix('mylinks-single-custom-styles'),
			'type'       => 'textarea_small',
			'attributes' => array(
				'class' => 'mylinks-input-textarea',
			),
			'options'    => array(),
		)
	);
}

/**
 * Custom side meta box: UTM Tag Generator quick links.
 *
 * @since 1.0.3
 */
function wp_mylinks_custom_sidebar_utm()
{
	$en_url = function_exists('wpmylinks_url')
		? wpmylinks_url('https://walterpinem.me/projects/utm-tag-campaign-builder/')
		: 'https://walterpinem.me/projects/utm-tag-campaign-builder/';
	$id_url = function_exists('wpmylinks_url')
		? wpmylinks_url('https://www.seniberpikir.com/utm-campaign-builder-google-analytics/')
		: 'https://www.seniberpikir.com/utm-campaign-builder-google-analytics/';
	?>
	<ul>
		<li>
			<a href="<?php echo esc_url($en_url); ?>" target="_blank" rel="noopener" style="text-decoration: none !important;">
				<strong><?php echo esc_html__('🇬🇧 English Version', 'wp-mylinks'); ?></strong>
			</a>
		</li>
		<li>
			<a href="<?php echo esc_url($id_url); ?>" target="_blank" rel="noopener" style="text-decoration: none !important;">
				<strong><?php echo esc_html__('🇮🇩 Bahasa Version', 'wp-mylinks'); ?></strong>
			</a>
		</li>
	</ul>
	<?php
}

/**
 * Custom side meta box: Support / project quick links.
 *
 * Renamed from wp_mylinks_custom_sidebar_suppport (typo) in 1.0.8. Aliased
 * below for any code that may have referenced the old name.
 *
 * @since 1.0.3
 */
function wp_mylinks_custom_sidebar_support()
{
	$review_url = 'https://wordpress.org/support/plugin/wp-mylinks/reviews/?rate=5#new-post';
	$contact    = function_exists('wpmylinks_url')
		? wpmylinks_url('https://walterpinem.me/projects/contact/')
		: 'https://walterpinem.me/projects/contact/';
	$paypal     = 'https://paypal.me/walterpinem';
	$tools      = function_exists('wpmylinks_url')
		? wpmylinks_url('https://walterpinem.me/projects/tools/')
		: 'https://walterpinem.me/projects/tools/';
	?>
	<ul>
		<li>
			<a href="<?php echo esc_url($review_url); ?>" target="_blank" rel="noopener" style="text-decoration: none !important;">
				<strong><?php echo esc_html__('⭐ Rate This Plugin', 'wp-mylinks'); ?></strong>
			</a>
		</li>
		<li>
			<a href="<?php echo esc_url($contact); ?>" target="_blank" rel="noopener" style="text-decoration: none !important;">
				<strong><?php echo esc_html__('📋 Support & Feature Request', 'wp-mylinks'); ?></strong>
			</a>
		</li>
		<li>
			<a href="<?php echo esc_url($paypal); ?>" target="_blank" rel="noopener" style="text-decoration: none !important;">
				<strong><?php echo esc_html__('☕ Buy Me a Coffee', 'wp-mylinks'); ?></strong>
			</a>
		</li>
		<li>
			<a href="<?php echo esc_url($tools); ?>" target="_blank" rel="noopener" style="text-decoration: none !important;">
				<strong><?php echo esc_html__('🛠️ 100% Free Online Tools', 'wp-mylinks'); ?></strong>
			</a>
		</li>
	</ul>
	<?php
}

/**
 * Backward-compatible alias for the misspelled function name shipped in
 * versions ≤ 1.0.7. Kept as a thin wrapper so any direct callers keep working.
 *
 * @deprecated 1.0.8 Use wp_mylinks_custom_sidebar_support() instead.
 */
if (!function_exists('wp_mylinks_custom_sidebar_suppport')) {
	function wp_mylinks_custom_sidebar_suppport()
	{
		wp_mylinks_custom_sidebar_support();
	}
}

/**
 * Register the side meta boxes on the mylink and mylinks-collection screens.
 */
function wp_mylinks_add_side_meta_box()
{
	add_meta_box(
		'mylink-sidebar-utm',
		__('UTM Campaign Builder', 'wp-mylinks'),
		'wp_mylinks_custom_sidebar_utm',
		array('mylink', 'mylinks-collection'),
		'side',
		'low',
		null
	);
	add_meta_box(
		'mylink-sidebar-support',
		__('Support', 'wp-mylinks'),
		'wp_mylinks_custom_sidebar_support',
		array('mylink', 'mylinks-collection'),
		'side',
		'low',
		null
	);
}
add_action('add_meta_boxes', 'wp_mylinks_add_side_meta_box');
