/**
 * WP MyLinks — Public JavaScript
 *
 * Light-weight, click-to-load YouTube embed.
 * Iframe is only injected when the user clicks the placeholder, so no
 * tracking cookies or third-party requests fire on initial page load.
 *
 * Original concept by @labnol — https://www.labnol.org/
 *
 * @package Wp_Mylinks
 * @since   1.0.0
 */
(function () {
	'use strict';

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
		iframe.setAttribute('title', 'YouTube video player');

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
		placeholder.setAttribute('aria-label', 'Play video');

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
})();
