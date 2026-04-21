
jQuery(document).ready(function($) {
	$('form.confirm-form').hide();
	$('form.cf-contact-form').hide();

	var lightboxItems = [];
	var activeLightboxIndex = 0;
	var frontendConfig = window.cfFrontend || { ajaxUrl: '', nonce: '', i18n: {} };
	var lastQuickViewTrigger = null;
	var savedFiltersKey = 'cfSavedFilters';

	// Dashboard tabs are initialized further below after cfDashboard is defined.
	var lastUsedFilterKey = 'cfLastUsedFilter';
	var autoRestoreEnabledKey = 'cfFilterAutoRestoreEnabled';

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

	function getLastUsedFilter() {
		try {
			var parsed = JSON.parse(window.localStorage.getItem(lastUsedFilterKey) || 'null');
			return parsed && typeof parsed === 'object' ? parsed : null;
		} catch (e) {
			return null;
		}
	}

	function setLastUsedFilter(payload) {
		if (!payload) {
			return;
		}

		window.localStorage.setItem(lastUsedFilterKey, JSON.stringify(payload));
	}

	function clearLastUsedFilter() {
		window.localStorage.removeItem(lastUsedFilterKey);
	}

	function isAutoRestoreEnabled() {
		var stored = window.localStorage.getItem(autoRestoreEnabledKey);
		var configDefault = frontendConfig.autoRestoreDefault === false ? false : true;
		if (stored === null) {
			return configDefault;
		}

		return stored === '1';
	}

	function setAutoRestoreEnabled(enabled) {
		window.localStorage.setItem(autoRestoreEnabledKey, enabled ? '1' : '0');
	}

	function hasActiveFilterInUrl() {
		var params = new URLSearchParams(window.location.search || '');
		return params.has('q') || params.has('cat') || params.has('region') || params.has('min_price') || params.has('max_price') || params.has('sort');
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

	function initSingleGallerySliders() {
		$('[data-cf-slider]').each(function() {
			var $slider = $(this);
			if ($slider.data('cfSliderReady')) {
				return;
			}

			var $slides = $slider.find('.cf-single-slider-slide');
			var $dots = $slider.find('.cf-single-slider-dot');
			var index = 0;

			if (!$slides.length) {
				return;
			}

			function render() {
				$slides.removeClass('is-active').attr('aria-hidden', 'true');
				$slides.eq(index).addClass('is-active').attr('aria-hidden', 'false');
				$dots.removeClass('is-active');
				$dots.eq(index).addClass('is-active');
			}

			$slider.on('click', '[data-cf-slider-prev]', function() {
				index = (index - 1 + $slides.length) % $slides.length;
				render();
			});

			$slider.on('click', '[data-cf-slider-next]', function() {
				index = (index + 1) % $slides.length;
				render();
			});

			$slider.on('click', '[data-cf-slider-dot]', function() {
				var nextIndex = parseInt($(this).attr('data-cf-slider-dot'), 10);
				if (!isNaN(nextIndex) && nextIndex >= 0 && nextIndex < $slides.length) {
					index = nextIndex;
					render();
				}
			});

			render();
			$slider.data('cfSliderReady', true);
		});
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

		setLastUsedFilter(payload);

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
		setLastUsedFilter(match.payload);
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

	$(document).on('submit', '.cf-filter-bar', function() {
		setLastUsedFilter(getCurrentFilterPayload());
	});

	$(document).on('click', '.cf-filter-reset', function() {
		clearLastUsedFilter();
	});

	$(document).on('change', '.cf-auto-restore-input', function() {
		setAutoRestoreEnabled($(this).is(':checked'));
	});

	renderSavedFilterOptions();

	var $filterBar = $('.cf-filter-bar');
	if ($filterBar.length) {
		var autoRestoreEnabled = isAutoRestoreEnabled();
		$('.cf-auto-restore-input').prop('checked', autoRestoreEnabled);

		if (hasActiveFilterInUrl()) {
			setLastUsedFilter(getCurrentFilterPayload());
		} else if (autoRestoreEnabled) {
			var lastUsed = getLastUsedFilter();
			if (lastUsed && (lastUsed.q || lastUsed.cat || lastUsed.region || lastUsed.min_price || lastUsed.max_price || (lastUsed.sort && lastUsed.sort !== 'newest'))) {
				applyFilterPayload(lastUsed);
				$filterBar.trigger('submit');
			}
		}
	}

	if (window.location.search.indexOf('cf_contact=1') !== -1 && $('#confirm-form[data-open-on-load="1"]').length) {
		classifieds.toggle_contact_form();
	}

	initSingleGallerySliders();
});

var classifieds = {
	toggle_end: function(key) {
		jQuery('#confirm-form-'+key).show();
		jQuery('#action-form-'+key).hide();
		jQuery('#cf-end-'+key).show();
		jQuery('#cf-renew-'+key).hide();
		jQuery('#cf-delete-'+key).hide();
		jQuery('#cf-reserve-'+key).hide();
		jQuery('#confirm-form-'+key+' input[name="action"]').val('end');
	},
	toggle_renew: function(key) {
		jQuery('#confirm-form-'+key).show();
		jQuery('#confirm-form-'+key+' select[name="duration"]' ).show();
		jQuery('#action-form-'+key).hide();
		jQuery('#cf-end-'+key).hide();
		jQuery('#cf-renew-'+key).show();
		jQuery('#cf-delete-'+key).hide();
		jQuery('#cf-reserve-'+key).hide();
		jQuery('#confirm-form-'+key+' input[name="action"]').val('renew');
	},
	toggle_delete: function(key) {
		jQuery('#confirm-form-'+key).show();
		jQuery('#confirm-form-'+key+' select[name="duration"]' ).hide();
		jQuery('#action-form-'+key).hide();
		jQuery('#cf-end-'+key).hide();
		jQuery('#cf-renew-'+key).hide();
		jQuery('#cf-delete-'+key).show();
		jQuery('#cf-reserve-'+key).hide();
		jQuery('#confirm-form-'+key+' input[name="action"]').val('delete');
	},
	toggle_reserve: function(key) {
		jQuery('#confirm-form-'+key).show();
		jQuery('#confirm-form-'+key+' select[name="duration"]').hide();
		jQuery('#action-form-'+key).hide();
		jQuery('#cf-end-'+key).hide();
		jQuery('#cf-renew-'+key).hide();
		jQuery('#cf-delete-'+key).hide();
		jQuery('#cf-reserve-'+key).show();
		jQuery('#confirm-form-'+key+' input[name="action"]').val('reserve');
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

		$(document).on('change', '.cf-gallery-picker', function() {
			renderGalleryPreview(this);
			queueGalleryPicker(this);
		});

		$(document).on('click', '.cf-gallery-remove-pending', function() {
			var pickerId = $(this).data('pickerId');
			if (!pickerId) {
				return;
			}

			$('input[data-gallery-picker-id="' + pickerId + '"]').remove();
			$('.cf-gallery-preview-item[data-picker-id="' + pickerId + '"]').remove();
		});

		$(document).on('click', '.cf-gallery-remove-existing', function() {
			var cfg = window.cfGalleryEditor || {};
			if (!cfg.ajaxUrl || !cfg.nonce) {
				return;
			}

			var $button = $(this);
			var attachmentId = parseInt($button.data('attachment-id'), 10);
			var postId = parseInt(cfg.postId || $('#post_ID').val(), 10);

			if (!attachmentId || !postId) {
				return;
			}

			$button.prop('disabled', true);

			$.post(cfg.ajaxUrl, {
				action: 'cf_remove_gallery_image',
				nonce: cfg.nonce,
				post_id: postId,
				attachment_id: attachmentId
			}).done(function(response) {
				if (response && response.success) {
					$button.closest('.cf-existing-gallery-item').remove();
					return;
				}

				$button.prop('disabled', false);
				window.alert((response && response.data && response.data.message) ? response.data.message : 'Bild konnte nicht geloescht werden.');
			}).fail(function() {
				$button.prop('disabled', false);
				window.alert('Bild konnte nicht geloescht werden.');
			});
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

		var pickerId = 'picker-' + Date.now() + '-' + Math.floor(Math.random() * 10000);
		$(input).attr('data-gallery-picker-id', pickerId);

		$.each(input.files, function(index, file) {
			if (!file.type.match('image.*')) {
				return;
			}
			var objectUrl = URL.createObjectURL(file);
			$preview.append('<div class="cf-gallery-preview-item" data-picker-id="' + pickerId + '"><img src="' + objectUrl + '" alt="Galerievorschau" class="cf-gallery-preview-img" /><button type="button" class="cf-gallery-remove-pending" data-picker-id="' + pickerId + '" aria-label="Bildauswahl entfernen">&#128465;</button></div>');
		});
	};

	queueGalleryPicker = function(input) {
		if (!input || !input.files || !input.files.length) {
			return;
		}

		var $input = $(input);
		$input.removeAttr('id').removeClass('cf-gallery-picker').addClass('cf-gallery-picker-stashed');
		$input.css({
			position: 'absolute',
			left: '-9999px',
			opacity: 0,
			width: '1px',
			height: '1px',
			pointerEvents: 'none'
		});

		var $nextInput = $('<input type="file" id="feature_gallery" class="cf-gallery-picker" name="feature_gallery[]" accept="image/*" multiple />');
		$input.after($nextInput);
	};

        // ============================================================
        // Dashboard: Nachrichten & Konversationen
        // ============================================================
        window.cfDashboard = {
                currentThreadId: null,
                currentRecipientId: null,

                openConversation: function(threadId) {
                        var cfg = window.cfFrontend || {};
                        if (!cfg.ajaxUrl || !cfg.nonce) return;

                        this.currentThreadId = threadId;

                        // Aktives Item markieren
                        $('.cf-inbox-item').removeClass('is-open');
                        $('.cf-inbox-item[data-thread-id="' + threadId + '"]').addClass('is-open');
                        this.currentRecipientId = parseInt($('.cf-inbox-item[data-thread-id="' + threadId + '"]').data('recipient-id'), 10);

                        var $view = $('#cf-conversation-view');
                        $view.html('<div class="cf-loading">&#x231B; Wird geladen&hellip;</div>');

                        $.post(cfg.ajaxUrl, {
                                action: 'cf_get_conversation',
                                nonce: cfg.nonce,
                                thread_id: threadId
                        }, function(res) {
                                if (!res.success) {
                                        $view.html('<div class="cf-notice cf-notice-error">' + (res.data && res.data.message ? res.data.message : 'Fehler') + '</div>');
                                        return;
                                }
                                var d = res.data;
                                cfDashboard.currentRecipientId = d.recipient_id;

                                var html = '<div class="cf-conversation-inner">';
                                html += '<div class="cf-conversation-header">';
                                html += '<div class="cf-conv-user">';
                                html += '<img src="' + d.other_avatar + '" alt="" class="cf-conv-avatar">';
                                html += '<div><strong class="cf-conv-name">' + d.other_user + '</strong>';
                                if (d.ad_title) {
                                        html += '<a href="' + d.ad_url + '" target="_blank" class="cf-conv-ad-link">' + d.ad_title + '</a>';
                                }
                                html += '</div></div></div>';

                                html += '<div class="cf-messages-thread" id="cf-messages-thread">';
                                if (d.messages.length === 0) {
                                        html += '<p class="cf-empty-thread">Noch keine Nachrichten in diesem Thread.</p>';
                                } else {
                                        $.each(d.messages, function(i, msg) {
                                                var cls = msg.is_mine ? 'cf-msg cf-msg-out' : 'cf-msg cf-msg-in';
                                                html += '<div class="' + cls + '">';
                                                html += '<img src="' + msg.avatar + '" alt="" class="cf-msg-avatar">';
                                                html += '<div class="cf-msg-bubble">';
                                                html += '<div class="cf-msg-text">' + msg.message.replace(/\n/g, '<br>') + '</div>';
                                                html += '<time class="cf-msg-time">' + msg.date + '</time>';
                                                html += '</div></div>';
                                        });
                                }
                                html += '</div>';

                                html += '<div class="cf-reply-box">';
                                html += '<textarea id="cf-reply-text" placeholder="Deine Antwort\u2026" rows="3"></textarea>';
                                html += '<button class="button cf-btn-primary cf-reply-send">Senden</button>';
                                html += '</div></div>';

                                $view.html(html);

                                // Scroll nach unten
                                var $thread = $('#cf-messages-thread');
                                $thread.scrollTop($thread[0].scrollHeight);

                                // Reply-Button
                                $view.on('click', '.cf-reply-send', function() {
                                        cfDashboard.sendReply();
                                });
                                $view.on('keydown', '#cf-reply-text', function(e) {
                                        if (e.ctrlKey && e.key === 'Enter') cfDashboard.sendReply();
                                });
                        });
                },

                sendReply: function() {
                        var cfg = window.cfFrontend || {};
                        var text = $('#cf-reply-text').val().trim();
                        if (!text) return;

                        var $btn = $('.cf-reply-send');
                        $btn.prop('disabled', true).text('Wird gesendet\u2026');

                        $.post(cfg.ajaxUrl, {
                                action: 'cf_send_message',
                                nonce: cfg.nonce,
                                message: text,
                                thread_id: this.currentThreadId,
                                recipient_id: this.currentRecipientId
                        }, function(res) {
                                $btn.prop('disabled', false).text('Senden');
                                if (res.success) {
                                        $('#cf-reply-text').val('');
                                        cfDashboard.openConversation(cfDashboard.currentThreadId);
                                } else {
                                        alert(res.data && res.data.message ? res.data.message : 'Fehler beim Senden.');
                                }
                        });
                },

                closeConversation: function() {
                        $('#cf-conversation-view').html('<div class="cf-conversation-placeholder"><span>&#x1F4AC;</span><p>W&auml;hle eine Konversation aus.</p></div>');
                        $('.cf-inbox-item').removeClass('is-open');
                        this.currentThreadId = null;
                },

                // Dashboard-Tab AJAX nachladen
                loadTab: function(tabName) {
                        var cfg = window.cfFrontend || {};
					var dashboardNonce = cfg.dashboardNonce || cfg.nonce || '';
					if (!cfg.ajaxUrl || !dashboardNonce) return;

                        var $content = $('#cf-tab-content');
                        var $loader = $('.cf-loader');

                        $loader.show();
                        $content.html('');

						$.post(cfg.ajaxUrl, {
                                action: 'cf_load_dashboard_tab',
				nonce: dashboardNonce,
                                tab: tabName
                        }, function(res) {
                                $loader.hide();
                                if (res.success) {
									var html = (res.data && typeof res.data === 'object' && typeof res.data.html !== 'undefined') ? res.data.html : res.data;
									$content.html(html);
                                        // Tab-Link als aktiv markieren
                                        $('.cf-nav-item').removeClass('is-active');
                                        $('.cf-nav-item[data-tab="' + tabName + '"]').addClass('is-active');
                                        // URL anpassen
                                        window.history.pushState({tab: tabName}, '', '?tab=' + tabName);
                                } else {
                                        $content.html('<div class="cf-notice cf-notice-error">Fehler beim Laden des Tabs.</div>');
                                }
						}).fail(function(xhr) {
								$loader.hide();
								var msg = 'Fehler beim Laden des Tabs.';
								if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
									msg = xhr.responseJSON.data.message;
								}
								$content.html('<div class="cf-notice cf-notice-error">' + msg + '</div>');
						});
                }
        };

				if ( $('#cf-dashboard-content').length > 0 ) {
					var urlParams = new URLSearchParams(window.location.search);
					var activeTab = urlParams.get('tab') || (urlParams.has('messages') ? 'messages' : (urlParams.has('favorites') ? 'favorites' : (urlParams.has('saved') ? 'saved' : (urlParams.has('ended') ? 'ended' : 'active'))));
					window.cfDashboard.loadTab(activeTab);

					$(document).on('click', '.cf-nav-item', function(e) {
						e.preventDefault();
						var tabName = $(this).data('tab');
						if (tabName) {
							window.cfDashboard.loadTab(tabName);
						}
					});
				}

        // AJAX-Kontaktformular auf Single-Seite
        $(document).on('submit', '#cf-contact-form-ajax', function(e) {
                e.preventDefault();
                var cfg = window.cfFrontend || {};
                if (!cfg.ajaxUrl || !cfg.nonce) return;

                var $form    = $(this);
                var $btn     = $form.find('.cf-ajax-send');
                var $feedback = $form.find('.cf-form-feedback');
                var postId    = $form.data('post-id');
                var recipientId = $form.data('recipient-id');

                $btn.prop('disabled', true).text('Wird gesendet\u2026');
                $feedback.hide();

                $.post(cfg.ajaxUrl, {
                        action:       'cf_send_message',
                        nonce:        cfg.nonce,
                        post_id:      postId,
                        recipient_id: recipientId,
                        subject:      $form.find('[name="subject"]').val(),
                        message:      $form.find('[name="message"]').val()
                }, function(res) {
                        $btn.prop('disabled', false).text('Nachricht senden');
                        $feedback.show();
                        if (res.success) {
                                $feedback.attr('class', 'cf-form-feedback cf-notice-success')
                                         .text('Nachricht gesendet! Der Anbieter meldet sich bei dir.');
                                $form.find('[name="message"]').val('');
                        } else {
                                var msg = res.data && res.data.message ? res.data.message : 'Fehler beim Senden.';
                                $feedback.attr('class', 'cf-form-feedback cf-notice-error').text(msg);
                        }
                });
        });

		})(jQuery);

