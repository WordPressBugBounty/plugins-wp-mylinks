<?php

/**
 * Permalink / Rewrite handling for the MyLink post type.
 *
 * Goal: let mylink posts be served at /<post-name>/ (no /mylink/ prefix) without
 * breaking pages, posts, taxonomy archives, pagination, attachments, or feeds.
 *
 * Strategy:
 *   1. The CPT registers with a real slug ('mylink') — see class-wp-mylinks-post-type.php.
 *      This keeps WordPress's rewrite engine fully functional.
 *   2. We display permalinks as /<post-name>/ via the post_type_link filter.
 *   3. We resolve incoming /<post-name>/ requests via the 'request' filter,
 *      but ONLY when the slug doesn't match an existing page or post. This
 *      prevents collisions with the rest of the site.
 *   4. /mylink/<post-name>/ requests are 301-redirected to /<post-name>/ for
 *      canonical SEO consistency.
 *
 * @link       https://walterpinem.me/
 * @since      1.0.8
 *
 * @package    Wp_Mylinks
 * @subpackage Wp_Mylinks/includes
 * @author     Walter Pinem <hello@walterpinem.me>
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('Wp_Mylinks_Rewrites')) :

	final class Wp_Mylinks_Rewrites
	{

		/**
		 * The post type this rewriter targets.
		 *
		 * @var string
		 */
		const POST_TYPE = 'mylink';

		/**
		 * Singleton instance.
		 *
		 * @var Wp_Mylinks_Rewrites|null
		 */
		private static $instance = null;

		/**
		 * Get / create the single instance.
		 */
		public static function instance()
		{
			if (null === self::$instance) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Wire up filters and actions.
		 */
		private function __construct()
		{
			// Strip the post-type slug from generated permalinks.
			add_filter('post_type_link', array($this, 'strip_post_type_slug'), 10, 3);

			// Resolve slug-only URLs to mylink posts (with conflict avoidance).
			add_filter('request', array($this, 'resolve_request'));

			// Canonicalize: /mylink/foo/ -> /foo/ (301).
			add_action('template_redirect', array($this, 'canonical_redirect'), 1);

			// Make the admin "View" link and post-row links use the stripped URL.
			// (post_type_link covers most cases; this is belt-and-suspenders.)
			add_filter('preview_post_link', array($this, 'preview_post_link'), 10, 2);
		}

		/**
		 * Strip the post-type slug from public permalinks for mylink posts.
		 *
		 * Replaces the FIRST occurrence of /<slug>/ in the URL path only — uses a
		 * regex bound to the home URL so we never mangle a post slug that happens
		 * to contain the word "mylink".
		 *
		 * @param string  $post_link Generated permalink.
		 * @param WP_Post $post      Post object.
		 * @param bool    $leavename Whether to leave the post name placeholder.
		 * @return string
		 */
		public function strip_post_type_slug($post_link, $post, $leavename)
		{
			if (!($post instanceof WP_Post)) {
				return $post_link;
			}
			if (self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status) {
				return $post_link;
			}

			// Ask the registered post type for its actual rewrite slug; default to 'mylink'.
			$pto  = get_post_type_object(self::POST_TYPE);
			$slug = (isset($pto->rewrite['slug']) && is_string($pto->rewrite['slug']) && '' !== $pto->rewrite['slug'])
				? trim($pto->rewrite['slug'], '/')
				: self::POST_TYPE;

			if ('' === $slug) {
				return $post_link;
			}

			// Only strip the FIRST /<slug>/ segment in the path, anchored to home_url().
			$home = trailingslashit(home_url());
			$pattern = '#^(' . preg_quote($home, '#') . ')' . preg_quote($slug, '#') . '/#';
			$post_link = preg_replace($pattern, '$1', $post_link, 1);

			return $post_link;
		}

		/**
		 * Adjust preview links so the front-end preview matches the live URL.
		 */
		public function preview_post_link($preview_link, $post)
		{
			if ($post instanceof WP_Post && self::POST_TYPE === $post->post_type) {
				return $this->strip_post_type_slug($preview_link, $post, false);
			}
			return $preview_link;
		}

		/**
		 * Resolve a slug-only request into a mylink post.
		 *
		 * When a visitor hits /<name>/ and the request is being processed, WordPress
		 * normally sets query var 'name' (post) or 'pagename' (page). For pages,
		 * WP also sets 'page' (the [page] query var, used for paged content).
		 *
		 * We add 'mylink' (the post type) only when:
		 *   - The query is for a single slug (name or pagename + page combo), AND
		 *   - No published page or post with that slug already exists.
		 *
		 * If a page or post with the same slug exists, we let WordPress serve that
		 * one — the user can rename the mylink to avoid the collision.
		 *
		 * @param array $query_vars
		 * @return array
		 */
		public function resolve_request($query_vars)
		{
			// Skip admin and REST requests entirely.
			if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
				return $query_vars;
			}

			// Only act on bare slug requests.
			$slug = $this->extract_slug_from_query_vars($query_vars);
			if (null === $slug) {
				return $query_vars;
			}

			// If a published page or post owns this slug, defer to it.
			if ($this->slug_belongs_to_other_post_type($slug)) {
				return $query_vars;
			}

			// Confirm a published mylink with this slug actually exists before
			// rewriting query vars — avoids creating false 404s for unrelated 404s.
			if (!$this->mylink_exists_for_slug($slug)) {
				return $query_vars;
			}

			// Route this request to the mylink post type.
			// Use 'name' (single post lookup) and the post_type query var.
			unset($query_vars['pagename']);
			unset($query_vars['page']);
			$query_vars['name']      = $slug;
			$query_vars['post_type'] = self::POST_TYPE;

			return $query_vars;
		}

		/**
		 * Pull a candidate slug out of WP's parsed query vars, if this looks like
		 * a single-slug page request (e.g. /my-page/).
		 */
		private function extract_slug_from_query_vars(array $query_vars)
		{
			// Page-style URL: WP set 'pagename' for /my-page/.
			if (!empty($query_vars['pagename']) && is_string($query_vars['pagename'])) {
				$pagename = $query_vars['pagename'];
				// Reject nested paths (e.g. parent/child) — those are clearly pages.
				if (false === strpos($pagename, '/')) {
					return $pagename;
				}
				return null;
			}

			// Single-post-style URL: WP set 'name' for /my-page/ when no page matches.
			if (!empty($query_vars['name']) && is_string($query_vars['name'])) {
				return $query_vars['name'];
			}

			return null;
		}

		/**
		 * Does a published page or post already own this slug?
		 *
		 * We look up by exact post_name in 'page' and 'post' types only. This
		 * intentionally does NOT consider other CPTs — they have their own slugs.
		 *
		 * Result is cached per-request to keep this filter cheap.
		 */
		private function slug_belongs_to_other_post_type($slug)
		{
			static $cache = array();
			if (isset($cache[$slug])) {
				return $cache[$slug];
			}

			global $wpdb;
			// Direct query: WP_Query has no equivalent for "exists by slug AND
			// post_type IN (...) limited to 1 ID". Result is cached per-request
			// in the static $cache above so this fires at most once per slug
			// per request.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Per-request static cache; one row lookup is the cheapest path.
			$post_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts}
					 WHERE post_name = %s
					   AND post_status = 'publish'
					   AND post_type IN ('page', 'post')
					 LIMIT 1",
					$slug
				)
			);

			$cache[$slug] = !empty($post_id);
			return $cache[$slug];
		}

		/**
		 * Does a published mylink with this slug exist?
		 *
		 * Per-request cached to avoid repeat queries.
		 */
		private function mylink_exists_for_slug($slug)
		{
			static $cache = array();
			if (isset($cache[$slug])) {
				return $cache[$slug];
			}

			global $wpdb;
			// See slug_belongs_to_other_post_type() for the rationale on the
			// direct query. Same per-request cache pattern.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Per-request static cache; one row lookup is the cheapest path.
			$post_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts}
					 WHERE post_name = %s
					   AND post_status = 'publish'
					   AND post_type = %s
					 LIMIT 1",
					$slug,
					self::POST_TYPE
				)
			);

			$cache[$slug] = !empty($post_id);
			return $cache[$slug];
		}

		/**
		 * If the request is /<cpt-slug>/<name>/, redirect to /<name>/ for
		 * canonical SEO consistency. Only fires for actual, resolved mylink posts.
		 */
		public function canonical_redirect()
		{
			if (is_admin() || wp_doing_ajax()) {
				return;
			}

			global $wp;
			if (!isset($wp->request) || '' === $wp->request) {
				return;
			}

			$pto  = get_post_type_object(self::POST_TYPE);
			$slug = (isset($pto->rewrite['slug']) && is_string($pto->rewrite['slug']) && '' !== $pto->rewrite['slug'])
				? trim($pto->rewrite['slug'], '/')
				: self::POST_TYPE;

			$request = trim($wp->request, '/');
			$prefix  = $slug . '/';

			// Only redirect when the path starts with /<slug>/ AND something follows.
			if (0 !== strpos($request, $prefix)) {
				return;
			}
			$remainder = substr($request, strlen($prefix));
			if ('' === $remainder) {
				return;
			}

			// Only redirect single-slug requests; leave deeper paths alone.
			if (false !== strpos($remainder, '/')) {
				return;
			}

			$target = home_url('/' . $remainder . '/');

			// Preserve query string if any. We deliberately accept the raw query
			// here and re-parse it via wp_parse_args, then re-emit it through
			// add_query_arg which URL-encodes properly. wp_unslash is the
			// canonical treatment for $_SERVER values that are about to be
			// re-emitted, not stored.
			if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
				$qs = wp_unslash( $_SERVER['QUERY_STRING'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Re-parsed via wp_parse_args + re-encoded by add_query_arg below.
				if ( '' !== $qs ) {
					$target = add_query_arg( wp_parse_args( $qs ), $target );
				}
			}

			wp_safe_redirect($target, 301);
			exit;
		}

		/**
		 * Run a one-time rewrite-rules flush. Call this on activation and
		 * whenever the rewrite slug changes.
		 */
		public static function flush()
		{
			// Make sure the post type is registered before we flush.
			if (function_exists('wp_mylinks_register_post_type')) {
				wp_mylinks_register_post_type();
			}
			flush_rewrite_rules(false);
		}
	}

endif;

// Bootstrap.
Wp_Mylinks_Rewrites::instance();
