<?php
/**
 * The More Plugins settings tab.
 *
 * Cross-promotion for the rest of the Online Store Kit range, ported from the
 * house reference implementation (Indonesian Banks for WooCommerce). Kept to
 * one tab the admin chooses to open — no dashboard widgets, no notices,
 * nothing on anyone's storefront. Each card reflects what the site already
 * has: a plugin that is installed offers to activate rather than to download,
 * and one already running says so instead of selling itself again.
 *
 * Registered through the public `wp_mylinks_settings_tabs` filter — the same
 * surface Pro and third parties use — so the extension API stays exercised by
 * the plugin itself.
 *
 * @package    Wp_Mylinks
 * @since      1.1.0
 * @link       https://walterpinem.me/
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Cross-promotion tab: registration, plugin list, and rendering.
 *
 * @since 1.1.0
 */
class Wp_Mylinks_More_Plugins {

	/**
	 * Tab slug (sanitize_key form, used in ?tab=).
	 */
	const TAB = 'more_plugins';

	/**
	 * Hook the tab in.
	 *
	 * @since 1.1.0
	 */
	public static function init() {
		add_filter('wp_mylinks_settings_tabs', array( __CLASS__, 'register_tab' ));
	}

	/**
	 * Append the tab last, after Support.
	 *
	 * Cross-promotion is deliberately the least prominent tab — Support
	 * matters more to the person opening the settings (owner decision,
	 * Aug 2026, overriding the usual Support-stays-last convention).
	 *
	 * @since 1.1.0
	 *
	 * @param array<string,array> $tabs Tab definitions.
	 * @return array<string,array>
	 */
	public static function register_tab( $tabs ) {
		$tabs = (array) $tabs;

		$tabs[ self::TAB ] = array( 'megaphone', __('More Plugins', 'wp-mylinks'), array( __CLASS__, 'render' ) );

		return $tabs;
	}

