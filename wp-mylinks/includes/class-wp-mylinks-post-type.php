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
if ( ! defined('ABSPATH') ) {
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
function wp_mylinks_register_post_type() {
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
		'menu_icon'           => WP_MYLINKS_URL . 'admin/images/wp-mylinks-icon.png',
		'supports'            => array( 'title' ),
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

/**
 * Size the PNG menu icon. Core ships no width rule for image menu icons, so
 * the 128px source would render full-size; it also dims images to 0.6 opacity,
 * which muddies a colored brand mark.
 *
 * @since 1.2.1
 */
function wp_mylinks_menu_icon_css() {
	echo '<style>#menu-posts-mylink .wp-menu-image img{width:20px;height:20px;padding-top:7px;object-fit:contain;opacity:1}</style>';
}
add_action('admin_head', 'wp_mylinks_menu_icon_css');

/*
 * Metabox registration moved to includes/class-wp-mylinks-metaboxes.php in
 * 1.1.0 — the CMB2 definitions were ported verbatim to the native fields
 * framework (includes/fields/). Field ids and meta keys are unchanged.
 */

/**
 * Side meta box: QR code for this page's URL (F2, 1.1.0).
 *
 * Generated entirely in the browser by the bundled qrcode-generator library —
 * the URL never leaves the site, unlike QR web services.
 *
 * @since 1.1.0
 *
 * @param WP_Post $post Post being edited.
 */
function wp_mylinks_render_qr_meta_box( $post ) {
	if ( 'publish' !== $post->post_status ) {
		echo '<p class="wml-field__help">' . esc_html__( 'Publish this MyLink to generate its QR code.', 'wp-mylinks' ) . '</p>';
		return;
	}

	$permalink = get_permalink( $post );
	?>
	<div class="wml-qr" data-wml-qr-url="<?php echo esc_url( $permalink ); ?>">
		<div class="wml-qr__canvas"></div>
		<p class="wml-qr__url"><?php echo esc_html( $permalink ); ?></p>
		<button type="button" class="wml-button-secondary wml-qr__download" data-filename="<?php echo esc_attr( sanitize_title( $post->post_name ) . '-qr.png' ); ?>">
			<span class="dashicons dashicons-download" aria-hidden="true"></span>
			<?php esc_html_e( 'Download PNG', 'wp-mylinks' ); ?>
		</button>
	</div>
	<?php
}

/**
 * Custom side meta box: UTM Tag Generator quick links.
 *
 * @since 1.0.3
 */
function wp_mylinks_custom_sidebar_utm() {
	$en_url = function_exists('wpmylinks_url')
		? wpmylinks_url('https://walterpinem.me/projects/tools/utm-tag-builder/')
		: 'https://walterpinem.me/projects/tools/utm-tag-builder/';
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
function wp_mylinks_custom_sidebar_support() {
	$review_url = 'https://wordpress.org/support/plugin/wp-mylinks/reviews/?rate=5#new-post';
	$contact    = function_exists('wpmylinks_url')
		? wpmylinks_url('https://www.onlinestorekit.com/support/')
		: 'https://www.onlinestorekit.com/support/';
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
if ( ! function_exists('wp_mylinks_custom_sidebar_suppport') ) {
	function wp_mylinks_custom_sidebar_suppport() {
		wp_mylinks_custom_sidebar_support();
	}
}

/**
 * Register the side meta boxes on the mylink and mylinks-collection screens.
 */
function wp_mylinks_add_side_meta_box() {
	add_meta_box(
		'mylink-sidebar-qr',
		__('QR Code', 'wp-mylinks'),
		'wp_mylinks_render_qr_meta_box',
		'mylink',
		'side',
		'default',
		null
	);
	add_meta_box(
		'mylink-sidebar-utm',
		__('UTM Campaign Builder', 'wp-mylinks'),
		'wp_mylinks_custom_sidebar_utm',
		array( 'mylink', 'mylinks-collection' ),
		'side',
		'low',
		null
	);
	add_meta_box(
		'mylink-sidebar-support',
		__('Support', 'wp-mylinks'),
		'wp_mylinks_custom_sidebar_support',
		array( 'mylink', 'mylinks-collection' ),
		'side',
		'low',
		null
	);
}
add_action('add_meta_boxes', 'wp_mylinks_add_side_meta_box');
