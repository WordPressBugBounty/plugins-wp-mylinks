/**
 * WP MyLinks — Public JavaScript
 *
 * Light-weight, click-to-load YouTube embed.
 * The tracking iframe is only injected when the user clicks the placeholder,
 * so no YouTube cookies are set on initial page load. The lightweight preview
 * thumbnail (i.ytimg.com) does load up front when a video link is present.
 *
 * Original concept by @labnol (https://www.labnol.org/).
 *
 * @package Wp_Mylinks
 * @since   1.0.0
 */
(function () {
	'use strict';

	var l10n = window.wpMylinksPublic || {};

	/**
	 * Validate a YouTube video ID.
	 *
	 * YouTube IDs are 11 characters long and use the URL-safe base64
	 * alphabet. Reject anything else to keep the iframe src trustworthy.
	 *
	 * @param {string} id
	 * @returns {boolean}
	 */
	function isValidVideoId(id) {
		return typeof id === 'string' && /^[A-Za-z0-9_-]{11}$/.test(id);
	}

	/**
	 * Replace a placeholder div with the actual YouTube iframe on click.
	 *
	 * @param {HTMLElement} placeholder
	 */
	function loadIframe(placeholder) {
		var videoId = placeholder.dataset.id;
		if (!isValidVideoId(videoId)) {
			return;
		}

		var iframe = document.createElement('iframe');
		iframe.setAttribute('src', 'https://www.youtube.com/embed/' + encodeURIComponent(videoId) + '?autoplay=1&rel=0');
		iframe.setAttribute('frameborder', '0');
		iframe.setAttribute('allowfullscreen', '1');
		iframe.setAttribute('allow', 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture');
		iframe.setAttribute('title', l10n.youtubePlayer || 'YouTube video player');

		if (placeholder.parentNode) {
			placeholder.parentNode.replaceChild(iframe, placeholder);
		}
	}

	/**
	 * Build the lazy-load placeholder for a single .youtube-player element.
	 *
	 * @param {HTMLElement} player
	 */
	function buildPlaceholder(player) {
		var videoId = player.dataset.id;
		if (!isValidVideoId(videoId)) {
			return;
		}

		var placeholder = document.createElement('div');
		placeholder.setAttribute('data-id', videoId);
		placeholder.setAttribute('role', 'button');
		placeholder.setAttribute('tabindex', '0');
		placeholder.setAttribute('aria-label', l10n.playVideo || 'Play video');

		var thumb = document.createElement('img');
		thumb.src = 'https://i.ytimg.com/vi/' + encodeURIComponent(videoId) + '/hqdefault.jpg';
		thumb.alt = '';
		thumb.loading = 'lazy';
		placeholder.appendChild(thumb);

		var play = document.createElement('div');
		play.className = 'play';
		placeholder.appendChild(play);

		// Click and keyboard activation.
		placeholder.addEventListener('click', function () {
			loadIframe(placeholder);
		});
		placeholder.addEventListener('keydown', function (event) {
			if (event.key === 'Enter' || event.key === ' ') {
				event.preventDefault();
				loadIframe(placeholder);
			}
		});

		player.appendChild(placeholder);
	}

	/**
	 * Initialize all .youtube-player elements on the page.
	 *
	 * Snapshots the live HTMLCollection into an array first so DOM mutations
	 * during iteration don't shift indexes.
	 */
	function init() {
		var players = document.getElementsByClassName('youtube-player');
		var snapshot = Array.prototype.slice.call(players);

		for (var i = 0; i < snapshot.length; i++) {
			buildPlaceholder(snapshot[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	/**
	 * Per-link click tracking (1.1.0).
	 *
	 * Sends a non-blocking beacon when a link button is clicked, so
	 * navigation is never delayed. The endpoint validates that the URL
	 * belongs to this page's stored links before counting.
	 */
	document.addEventListener('click', function (event) {
		var anchor = event.target.closest ? event.target.closest('a.link_count') : null;
		if (!anchor || !document.body.dataset) {
			return;
		}

		var postId = document.body.dataset.wmlPost;
		var ajaxUrl = document.body.dataset.wmlAjax;
		if (!postId || !ajaxUrl) {
			return;
		}

		var data = new FormData();
		data.append('action', 'wp_mylinks_link_click');
		data.append('post_id', postId);
		data.append('url', anchor.href);

		if (navigator.sendBeacon) {
			navigator.sendBeacon(ajaxUrl, data);
		} else if (window.fetch) {
			fetch(ajaxUrl, { method: 'POST', body: data, keepalive: true });
		}

		// Google Tag Manager / GA4 convenience (1.1.0): push a dataLayer event
		// when the site has GTM. No-op when window.dataLayer is absent, so this
		// never loads or requires anything.
		if (window.dataLayer && typeof window.dataLayer.push === 'function') {
			var label = anchor.textContent ? anchor.textContent.trim() : '';
			window.dataLayer.push({
				event: 'wp_mylinks_click',
				mylinks_post_id: postId,
				mylinks_link_url: anchor.href,
				mylinks_link_title: label
			});
		}
	});

	/**
	 * Search / filter bar (1.1.0).
	 *
	 * Client-side only: filters the rendered link rows by the visible text as
	 * the visitor types. Section headings hide when every link under them is
	 * filtered out. HTML blocks are left in place. No network, no dependency.
	 */
	(function () {
		var input = document.getElementById('mylinks-search-input');
		var list = document.getElementById('wp-mylinks-links');
		if (!input || !list) {
			return;
		}
		var rows = Array.prototype.slice.call(list.children);
		var empty = document.querySelector('.mylinks-search__empty');

		function normalize(value) {
			return (value || '').toLowerCase().trim();
		}

		function filter() {
			var query = normalize(input.value);
			var visibleLinks = 0;
			var pendingHeading = null;
			var headingHasMatch = false;

			rows.forEach(function (row) {
				var isHeading = row.classList.contains('link-heading');
				var isLink = row.querySelector && row.querySelector('a.link_count');

				if (isHeading) {
					// Resolve the previous heading's visibility before starting a new one.
					if (pendingHeading) {
						pendingHeading.hidden = query !== '' && !headingHasMatch;
					}
					pendingHeading = row;
					headingHasMatch = false;
					return;
				}

				if (!isLink) {
					// HTML blocks and anything else stay visible.
					row.hidden = false;
					return;
				}

				var text = normalize(row.textContent);
				var match = query === '' || text.indexOf(query) !== -1;
				row.hidden = !match;
				if (match) {
					visibleLinks++;
					headingHasMatch = true;
				}
			});

			if (pendingHeading) {
				pendingHeading.hidden = query !== '' && !headingHasMatch;
			}

			if (empty) {
				empty.hidden = !(query !== '' && visibleLinks === 0);
			}
		}

		input.addEventListener('input', filter);
	})();
})();
