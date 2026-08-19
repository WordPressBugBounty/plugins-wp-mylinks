/**
 * WP MyLinks — Admin JavaScript
 *
 * Wires up WordPress media library uploaders for the global Favicon and the
 * global Open Graph share image inputs on Settings → MyLinks → Global.
 *
 * @package Wp_Mylinks
 * @since   1.0.0
 */
(function ($) {
	'use strict';

	/**
	 * Open the WP media frame and write the chosen URL into the target input.
	 *
	 * Each (button, input) pair gets its own media frame so picking a favicon
	 * doesn't accidentally overwrite the og:image and vice versa.
	 *
	 * @param {jQuery} $button Button being clicked.
	 * @param {string} title   Title shown on the modal.
	 * @param {string} target  Selector for the input that receives the URL.
	 */
	function openMediaFrame($button, title, target) {
		if (typeof wp === 'undefined' || typeof wp.media !== 'function') {
			return;
		}

		var frameKey = 'mylinks_media_frame_' + target.replace(/[^a-z0-9_]/gi, '');
		var frame    = $button.data(frameKey);

		if (frame) {
			frame.open();
			return;
		}

		frame = wp.media({
			title:    title,
			button:   { text: title },
			multiple: false
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			if (attachment && attachment.url) {
				$(target).val(attachment.url);
			}
		});

		$button.data(frameKey, frame);
		frame.open();
	}

	var l10n = window.wpMylinksAdmin || {};

	$(function () {
		// Original favicon uploader (unchanged contract).
		$('#upload_image_button').on('click', function (event) {
			event.preventDefault();
			openMediaFrame($(this), l10n.chooseFavicon || 'Choose Favicon', '#mylinks_upload_favicon');
		});

		// New og:image uploader (1.0.8+).
		$('#wp_mylinks_og_image_button').on('click', function (event) {
			event.preventDefault();
			openMediaFrame($(this), l10n.chooseShareImage || 'Choose Share Image', '#wp_mylinks_og_image');
		});
	});
})(jQuery);