	/**
	 * The range.
	 *
	 * Keys per entry:
	 *   name        (string) Plugin name.
	 *   description (string) One line, in the plugin's own words.
	 *   icon        (string) File in admin/images/.
	 *   slug        (string) WordPress.org slug, and the install directory name.
	 *   wporg       (string) WordPress.org listing, if it is released there.
	 *   landing     (string) Online Store Kit page, tracked.
	 *   product     (string) Online Store Kit product page, tracked.
	 *   soon        (bool)   Built but not yet launched; its pages are placeholders.
	 *
	 * @since 1.1.0
	 *
	 * @return array<int, array>
	 */
	public static function plugins() {
		$plugins = array(
			array(
				'name'        => 'OneClick Chat to Order',
				'description' => __('Transform your WooCommerce store with seamless WhatsApp integration. Enable customers to order products instantly via WhatsApp with enhanced features.', 'wp-mylinks'),
				'icon'        => 'oneclick-chat-to-order.png',
				'slug'        => 'oneclick-whatsapp-order',
				'wporg'       => 'https://wordpress.org/plugins/oneclick-whatsapp-order/',
				'landing'     => 'https://www.onlinestorekit.com/oneclick-chat-to-order/',
			),
			array(
				'name'        => 'OneClick WP Hello',
				'description' => __('Make your audience contact you directly and easily on WhatsApp with a single click.', 'wp-mylinks'),
				'icon'        => 'oneclick-whatsapp-hello.png',
				'slug'        => 'oneclick-whatsapp-hello',
				'wporg'       => 'https://wordpress.org/plugins/oneclick-whatsapp-hello/',
			),
			array(
				'name'        => 'Indonesian Banks for WooCommerce',
				'description' => __('Manual bank transfer payment methods for Indonesian banks and e-wallets. Zero transaction fees, money straight into your own account, live in five minutes — no gateway registration required.', 'wp-mylinks'),
				'icon'        => 'indonesian-banks.png',
				'slug'        => 'indonesian-banks-for-woocommerce-free-version',
				'wporg'       => 'https://wordpress.org/plugins/indonesian-banks-for-woocommerce-free-version/',
				'landing'     => 'https://www.onlinestorekit.com/indonesian-banks-for-woocommerce/',
			),
			array(
				'name'        => 'External Marketplace Buttons',
				'description' => __('Display customizable external marketplace buttons on WooCommerce product pages. Add URLs to Amazon, eBay, Shopee, Tokopedia, and 15+ other marketplaces.', 'wp-mylinks'),
				'icon'        => 'external-marketplace-buttons.png',
				'slug'        => 'external-marketplace-buttons',
				'landing'     => 'https://www.onlinestorekit.com/external-marketplace-buttons/',
				'product'     => 'https://www.onlinestorekit.com/kit/external-marketplace-buttons/',
			),
			array(
				'name'        => 'PixMapr',
				'description' => __('Interactive image hotspots for WordPress. Draw polygon, circle and rectangle regions over any image and attach content, links or WooCommerce products to them.', 'wp-mylinks'),
				'icon'        => 'pixmapr.png',
				'slug'        => 'pixmapr',
				'landing'     => 'https://www.onlinestorekit.com/pixmapr/',
				'product'     => 'https://www.onlinestorekit.com/kit/pixmapr/',
				'soon'        => true,
			),
			array(
				'name'        => 'PixSwipr',
				'description' => __('Transform the way you showcase visual progress with stunning before-after image comparison sliders.', 'wp-mylinks'),
				'icon'        => 'pixswipr.png',
				'slug'        => 'pixswipr',
				'landing'     => 'https://www.onlinestorekit.com/pixswipr/',
				'product'     => 'https://www.onlinestorekit.com/kit/pixswipr/',
				'soon'        => true,
			),
			array(
				'name'        => 'Aerys Smart Links',
				'description' => __('AI-powered contextual internal linking, auto-linking, and GSC-driven anchor optimization for WordPress.', 'wp-mylinks'),
				'icon'        => 'aerys-smart-links.png',
				'slug'        => 'aerys-smart-links',
				'landing'     => 'https://www.onlinestorekit.com/aerys-smart-links/',
				'product'     => 'https://www.onlinestorekit.com/kit/aerys-smart-links/',
				'soon'        => true,
			),
		);

		/**
		 * Filter the cross-promoted plugin list.
		 *
		 * @since 1.1.0
		 * @param array $plugins Plugin definitions.
		 */
		return apply_filters('wp_mylinks_more_plugins', $plugins);
	}

