<?php

/**
 * The base template for the MyLinks page.
 *
 * @link       https://walterpinem.me/
 * @since      1.0.0
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/public
 * @author     Walter Pinem <hello@walterpinem.me>
 */

global $post;
$allowed_tags = array('script' => array('type' => array(), 'src' => array(), 'async' => array(), 'defer' => array(), 'crossorigin' => array(), 'integrity' => array(),), 'noscript' => array(), 'div' => array('id' => array(), 'class' => array(),), 'span' => array('id' => array(), 'class' => array(),), 'a' => array('href' => array(), 'target' => array(), 'rel' => array(),), 'p' => array(), 'br' => array(), 'strong' => array(), 'em' => array(),);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<?php
	$active_plugins  = apply_filters('active_plugins', get_option('active_plugins'));
	$yoast_seo_active = in_array('wordpress-seo/wp-seo.php', $active_plugins) || in_array('wordpress-seo-premium/wp-seo-premium.php', $active_plugins);

	// Get Meta Title
	if ($yoast_seo_active) {
		$meta_title = get_post_meta($post->ID, '_yoast_wpseo_title', true);
	} else {
		$meta_title = get_post_meta(get_the_ID(), mylinks_prefix('meta-title'), true);
	}
	if (empty($meta_title)) {
		$meta_title = get_option('mylinks_meta_title');
	}
	if (empty($meta_title)) {
		$meta_title = get_the_title();
	}
	$meta_title = esc_html($meta_title);

	// Get Meta Description
	if ($yoast_seo_active) {
		$meta_description = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
	} else {
		$meta_description = get_post_meta(get_the_ID(), mylinks_prefix('meta-description'), true);
	}
	if (empty($meta_description)) {
		$meta_description = get_option('mylinks_meta_description');
	}
	if (empty($meta_description)) {
		$meta_description = '';
	}
	$meta_description = esc_attr($meta_description);

	// Get Indexing Options
	$set_noindex = get_post_meta(get_the_ID(), mylinks_prefix('noindex'), true);
	if (empty($set_noindex)) {
		$set_noindex = get_option('wp_mylinks_noindex');
	}
	$set_nofollow = get_post_meta(get_the_ID(), mylinks_prefix('nofollow'), true);
	if (empty($set_nofollow)) {
		$set_nofollow = get_option('wp_mylinks_nofollow');
	}

	$noindex  = ($set_noindex === "yes") ? "noindex" : "index";
	$nofollow = ($set_nofollow === "yes") ? "nofollow" : "follow";
	?>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Start Favicon -->
	<?php
	$single_favicon = get_post_meta(get_the_ID(), mylinks_prefix('single-favicon'), true);
	$global_favicon = get_option('mylinks_upload_favicon');
	if (empty($single_favicon)) :
	?>
		<link rel="icon" type="image/png" href="<?php echo esc_url($global_favicon); ?>">
	<?php else : ?>
		<link rel="icon" type="image/png" href="<?php echo esc_url($single_favicon); ?>">
	<?php endif; ?>
	<!-- End Favicon -->
	<title><?php echo esc_html($meta_title); ?></title>
	<meta name="description" content="<?php echo esc_attr($meta_description); ?>">
	<meta name="robots" content="<?php echo esc_attr($noindex); ?>, <?php echo esc_attr($nofollow); ?>, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
	<?php
	wp_enqueue_style('mylinks-public-css');
	wp_styles()->do_item('mylinks-public-css');
	wp_enqueue_style('mylinks-youtube-css');
	wp_styles()->do_item('mylinks-youtube-css');
	$play_icon_path = esc_url(plugins_url('/public/images/play.png', dirname(__DIR__)));
	echo '<style type="text/css">.youtube-player .play{ background: url(' . esc_url_raw($play_icon_path) . ') no-repeat;}</style>';

	// Analytics Script
	$analytics_script = get_option('wp_mylinks_analytics');
	if (!empty($analytics_script)) {
		echo wp_kses($analytics_script, $allowed_tags);
	}

	// Header Script
	$header_script = get_post_meta(get_the_ID(), mylinks_prefix('mylinks-single-custom-header-script'), true);
	if (empty($header_script)) {
		$header_script = get_option('wp_mylinks_header_script');
	}
	if (!empty($header_script)) {
		echo wp_kses($header_script, $allowed_tags);
	}

	// After Body Script
	$body_script = get_option('wp_mylinks_open_body_script');

	// Custom CSS
	$custom_css = get_post_meta(get_the_ID(), mylinks_prefix('mylinks-single-custom-styles'), true);
	if (empty($custom_css)) {
		$custom_css = get_option('wp_mylinks_custom_css');
	}
	if (!empty($custom_css)) {
		// Escape the custom CSS to ensure safe output
		echo '<style type="text/css">' . esc_html(wp_strip_all_tags($custom_css)) . '</style>';
	}

	// Avatar Style
	$avatar_style = get_post_meta(get_the_ID(), mylinks_prefix('avatar-style'), true);
	if ($avatar_style === "shadow") {
		echo '<style type="text/css">.mylinks .avatar{box-shadow:0 1px 2px rgba(0,0,0,.1),0 2px 4px rgba(0,0,0,.1),0 4px 8px rgba(0,0,0,.1),0 8px 16px rgba(0,0,0,.1),0 16px 32px rgba(0,0,0,.1),0 32px 64px rgba(0,0,0,.1)}</style>';
	} elseif ($avatar_style === "plain") {
		echo '<style type="text/css">.mylinks .avatar{background:transparent}</style>';
	} elseif ($avatar_style === "transparent") {
		echo '<style type="text/css">.mylinks .avatar,.mylinks-body .avatar img{background:transparent!important}</style>';
	} else {
		echo '<style type="text/css">.mylinks .avatar{background:#fdf497;background:radial-gradient(circle at 30% 107%,#fdf497 0,#fdf497 5%,#fd5949 45%,#d6249f 60%,#8a3fb6 90%)}</style>';
	}
	?>
