
jQuery(document).ready(function($) {
	$('form.confirm-form').hide();
	$('form.cf-contact-form').hide();

	var lightboxItems = [];
	var activeLightboxIndex = 0;
	var frontendConfig = window.cfFrontend || { ajaxUrl: '', nonce: '', i18n: {} };
	var lastQuickViewTrigger = null;
	var savedFiltersKey = 'cfSavedFilters';

	function getSavedFilters() {
		try {
			var parsed = JSON.parse(window.localStorage.getItem(savedFiltersKey) || '[]');
			return Array.isArray(parsed) ? parsed : [];
		} catch (e) {
			return [];
		}
	}

	function setSavedFilters(filters) {
		window.localStorage.setItem(savedFiltersKey, JSON.stringify(filters));
	}

	function getCurrentFilterPayload() {
		var $form = $('.cf-filter-bar');
		if (!$form.length) {
			return null;
		}

		return {
			q: $form.find('[name="q"]').val() || '',
			cat: $form.find('[name="cat"]').val() || '',
			region: $form.find('[name="region"]').val() || '',
			min_price: $form.find('[name="min_price"]').val() || '',
			max_price: $form.find('[name="max_price"]').val() || '',
			sort: $form.find('[name="sort"]').val() || 'newest'
		};
	}

	function applyFilterPayload(payload) {
		var $form = $('.cf-filter-bar');
		if (!$form.length || !payload) {
			return;
		}

		$.each(payload, function(key, value) {
			$form.find('[name="' + key + '"]').val(value);
		});
	}

	function renderSavedFilterOptions() {
		var filters = getSavedFilters();
		var $select = $('.cf-saved-filter-select');
		if (!$select.length) {
			return;
		}

		$select.each(function() {
			var $this = $(this);
			var current = $this.val();
			$this.html('<option value="">' + (frontendConfig.i18n.savedFilterDefault || 'Gespeicherten Filter laden') + '</option>');
			$.each(filters, function(_, filterEntry) {
				$this.append($('<option />').val(filterEntry.id).text(filterEntry.name));
			});
			$this.val(current);
		});
	}

	function syncFavoriteButtons(postId, active) {
		$('.cf-favorite-toggle[data-post-id="' + postId + '"]').toggleClass('is-active', !!active);
	}

	function closeQuickView() {
		$('#cf-quickview-modal').removeClass('is-open').attr('aria-hidden', 'true');
		$('body').removeClass('cf-modal-open');
		if (lastQuickViewTrigger) {
			lastQuickViewTrigger.trigger('focus');
			lastQuickViewTrigger = null;
		}
	}

	function openQuickView(postId, $trigger) {
		var $modal = $('#cf-quickview-modal');
		if (!$modal.length || !frontendConfig.ajaxUrl || !frontendConfig.nonce) {
			return;
		}

		lastQuickViewTrigger = $trigger || null;
		$modal.addClass('is-open').attr('aria-hidden', 'false');
		$('body').addClass('cf-modal-open');
		$modal.find('.cf-modal-content').html('<div class="cf-modal-loading">' + (frontendConfig.i18n.loading || 'Wird geladen ...') + '</div>');

		$.post(frontendConfig.ajaxUrl, {
			action: 'cf_quick_view',
			nonce: frontendConfig.nonce,
			post_id: postId
		}).done(function(response) {
			if (response && response.success && response.data && response.data.html) {
				$modal.find('.cf-modal-content').html(response.data.html);
				return;
			}

			$modal.find('.cf-modal-content').html('<div class="cf-modal-error">' + (frontendConfig.i18n.loadError || 'Die Schnellansicht konnte gerade nicht geladen werden.') + '</div>');
		}).fail(function() {
			$modal.find('.cf-modal-content').html('<div class="cf-modal-error">' + (frontendConfig.i18n.loadError || 'Die Schnellansicht konnte gerade nicht geladen werden.') + '</div>');
		});
	}

	function toggleFavorite(postId, $button) {
		if (!frontendConfig.ajaxUrl || !frontendConfig.nonce || !postId) {
			return;
		}

		$button.prop('disabled', true).addClass('is-busy');

		$.post(frontendConfig.ajaxUrl, {
			action: 'cf_toggle_favorite',
			nonce: frontendConfig.nonce,
			post_id: postId
		}).done(function(response) {
			if (response && response.success && response.data) {
				syncFavoriteButtons(postId, !!response.data.active);
			}
		}).always(function() {
			$button.prop('disabled', false).removeClass('is-busy');
		});
	}

	function syncLightboxItems(groupName) {
		lightboxItems = $('[data-lightbox-group="' + groupName + '"]');
	}

	function openLightbox(index) {
		if (!lightboxItems.length || typeof lightboxItems[index] === 'undefined') {
			return;
		}

		activeLightboxIndex = index;
		var $item = $(lightboxItems[index]);
		$('#cf-lightbox .cf-lightbox-image').attr('src', $item.attr('href')).attr('alt', $item.data('lightbox-caption') || 'Bildansicht');
		$('#cf-lightbox .cf-lightbox-caption').text($item.data('lightbox-caption') || '');
		$('#cf-lightbox').addClass('is-open').attr('aria-hidden', 'false');
		$('body').addClass('cf-lightbox-open');
	}

	function closeLightbox() {
		$('#cf-lightbox').removeClass('is-open').attr('aria-hidden', 'true');
		$('body').removeClass('cf-lightbox-open');
	}

	function moveLightbox(step) {
		if (!lightboxItems.length) {
			return;
		}
		activeLightboxIndex = (activeLightboxIndex + step + lightboxItems.length) % lightboxItems.length;
		openLightbox(activeLightboxIndex);
	}

	$(document).on('click', '.cf-lightbox-trigger', function(e) {
		e.preventDefault();
		var groupName = $(this).data('lightbox-group');
		syncLightboxItems(groupName);
		openLightbox(lightboxItems.index(this));
	});

	$(document).on('click', '.cf-lightbox-close', function() {
		closeLightbox();
	});

	$(document).on('click', '#cf-lightbox', function(e) {
		if ($(e.target).is('#cf-lightbox')) {
			closeLightbox();
		}
	});

	$(document).on('click', '.cf-lightbox-prev', function() {
		moveLightbox(-1);
	});

	$(document).on('click', '.cf-lightbox-next', function() {
		moveLightbox(1);
	});

	$(document).on('keydown', function(e) {
		if (!$('#cf-lightbox').hasClass('is-open')) {
			return;
		}

		if (e.key === 'Escape') {
			closeLightbox();
		}

		if (e.key === 'ArrowLeft') {
			moveLightbox(-1);
		}

		if (e.key === 'ArrowRight') {
			moveLightbox(1);
		}
	});

	$(document).on('click', '.cf-cta-share', function(e) {
		e.preventDefault();
		var $button = $(this);
		var url = $button.data('copy-url');
		if (!url) {
			return;
		}

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(url).then(function() {
				$button.text(frontendConfig.i18n.copySuccess || 'Link kopiert');
				setTimeout(function() {
					$button.text(frontendConfig.i18n.copyDefault || 'Link teilen');
				}, 1800);
			});
			return;
		}

		var $temp = $('<input type="text" />');
		$('body').append($temp);
		$temp.val(url).trigger('select');
		document.execCommand('copy');
		$temp.remove();
		$button.text(frontendConfig.i18n.copySuccess || 'Link kopiert');
		setTimeout(function() {
			$button.text(frontendConfig.i18n.copyDefault || 'Link teilen');
		}, 1800);
	});

	$(document).on('click', '.cf-favorite-toggle', function(e) {
		e.preventDefault();
		var $button = $(this);
		toggleFavorite($button.data('post-id'), $button);
	});

	$(document).on('click', '.cf-quickview-trigger', function(e) {
		e.preventDefault();
		openQuickView($(this).data('post-id'), $(this));
	});

	$(document).on('click', '.cf-modal-close', function() {
		closeQuickView();
	});

	$(document).on('click', '#cf-quickview-modal', function(e) {
		if ($(e.target).is('#cf-quickview-modal')) {
			closeQuickView();
		}
	});

	$(document).on('keydown', function(e) {
		if ($('#cf-quickview-modal').hasClass('is-open') && e.key === 'Escape') {
			closeQuickView();
		}
	});

	$(document).on('click', '.cf-save-filter', function() {
		var payload = getCurrentFilterPayload();
		if (!payload) {
			return;
		}

		var name = window.prompt(frontendConfig.i18n.saveFilterPrompt || 'Wie willst du diesen Filter nennen?');
		if (name === null) {
			return;
		}

		name = $.trim(name);
		if (!name) {
			window.alert(frontendConfig.i18n.saveFilterEmpty || 'Gib erst einen Namen fuer den Filter ein.');
			return;
		}

		var filters = getSavedFilters();
		filters = $.grep(filters, function(filterEntry) {
			return filterEntry.name !== name;
		});
		filters.push({
			id: 'filter-' + Date.now(),
			name: name,
			payload: payload
		});
		setSavedFilters(filters);
		renderSavedFilterOptions();
		$('.cf-saved-filter-select').val(filters[filters.length - 1].id);
		window.alert(frontendConfig.i18n.saveFilterDone || 'Filter wurde gemerkt.');
	});

	$(document).on('click', '.cf-apply-saved-filter', function() {
		var selectedId = $('.cf-saved-filter-select').val();
		if (!selectedId) {
			window.alert(frontendConfig.i18n.loadFilterEmpty || 'Waehle erst einen gespeicherten Filter aus.');
			return;
		}

		var filters = getSavedFilters();
		var match = null;
		$.each(filters, function(_, filterEntry) {
			if (filterEntry.id === selectedId) {
				match = filterEntry;
				return false;
			}
		});

		if (!match) {
			return;
		}

		applyFilterPayload(match.payload);
		$('.cf-filter-bar').trigger('submit');
	});

	$(document).on('change', '.cf-saved-filter-select', function() {
		var selectedId = $(this).val();
		$('.cf-saved-filter-select').val(selectedId);
	});

	$(document).on('click', '.cf-delete-saved-filter', function() {
		var selectedId = $('.cf-saved-filter-select').val();
		if (!selectedId) {
			window.alert(frontendConfig.i18n.loadFilterEmpty || 'Waehle erst einen gespeicherten Filter aus.');
			return;
		}

		if (!window.confirm(frontendConfig.i18n.deleteFilterAsk || 'Willst du diesen gespeicherten Filter wirklich loeschen?')) {
			return;
		}

		var filters = $.grep(getSavedFilters(), function(filterEntry) {
			return filterEntry.id !== selectedId;
		});
		setSavedFilters(filters);
		renderSavedFilterOptions();
		window.alert(frontendConfig.i18n.deleteFilterDone || 'Filter wurde geloescht.');
	});

	renderSavedFilterOptions();

	if (window.location.search.indexOf('cf_contact=1') !== -1 && $('#confirm-form[data-open-on-load="1"]').length) {
		classifieds.toggle_contact_form();
	}
});

