/**
 * WP MyLinks native fields framework — admin behavior.
 *
 * Media picker, sortable repeater groups, Select2 pickers with the
 * Collection URL auto-fill, and lazy oEmbed previews. Replaces the
 * CMB2-era wp-mylinks-select.js wiring.
 *
 * @package Wp_Mylinks
 * @since 1.1.0
 */

(function ($) {
	'use strict';

	var settings = window.wmlFields || {};

	// -------------------------------------------------------------------
	// Media picker (.wml-file)
	// -------------------------------------------------------------------

	$(document).on('click', '.wml-file__choose', function (e) {
		e.preventDefault();
		if (!window.wp || !wp.media) {
			return;
		}
		var $wrap = $(this).closest('.wml-file');
		var type = $wrap.data('media-type');

		var frame = wp.media({
			title: settings.mediaTitle || '',
			button: { text: settings.mediaButton || '' },
			library: type ? { type: type } : {},
			multiple: false
		});

		frame.on('select', function () {
			var att = frame.state().get('selection').first().toJSON();
			var thumb = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
			$wrap.find('.wml-file__url').val(att.url).trigger('change');
			$wrap.find('.wml-file__id').val(att.id);
			$wrap.find('.wml-file__preview').html(
				$('<img>', { src: thumb, alt: '' }).css({ 'max-width': '150px', height: 'auto' })
			);
			$wrap.find('.wml-file__remove').show();
		});

		frame.open();
	});

	$(document).on('click', '.wml-file__remove', function (e) {
		e.preventDefault();
		var $wrap = $(this).closest('.wml-file');
		$wrap.find('.wml-file__url').val('').trigger('change');
		$wrap.find('.wml-file__id').val('');
		$wrap.find('.wml-file__preview').empty();
		$(this).hide();
	});

	// A hand-typed URL invalidates the picked attachment id.
	$(document).on('input', '.wml-file__url', function () {
		$(this).closest('.wml-file').find('.wml-file__id').val('');
	});

	// -------------------------------------------------------------------
	// Select2 (.wml-select2) + Collection URL auto-fill
	// -------------------------------------------------------------------

	function initSelect2($scope) {
		if (!$.fn.select2) {
			return;
		}
		$scope.find('.wml-select2').each(function () {
			if ($(this).data('select2')) {
				return;
			}
			var $el = $(this);
			var config = { allowClear: true, placeholder: $el.attr('placeholder') || '' };

			// The Link URL picker searches server-side (data-wml-ajax) so any
			// post type enabled on the Tools tab is reachable, not just the
			// preloaded page. The preloaded <option>s stay as the no-JS/initial
			// set until a search runs.
			if ($el.attr('data-wml-ajax') === 'link-search') {
				config.minimumInputLength = 0;
				config.ajax = {
					url: settings.ajaxUrl,
					dataType: 'json',
					delay: 250,
					cache: true,
					data: function (params) {
						return {
							action: 'wp_mylinks_link_search',
							nonce: settings.nonce,
							q: params.term || ''
						};
					},
					processResults: function (response) {
						return {
							results: (response && response.success && response.data && response.data.results) || []
						};
					}
				};
			}

			$el.select2(config);
		});
	}

	function destroySelect2($scope) {
		if (!$.fn.select2) {
			return;
		}
		$scope.find('.wml-select2').each(function () {
			if ($(this).data('select2')) {
				$(this).select2('destroy');
			}
		});
	}

	// Picking a Collection/post fills the sibling manual-URL input from the
	// option label ("Title - URL"), same contract as the CMB2-era JS.
	$(document).on('change', '.wml-select2', function () {
		var text = $(this).find('option:selected').text() || '';
		var parts = text.split(' - ');
		if (parts.length < 2) {
			return;
		}
		$(this)
			.closest('.wml-group__row-body, .wml-fields')
			.find('.mylinks-selected-url')
			.first()
			.val(parts.slice(1).join(' - '));
	});

	// -------------------------------------------------------------------
	// oEmbed previews (.wml-oembed__url)
	// -------------------------------------------------------------------

	var oembedTimers = {};

	function loadOembedPreview($input) {
		var url = $.trim($input.val());
		var $preview = $input.closest('.wml-field').find('.wml-oembed__preview');
		if (!url) {
			$preview.empty();
			return;
		}
		$.post(settings.ajaxUrl, {
			action: 'wp_mylinks_oembed_preview',
			nonce: settings.nonce,
			url: url
		}).done(function (response) {
			if (response && response.success && response.data && response.data.html) {
				$preview.html(response.data.html);
			} else {
				$preview.text((response && response.data && response.data.message) || '');
			}
		});
	}

	$(document).on('input change', '.wml-oembed__url', function () {
		var input = this;
		var key = $(input).attr('name') || Math.random();
		window.clearTimeout(oembedTimers[key]);
		oembedTimers[key] = window.setTimeout(function () {
			loadOembedPreview($(input));
		}, 600);
	});

	// -------------------------------------------------------------------
	// Repeater groups (.wml-group)
	// -------------------------------------------------------------------

	function renumberRows($group) {
		var titleTemplate = $group.data('title-template') || '{#}';
		$group.find('> .wml-group__rows > .wml-group__row').each(function (i) {
			$(this).find('.wml-group__title').first().text(titleTemplate.replace('{#}', i + 1));
			// Rewrite "group[old][sub]" name indexes to the new position.
			$(this).find('[name]').each(function () {
				var name = $(this).attr('name');
				var groupId = $group.data('group-id');
				if (name && name.indexOf(groupId + '[') === 0) {
					$(this).attr('name', name.replace(/\[\d+\]/, '[' + i + ']'));
				}
			});
		});
	}

	$(document).on('click', '.wml-group__add', function (e) {
		e.preventDefault();
		var $group = $(this).closest('.wml-group');
		var template = $group.find('> .wml-group__row-template').html() || '';
		var index = $group.find('> .wml-group__rows > .wml-group__row').length;
		var html = template
			.split('__wmlidx__').join(index)
			.split('__wmlnum__').join(index + 1);
		var $row = $(html).appendTo($group.find('> .wml-group__rows'));
		initSelect2($row);
		applyRowConditions($row);
	});

	// -------------------------------------------------------------------
	// Conditional fields in the Links repeater: show only the fields that
	// apply to the chosen Row Type, and hide the video/embed fields when a
	// link uses Card Layout. Values of hidden fields are kept; the frontend
	// renders strictly by Row Type, so a stray value is never output.
	// -------------------------------------------------------------------
	function subField($row, id) {
		return $row.find('> .wml-group__row-body > [data-wml-field="' + id + '"]');
	}

	function applyRowConditions($row) {
		var $rowType = subField($row, 'row-type');
		if (!$rowType.length) {
			return; // Not a Links row (other repeaters have no row-type).
		}
		var type = $rowType.find('input:checked').val() || 'link';
		var isLink = type === 'link';
		var isHeading = type === 'heading';
		var isHtml = type === 'html';
		var cardYes = isLink && subField($row, 'card-layout').find('input:checked').val() === 'yes';

		function show(id, on) {
			subField($row, id).toggle(!!on);
		}

		show('title', isLink || isHeading);
		show('html-block', isHtml);
		show('select_url', isLink);
		show('url', isLink);
		show('image', isLink);
		show('card-layout', isLink);
		show('youtube-video', isLink && !cardYes);
		show('media-embed', isLink && !cardYes);
	}

	$(document).on('change', '[data-wml-field="row-type"] input, [data-wml-field="card-layout"] input', function () {
		applyRowConditions($(this).closest('.wml-group__row'));
	});

	$(document).on('click', '.wml-group__collapse', function (e) {
		e.preventDefault();
		var $row = $(this).closest('.wml-group__row');
		var collapsed = $row.toggleClass('is-collapsed').hasClass('is-collapsed');
		$(this).attr('aria-expanded', collapsed ? 'false' : 'true');
	});

	$(document).on('click', '.wml-group__row-remove', function (e) {
		e.preventDefault();
		var $group = $(this).closest('.wml-group');
		var confirmText = $group.data('remove-confirm');
		if (confirmText && !window.confirm(confirmText)) {
			return;
		}
		var $rows = $group.find('> .wml-group__rows > .wml-group__row');
		if ($rows.length <= 1) {
			// Keep one row; just blank it out.
			var $last = $rows.first();
			$last.find('input[type="text"], input[type="hidden"], textarea').val('');
			$last.find('select').val('').trigger('change');
			$last.find('.wml-file__preview').empty();
			$last.find('.wml-file__remove').hide();
			return;
		}
		$(this).closest('.wml-group__row').remove();
		renumberRows($group);
	});

	function initSortable($group) {
		if ($group.data('sortable') !== 1 || !$.fn.sortable) {
			return;
		}
		$group.find('> .wml-group__rows').sortable({
			handle: '.wml-group__handle',
			axis: 'y',
			opacity: 0.8,
			start: function () {
				destroySelect2($group);
			},
			stop: function () {
				renumberRows($group);
				initSelect2($group);
			}
		});
	}

	// -------------------------------------------------------------------
	// Boot
	// -------------------------------------------------------------------

	// -------------------------------------------------------------------
	// QR code side box (.wml-qr) — rendered locally by qrcode-generator.
	// -------------------------------------------------------------------

	function initQr() {
		var $wrap = $('.wml-qr');
		if (!$wrap.length || typeof window.qrcode !== 'function') {
			return;
		}
		var url = $wrap.data('wml-qr-url');
		if (!url) {
			return;
		}

		// Type 0 auto-sizes to the data; M error correction is the QR default.
		var qr = window.qrcode(0, 'M');
		qr.addData(url);
		qr.make();
		// Sizing lives in wml-components.css (.wml-qr__canvas img).
		$wrap.find('.wml-qr__canvas').html(qr.createImgTag(6, 12));

		$wrap.on('click', '.wml-qr__download', function () {
			var img = $wrap.find('.wml-qr__canvas img')[0];
			if (!img) {
				return;
			}
			var canvas = document.createElement('canvas');
			canvas.width = img.naturalWidth;
			canvas.height = img.naturalHeight;
			canvas.getContext('2d').drawImage(img, 0, 0);

			var link = document.createElement('a');
			link.download = $(this).data('filename') || 'mylink-qr.png';
			link.href = canvas.toDataURL('image/png');
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
		});
	}

	// -------------------------------------------------------------------
	// Import/Export: editor side box + Tools-tab single-page export nonce.
	// -------------------------------------------------------------------

	function initImportExport() {
		// Show the picked filename inside the styled drop zone.
		$(document).on('change', '.wml-file-drop input[type="file"]', function () {
			var $drop = $(this).closest('.wml-file-drop');
			var file = this.files[0];
			if (file) {
				$drop.addClass('wml-file-drop--has-file');
				$drop.find('.wml-file-drop__text strong').text(file.name);
				$drop.find('.wml-file-drop__text small').text(wmlFields.importReady);
			}
		});

		// The single-page export nonce is per-post, carried on each <option>.
		$('.wml-export-single').closest('form').on('submit', function () {
			var $form = $(this);
			var nonce = $form.find('.wml-export-single option:selected').data('nonce') || '';
			$form.find('input[name="_wpnonce"]').remove();
			$('<input>', { type: 'hidden', name: '_wpnonce', value: nonce }).appendTo($form);
		});

		// Editor side box: upload a single-page export into the current page.
		$('.wml-import-export').each(function () {
			var $box = $(this);
			$box.on('click', '.wml-import-export__import', function () {
				var file = $box.find('.wml-import-export__file')[0].files[0];
				var $status = $box.find('.wml-import-export__status');
				if (!file) {
					$status.text(wmlFields.importPickFile);
					return;
				}
				var data = new FormData();
				data.append('action', 'wp_mylinks_import_page');
				data.append('post_id', $box.data('wml-post-id'));
				data.append('_ajax_nonce', $box.data('wml-nonce'));
				data.append('wp_mylinks_import_file', file);
				$status.text(wmlFields.importWorking);
				$.ajax({
					url: wmlFields.ajaxUrl,
					method: 'POST',
					data: data,
					processData: false,
					contentType: false
				}).done(function (response) {
					if (response && response.success) {
						window.location.reload();
					} else {
						$status.text((response && response.data && response.data.message) || wmlFields.importFailed);
					}
				}).fail(function (xhr) {
					var message = wmlFields.importFailed;
					if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						message = xhr.responseJSON.data.message;
					}
					$status.text(message);
				});
			});
		});
	}

	$(function () {
		if ($.fn.wpColorPicker) {
			$('.wml-color').wpColorPicker();
		}
		initQr();
		initImportExport();
		initSelect2($(document));
		$('.wml-group').each(function () {
			initSortable($(this));
		});
		$('.wml-group__row').each(function () {
			applyRowConditions($(this));
		});
		$('.wml-oembed__url').each(function () {
			if ($.trim($(this).val())) {
				loadOembedPreview($(this));
			}
		});
	});
})(jQuery);