</head>
<?php
$theme_options = get_option('mylinks_theme');
$theme_value   = $theme_options;
$metafield_id  = get_the_ID();
$options       = wp_mylinks_theme_callback();
$key           = get_post_meta($metafield_id, mylinks_prefix('theme'), true);
$option_name   = isset($options[$key]) ? $options[$key] : $options['default'];
// If in MyLinks post editor, None is selected
if ('none' === $key) {
	echo '<body class="mylinks-body ' . esc_attr($theme_value) . '">';
} else {
	echo '<body class="mylinks-body ' . esc_attr($key) . '">';
}
// Print the after body script
if (!empty($body_script)) {
	echo wp_kses($body_script, $allowed_tags);
}
?>
<?php if (have_posts()) : ?>
	<?php while (have_posts()) : the_post(); ?>
		<div class="mylinks">
			<div class="avatar">
				<?php
				// Grab the metadata from the database and define it
				$avatar = get_post_meta(get_the_ID(), mylinks_prefix('avatar'), true);
				$name   = get_post_meta(get_the_ID(), mylinks_prefix('name'), true);
				?>
				<?php if (!empty($avatar)) : ?>
					<img width="140" height="140" src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($name); ?>">
				<?php endif; ?>
			</div>
			<div class="name">
				<h1><?php echo esc_html($name); ?></h1>
			</div>
			<div class="description">
				<?php
				// Grab the description from the database
				$description = get_post_meta(get_the_ID(), mylinks_prefix('description'), true);
				// Echo the description
				echo wp_kses_post(wpautop($description));
				?>
			</div>
			<?php
			$top = get_post_meta(get_the_ID(), mylinks_prefix('social-media-position'), true);
			if ($top === "top") :
			?>
				<div class="user-profile">
					<?php
					// Get the social media data ready for display
					$social_platforms = ['facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'pinterest', 'tiktok', 'discord'];
					$social_data      = [];
					foreach ($social_platforms as $platform) {
						$social_data[$platform] = wp_mylinks_get_social_meta($platform);
					}
					foreach ($social_platforms as $platform) {
						$url  = esc_url($social_data[$platform][0]);
						$icon = esc_url($social_data[$platform][1]) ?: esc_url(plugins_url('/public/images/' . $platform . '.png', dirname(__DIR__)));
						if (!empty($url)) {
							echo '<a href="' . $url . '" target="_blank" class="user-profile-link" rel="noopener noreferrer nofollow">
								<img align="middle" class="mylinks-social-icons" width="32" height="32" src="' . $icon . '">
							</a>';
						}
					}
					?>
				</div>
			<?php endif; ?>
			<!-- End Top Social Media -->
			<div class="links">
				<?php
				// Now it's time to retrieve the saved data
				$links = get_post_meta(get_the_ID(), mylinks_prefix('links'), true);
				foreach ((array) $links as $key => $link) :
					$title       = isset($link['title']) ? $link['title'] : '';
					$url         = isset($link['url']) ? $link['url'] : '';
					$image       = isset($link['image']) ? $link['image'] : '';
					$youtube_url = isset($link['youtube-video']) ? $link['youtube-video'] : '';
					$embed       = isset($link['media-embed']) ? $link['media-embed'] : '';
					$card_layout = isset($link['card-layout']) && $link['card-layout'] === 'yes';

					if ($youtube_url && empty($embed)) :
						parse_str(wp_parse_url($youtube_url, PHP_URL_QUERY), $video_id);
				?>
						<div class="link youtube-embed">
							<div class="youtube-player" data-id="<?php echo esc_attr($video_id['v']); ?>"></div>
						</div>
					<?php elseif ($embed && empty($youtube_url)) : ?>
						<div class="link media-embed-wrapper">
							<div class="media-embed">
								<?php
								$cache_key = 'wp_mylinks_oembed_' . md5($embed);
								$embed_html = get_transient($cache_key);

								if (false === $embed_html) {
									$embed_html = wp_oembed_get($embed);
									if ($embed_html) {
										// Apply filters to the oEmbed result
										$embed_html = wp_filter_oembed_result($embed_html, $embed, array(), $post);
										set_transient($cache_key, $embed_html, DAY_IN_SECONDS); // Cache for 1 day
									}
								}

								if ($embed_html && !is_wp_error($embed_html)) {
									echo $embed_html;
								} else {
									if (is_wp_error($embed_html)) {
										echo esc_html__('Unable to embed the content. Reason: ', 'wp-mylinks') . esc_html($embed_html->get_error_message());
									} else {
										echo esc_html__('Unable to embed the content.', 'wp-mylinks');
									}
								}
								?>
							</div>
						</div>
					<?php elseif ($card_layout && $title && $url && $image && empty($youtube_url) && empty($embed)) : ?>
						<div class="card-wrapper">
							<div class="mylink-card">
								<a id="link_count" class="mylink-card-link" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
									<img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" class="mylink-card-background">
									<div class="mylink-card-title-wrapper">
										<h2 class="mylink-card-title"><?php echo esc_html($title); ?></h2>
									</div>
								</a>
							</div>
						</div>
					<?php elseif (empty($image) && !empty($url) && !empty($title)) : ?>
						<div class="link">
							<a id="link_count" class="button link-without-image inline-photo show-on-scroll" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
								<span class="link-text"><?php echo esc_html($title); ?></span>
							</a>
						</div>
					<?php elseif (!empty($image) && !empty($url) && !empty($title)) : ?>
						<div class="link">
							<a id="link_count" class="button link-with-image inline-photo show-on-scroll" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
								<div class="thumbnail-wrap">
									<img src="<?php echo esc_url($image); ?>" class="link-image" alt="thumbnail">
								</div>
								<span class="link-text"><?php echo esc_html($title); ?></span>
							</a>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php wp_mylinks_track_mylink_page(get_the_ID()); ?>
	<?php endwhile; ?>
<?php endif; ?>
<?php
$bottom = get_post_meta(get_the_ID(), mylinks_prefix('social-media-position'), true);
if ($bottom === "bottom") :
?>
	<div class="user-profile">
		<?php
		// Get the social media data ready for display
		$social_platforms = ['facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'pinterest', 'tiktok', 'discord'];
		$social_data      = [];
		foreach ($social_platforms as $platform) {
			$social_data[$platform] = wp_mylinks_get_social_meta($platform);
		}
		foreach ($social_platforms as $platform) {
			$url  = esc_url($social_data[$platform][0]);
			$icon = esc_url($social_data[$platform][1]) ?: esc_url(plugins_url('/public/images/' . $platform . '.png', dirname(__DIR__)));
			if (!empty($url)) {
				echo '<a href="' . $url . '" target="_blank" class="user-profile-link" rel="noopener noreferrer nofollow">
					<img align="middle" class="mylinks-social-icons" width="32" height="32" src="' . $icon . '">
				</a>';
			}
		}
		?>
	</div>
<?php endif; ?>
<!-- End Bottom Social Media -->
<footer id="site-footer" role="contentinfo" class="mylinks-footer">
	<?php
	$enable_credits = get_option('wp_mylinks_credits');
	if ($enable_credits === 'yes') {
		echo '<div class="wp-mylinks-credits">
			Made with ❤️ and ☕ by <a href="https://walterpinem.me/" target="_blank" rel="noopener nofollow"><strong>Walter Pinem</strong></a></div>';
	}

	$footer_script = get_post_meta(get_the_ID(), mylinks_prefix('mylinks-single-custom-footer-script'), true);
	if (empty($footer_script)) {
		$footer_script = get_option('wp_mylinks_footer_script');
	}
	if (!empty($footer_script)) {
		echo wp_kses($footer_script, $allowed_tags);
	}
	?>
</footer>
<?php
wp_enqueue_script('mylinks-public-js');
wp_scripts()->do_item('mylinks-public-js');
?>
</body>

</html>