var classifieds = {
	toggle_end: function(key) {
		jQuery('#confirm-form-'+key).show();
		jQuery('#action-form-'+key).hide();
		jQuery('#cf-end-'+key).show();
		jQuery('#cf-renew-'+key).hide();
		jQuery('#cf-delete-'+key).hide();
		jQuery('input[name="action"]').val('end');
	},
	toggle_renew: function(key) {
		jQuery('#confirm-form-'+key).show();
		jQuery('#confirm-form-'+key+' select[name="duration"]' ).show();
		jQuery('#action-form-'+key).hide();
		jQuery('#cf-end-'+key).hide();
		jQuery('#cf-renew-'+key).show();
		jQuery('#cf-delete-'+key).hide();
		jQuery('input[name="action"]').val('renew');
	},
	toggle_delete: function(key) {
		jQuery('#confirm-form-'+key).show();
		jQuery('#confirm-form-'+key+' select[name="duration"]' ).hide();
		jQuery('#action-form-'+key).hide();
		jQuery('#cf-end-'+key).hide();
		jQuery('#cf-renew-'+key).hide();
		jQuery('#cf-delete-'+key).show();
		jQuery('input[name="action"]').val('delete');
	},
	toggle_contact_form: function() {
		jQuery('.cf-ad-info').hide();
		jQuery('#action-form').hide();
		jQuery('#confirm-form').show();
	},
	cancel_contact_form: function() {
		jQuery('#confirm-form').hide();
		jQuery('.cf-ad-info').show();
		jQuery('#action-form').show();
	},
	cancel: function(key) {
		jQuery('#confirm-form-'+key).hide();
		jQuery('#action-form-'+key).show();
	}
};

