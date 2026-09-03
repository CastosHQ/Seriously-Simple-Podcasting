jQuery(document).ready(function($) {
	var $imgInfo = $('.js-onboarding-img-info'),
		$preview = $imgInfo.find('.js-onboarding-img'),
		$imgInput = $imgInfo.find('.js-onboarding-img-val'),
		$imgName = $imgInfo.find('.js-onboarding-img-name'),
		$fields = $('.js-onboarding-field'),
		$btn = $('.js-onboarding-btn'),
		$connectCastosBtn = $('.js-onboarding-castos-connect'),
		$hostingStep2 = $('.js-hosting-form'),
		$accordion = $('.js-accordion'),
		$dragable = $('.js-onboarding-dragable'),
		$uploadImageBtn = $('#ss_podcasting_data_image_button'),
		validateOnboarding = function () {
			var valid = true;
			$fields.each(function () {
				if (!$(this).val()) {
					valid = false;
					return false;
				}
			});

			if (valid) {
				$btn.removeAttr('disabled');
			} else {
				$btn.attr('disabled', 'disabled');
			}
		},
		initDeleteImgInfo = function(){
			$imgInfo.find('.js-onboarding-delete-img-info').on('click', function(){
				$imgInput.val('');
				$imgInfo.hide();
				validateOnboarding();
			});
		},
		sprintf = function (template, values) {
			var next = 0;
			return String(template || '').replace(/%(\d\$)?[ds]/g, function (match, position) {
				var index = position ? parseInt(position, 10) - 1 : next++;
				return values[index];
			});
		},
		baseName = function (str) {
			var base = String(str).substring(str.lastIndexOf('/') + 1);
			if( base.length > 20 ){
				base = '..' + base.substring(base.length - 20, base.length);
			}
			return base;
		},
		updateImageName = function(){
			var imageUrl = $imgInput.val();
			if( imageUrl ){
				$imgName.html(baseName(imageUrl));
				$imgInfo.show();
			}
		},
		initImgPreview = function(){
			updateImageName();
			$preview.on('load', function(){
				validateOnboarding();
				updateImageName();
			});
		},
		initTokenValidation = function(){
			$connectCastosBtn.on('connected', function (e, response) {
				var $form = $connectCastosBtn.closest('form'),
					$nextButton = $form.find('button[type=submit]'),
					$me = $(this),
					$msg = $form.find('.connect-castos-message'),
					$field = $('.js-onboarding-castos-connect-field');
				$me.removeClass('connecting');
				$msg.show();

				if ("success" === response.status) {
					$me.html($me.data('connected-txt'));
					$field.attr('disabled', 'disabled');
					$nextButton.removeAttr('disabled');
				} else {
					$me.html($me.data('initial-txt'));
					$nextButton.attr('disabled', 'disabled');
				}
			});

			$connectCastosBtn.on('connecting', function(){
				$(this).addClass('connecting').html($(this).data('connecting-txt'));
			});

			$('.js-onboarding-castos-connect-field').on('change paste keyup', function(){
				var $nextButton = $connectCastosBtn.closest('form').find('button[type=submit]');
				$connectCastosBtn.html($connectCastosBtn.data('initial-txt'));
				$connectCastosBtn.removeClass('valid');
				$nextButton.attr('disabled', 'disabled');
			});
		},
		initOnboardingValidation = function(){
			$fields.on('change paste keyup', validateOnboarding);
			validateOnboarding();
		},
		initHostingConnectionSteps = function(){
			$accordion.on('click', function () {
				let openedClass = 'ssp-onboarding-step-4__accordion--opened',
					openedFormClass = 'ssp-onboarding-step-4__form--opened';
				if ($accordion.hasClass(openedClass)) {
					$accordion.removeClass(openedClass);
					$hostingStep2.removeClass(openedFormClass);
				} else {
					$accordion.addClass(openedClass);
					$hostingStep2.addClass(openedFormClass);
				}
			});
		},
		initDragableImage = function () {
			$dragable.on('dragover', function (e) {
					e.preventDefault();
					e.stopPropagation();
					$uploadImageBtn.trigger('click');
			});
			$dragable.on('dragenter', function (e) {
					e.preventDefault();
					e.stopPropagation();
					$uploadImageBtn.trigger('click');
			});
			$dragable.on('drop', function (e) {
					e.preventDefault();
					e.stopPropagation();
					$uploadImageBtn.trigger('click');
			});
			$dragable.on('click', function(){
				$uploadImageBtn.trigger('click');
			});
		},
		initFeedImport = function () {
			var $import = $('.js-ssp-import');

			if (!$import.length) {
				return;
			}

			// How many chunks may report no new episodes before the import is called stalled.
			var MAX_STALLED_CHUNKS = 3;

			var i18n = (window.sspOnboarding && window.sspOnboarding.i18n) || {},
				ajaxUrl = $import.data('ajax-url'),
				nonce = $import.data('nonce'),
				nextUrl = $import.data('next-url'),
				$url = $import.find('.js-ssp-import-url'),
				$fetch = $import.find('.js-ssp-import-fetch'),
				$start = $import.find('.js-ssp-import-start'),
				$error = $import.find('.js-ssp-import-error'),
				$coverEmpty = $import.find('.js-ssp-import-cover-empty'),
				$coverNote = $import.find('.js-ssp-import-cover-note'),
				$failure = $import.find('.js-ssp-import-failure'),
				$bar = $import.find('.js-ssp-import-bar'),
				$progress = $import.find('.js-ssp-import-progress'),
				episodeCount = 0,
				progressTimer = null;

			function showStage(stage) {
				$import.find('.js-ssp-import-stage').each(function () {
					$(this).prop('hidden', $(this).data('stage') !== stage);
				});
			}

			function showError(message) {
				$error.text(message || '').prop('hidden', !message);
			}

			function busy($button, isBusy) {
				if (isBusy) {
					$button.data('idle-txt', $button.text());
					$button.prop('disabled', true).text($button.data('busy-txt'));
				} else {
					$button.prop('disabled', false).text($button.data('idle-txt'));
				}
			}

			function request(action, data) {
				return $.ajax({
					url: ajaxUrl,
					type: 'post',
					data: $.extend({action: action, nonce: nonce}, data || {}),
					timeout: 0
				});
			}

			function stopPolling() {
				if (progressTimer) {
					clearInterval(progressTimer);
					progressTimer = null;
				}
			}

			function fail(message) {
				stopPolling();
				$failure.text(message || '');
				showStage('failed');
			}

			/**
			 * Shows the placeholder instead of the artwork, with an optional reason.
			 * The next wizard step is Cover, so missing artwork is a prompt, not an error.
			 */
			function showCoverPlaceholder($image, message) {
				$image.prop('hidden', true);
				$coverEmpty.prop('hidden', false);
				$coverNote.text(message || '').prop('hidden', !message);
			}

			function renderPreview(feed) {
				var $image = $import.find('.js-ssp-import-image'),
					meta = [feed.author, feed.host].filter(Boolean).join(' · ');

				episodeCount = feed.episodes;

				$image.off('error load');

				if (feed.image) {
					$coverEmpty.prop('hidden', true);
					$coverNote.prop('hidden', true);

					// The browser failing on the URL does not mean the server will:
					// hotlink protection blocks one and not the other. Say only what
					// we know, which is that the preview could not be shown.
					$image.one('error', function () {
						showCoverPlaceholder($image, i18n.coverFailed);
					});
					$image.one('load', function () {
						$image.prop('hidden', false);
					});

					$image.prop('hidden', true).attr('src', feed.image).attr('alt', feed.title);
				} else {
					showCoverPlaceholder($image, i18n.noCoverArt);
				}

				$import.find('.js-ssp-import-title').text(feed.title);
				$import.find('.js-ssp-import-meta').text(meta);
				$import.find('.js-ssp-import-count').text(
					1 === feed.episodes ? i18n.episodeFound : sprintf(i18n.episodesFound, [feed.episodes])
				);

				showStage('preview');
			}

			function setProgress(percent) {
				percent = Math.max(0, Math.min(100, parseInt(percent, 10) || 0));
				$bar.css('width', percent + '%');

				if (percent >= 100 || !episodeCount) {
					$progress.text(i18n.finishingUp);
					return;
				}

				var done = Math.max(1, Math.round(episodeCount * percent / 100));
				$progress.text(sprintf(i18n.importingItem, [done, episodeCount]));
			}

			function pollProgress() {
				stopPolling();
				progressTimer = setInterval(function () {
					request('get_external_rss_feed_progress').done(function (response) {
						setProgress(response.progress);
					});
				}, 2000);
			}

			// The chunk loop trusts the server to advance. If it ever stops doing so,
			// stop asking rather than calling admin-ajax.php forever.
			var stalledChunks = 0,
				lastCount = -1;

			function importChunk() {
				request('import_external_rss_feed').done(function (response) {
					if ('error' === response.status) {
						fail(response.message);
						return;
					}

					if (!response.is_finished) {
						if (response.count === lastCount) {
							stalledChunks++;
						} else {
							stalledChunks = 0;
							lastCount = response.count;
						}

						if (stalledChunks >= MAX_STALLED_CHUNKS) {
							fail(i18n.importStalled);
							return;
						}

						importChunk();
						return;
					}

					stopPolling();
					setProgress(100);
					request('reset_rss_feed_data').always(function () {
						window.location.href = nextUrl;
					});
				}).fail(function () {
					fail(i18n.importFailed);
				});
			}

			function runImport() {
				stalledChunks = 0;
				lastCount = -1;
				showStage('running');
				setProgress(0);
				pollProgress();
				importChunk();
			}

			/**
			 * Sends the feed URL for validation or import. Both requests return feed
			 * errors to the URL stage, where the reason appears below the field.
			 *
			 * @param {Object} options button, action, fallback message and success handler.
			 */
			function submitFeedUrl(options) {
				var $button = options.button;

				showError('');
				busy($button, true);

				request(options.action, {feed_url: $url.val().trim()}).done(function (response) {
					busy($button, false);

					if ('error' === response.status) {
						showStage('url');
						showError(response.message);
						return;
					}

					options.onSuccess(response);
				}).fail(function () {
					busy($button, false);
					showStage('url');
					showError(options.fallback);
				});
			}

			$url.on('input change paste keyup', function () {
				showError('');
				$fetch.prop('disabled', !$url.val().trim());
			});

			$url.on('keydown', function (e) {
				if (13 === e.which && $url.val().trim()) {
					e.preventDefault();
					$fetch.trigger('click');
				}
			});

			$fetch.on('click', function () {
				submitFeedUrl({
					button: $fetch,
					action: 'ssp_preview_rss_feed',
					fallback: i18n.checkFailed,
					onSuccess: renderPreview
				});
			});

			$start.on('click', function () {
				submitFeedUrl({
					button: $start,
					action: 'ssp_start_onboarding_import',
					fallback: i18n.startFailed,
					onSuccess: runImport
				});
			});

			$import.find('.js-ssp-import-back').on('click', function (e) {
				e.preventDefault();
				showStage('url');
			});

			$import.find('.js-ssp-import-retry').on('click', runImport);
		},
		init = function(){
			initDeleteImgInfo();
			initImgPreview();
			initTokenValidation();
			initOnboardingValidation();
			initHostingConnectionSteps();
			initDragableImage();
			initFeedImport();
		}


	init();
});