	/**
	 * Find an installed plugin by its directory name.
	 *
	 * Matching on the directory rather than a hard-coded main file means a
	 * plugin that renames its bootstrap still resolves.
	 *
	 * @since 1.1.0
	 *
	 * @param string $slug Plugin directory name.
	 * @return string Plugin basename, or '' when not installed.
	 */
	protected static function installed_file( $slug ) {
		static $installed = null;

		if ( null === $installed ) {
			if ( ! function_exists('get_plugins') ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$installed = array();
			foreach ( array_keys(get_plugins()) as $file ) {
				$dir = dirname($file);
				if ( '.' !== $dir && ! isset($installed[ $dir ]) ) {
					$installed[ $dir ] = $file;
				}
			}
		}

		return isset($installed[ $slug ]) ? $installed[ $slug ] : '';
	}

	/**
	 * Render the tab.
	 *
	 * @since 1.1.0
	 */
	public static function render() {
		?>
		<div class="wml-settings-card wml-more-intro">
			<div class="wml-card-body">
				<h2><?php esc_html_e('More from Online Store Kit', 'wp-mylinks'); ?></h2>
				<p class="wml-card-description">
					<?php esc_html_e('Other plugins built by the same author, for stores and sites that need a little more.', 'wp-mylinks'); ?>
				</p>
			</div>
		</div>

		<div class="wml-plugin-grid">
			<?php foreach ( self::plugins() as $plugin ) : ?>
				<?php self::render_card($plugin); ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * One plugin card.
	 *
	 * @since 1.1.0
	 *
	 * @param array $plugin Definition from plugins().
	 */
	protected static function render_card( array $plugin ) {
		$plugin = wp_parse_args(
			$plugin,
			array(
				'name'        => '',
				'description' => '',
				'icon'        => '',
				'slug'        => '',
				'wporg'       => '',
				'landing'     => '',
				'product'     => '',
				'soon'        => false,
			)
		);

		$file      = $plugin['slug'] ? self::installed_file($plugin['slug']) : '';
		$is_active = $file && is_plugin_active($file);

		// Own-property pages carry the house tracking parameters.
		$landing = $plugin['landing'] ? wpmylinks_url($plugin['landing']) : '';
		$product = $plugin['product'] ? wpmylinks_url($plugin['product']) : '';
		?>
		<div class="wml-plugin">
			<div class="wml-plugin__head">
				<?php if ( $plugin['icon'] ) : ?>
					<img
						class="wml-plugin__icon"
						src="<?php echo esc_url(WP_MYLINKS_URL . 'admin/images/' . $plugin['icon']); ?>"
						alt=""
						width="256"
						height="256"
					/>
				<?php endif; ?>
				<div class="wml-plugin__title">
					<h3><?php echo esc_html($plugin['name']); ?></h3>
					<?php if ( $is_active ) : ?>
						<span class="wml-pill wml-pill--on"><?php esc_html_e('Active', 'wp-mylinks'); ?></span>
					<?php elseif ( $file ) : ?>
						<span class="wml-pill"><?php esc_html_e('Installed', 'wp-mylinks'); ?></span>
					<?php elseif ( $plugin['soon'] ) : ?>
						<span class="wml-pill wml-pill--brand"><?php esc_html_e('Coming soon', 'wp-mylinks'); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<p class="wml-plugin__description"><?php echo esc_html($plugin['description']); ?></p>

			<div class="wml-plugin__actions">
				<?php if ( $is_active ) : ?>
					<span class="wml-plugin__running">
						<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
						<?php esc_html_e('Running on this site', 'wp-mylinks'); ?>
					</span>
				<?php elseif ( $file && current_user_can('activate_plugins') ) : ?>
					<a class="wml-button-primary" href="<?php echo esc_url(self::activate_url($file)); ?>">
						<?php esc_html_e('Activate', 'wp-mylinks'); ?>
					</a>
				<?php elseif ( $plugin['wporg'] ) : ?>
					<a class="wml-button-primary" href="<?php echo esc_url($plugin['wporg']); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e('Get plugin', 'wp-mylinks'); ?>
						<span class="dashicons dashicons-external" aria-hidden="true"></span>
					</a>
				<?php elseif ( $product ) : ?>
					<a class="wml-button-primary" href="<?php echo esc_url($product); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e('Get plugin', 'wp-mylinks'); ?>
						<span class="dashicons dashicons-external" aria-hidden="true"></span>
					</a>
				<?php endif; ?>

				<?php if ( $landing ) : ?>
					<a class="wml-button-secondary" href="<?php echo esc_url($landing); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e('Learn more', 'wp-mylinks'); ?>
					</a>
				<?php elseif ( $plugin['wporg'] && ( $is_active || $file ) ) : ?>
					<a class="wml-button-secondary" href="<?php echo esc_url($plugin['wporg']); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e('Learn more', 'wp-mylinks'); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Nonced activation link for an installed plugin.
	 *
	 * @since 1.1.0
	 *
	 * @param string $file Plugin basename.
	 * @return string
	 */
	protected static function activate_url( $file ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'activate',
					'plugin' => rawurlencode($file),
				),
				admin_url('plugins.php')
			),
			'activate-plugin_' . $file
		);
	}
}

Wp_Mylinks_More_Plugins::init();