var js_translate = js_translate || {};
js_translate.image_chosen = 'Bild ausgewaehlt';

(function($){

	jQuery(document).ready(function($) {
		$('.upload-button input:file').on('change focus click', fileInputs );
		$('.upload-button input:file').on('change', function() {
			renderPreview(this);
		});

		$('#feature_gallery').on('change', function() {
			renderGalleryPreview(this);
		});

		$('.upload-button').on('dragover', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).addClass('is-dragover');
		});

		$('.upload-button').on('dragleave drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).removeClass('is-dragover');
		});
	});

	fileInputs = function() {
		var $this = $(this),
		$val = $this.val(),
		valArray = $val.split('\\'),
		newVal = valArray[valArray.length-1],
		$button = $this.siblings('.button'),
		$fakeFile = $this.siblings('.file-holder');
		if(newVal !== '') {
			$button.text(js_translate.image_chosen);
			if($fakeFile.length === 0) {
				$button.after('<span class="file-holder">' + newVal + '</span>');
			} else {
				$fakeFile.text(newVal);
			}
		}
	};

	renderPreview = function(input) {
		if (!input || !input.files || !input.files.length) {
			return;
		}

		var file = input.files[0];
		if (!file.type.match('image.*')) {
			return;
		}

		var $preview = $('.cf-image-preview[data-for="' + input.id + '"]');
		if (!$preview.length) {
			return;
		}

		var objectUrl = URL.createObjectURL(file);
		$preview.html('<img src="' + objectUrl + '" alt="Bildvorschau" class="cf-image-preview-img" />');
	};

	renderGalleryPreview = function(input) {
		if (!input || !input.files) {
			return;
		}

		var $preview = $('.cf-gallery-preview[data-for="feature_gallery"]');
		if (!$preview.length) {
			return;
		}

		$preview.empty();

		$.each(input.files, function(index, file) {
			if (!file.type.match('image.*')) {
				return;
			}
			var objectUrl = URL.createObjectURL(file);
			$preview.append('<img src="' + objectUrl + '" alt="Galerievorschau" class="cf-gallery-preview-img" />');
		});
	};

})(jQuery);

