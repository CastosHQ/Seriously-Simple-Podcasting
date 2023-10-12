/**
 * SSP settings functions
 * Created by Jonathan Bossenger on 2017/01/20.
 * Updated by Serhiy Zakharchenko from 2021
 */

jQuery(document).ready(function($) {

	var $podmotorAccountEmail = $("#podmotor_account_email"),
		$podmotorAccountAPIToken = $("#podmotor_account_api_token"),
		$parentCategories = $('.js-parent-category'),
		$validateBtn = $("#validate_api_credentials");

	const { __ } = wp.i18n;

	/**
	 * Show only options related to parent category
	 */
	function filterSubcategoryGroups() {
		var $parent = $(this),
			subcategoryID = $parent.data('subcategory'),
			parentCategory = $parent.find('option:selected').text();

		if (!subcategoryID || !parentCategory) return false;

		var $subcategory = $('#' + subcategoryID);
		$subcategory.find('optgroup').hide();
		var $selectedOptgroup = $subcategory.find('optgroup[label="' + parentCategory + '"]');

		if (!$selectedOptgroup.length || '-- None --' === parentCategory) {
			$subcategory.val('');
		} else {
			$selectedOptgroup.show();
		}
	}

	function initCastosAPICredentials() {
		var disableSubmitButton = function () {
				/**
				 * If either API field is empty, disable the submit button
				 */
				if ($podmotorAccountEmail.val() === '' || $podmotorAccountAPIToken.val() === '') {
					$("#ssp-settings-submit").prop("disabled", "disabled");
				}

				/**
				 * If the user changes the email, disable the submit button
				 */
				$podmotorAccountEmail.on("change paste keydown keyup", function () {
					$("#ssp-settings-submit").prop("disabled", "disabled");
				});

				/**
				 * If the user changes the account api key, disable the submit button
				 */
				$podmotorAccountAPIToken.on("change paste keydown keyup", function () {
					$("#ssp-settings-submit").prop("disabled", "disabled");
				});
			},
			/**
			 * Validate the api credentials
			 */
			validateAPICredentials = function () {
				$validateBtn.on("click", function () {

					var podmotor_account_email = $("#podmotor_account_email").val(),
						podmotor_account_api_token = $("#podmotor_account_api_token").val(),
						nonce = $("#podcast_settings_tab_nonce").val(),
						$msg = $('.validate-api-credentials-message');

					if (!$msg.length) {
						$msg = $('<span class="validate-api-credentials-message"></span>');
						$validateBtn.parent().append($msg);
					}

					$msg.html("Validating API credentials...");

					$validateBtn.addClass('loader');

					$.ajax({
						method: "GET",
						url: ajaxurl,
						data: {
							action: "validate_castos_credentials",
							api_token: podmotor_account_api_token,
							email: podmotor_account_email,
							nonce: nonce
						}
					})
						.done(function (response) {
							$validateBtn.removeClass('loader');
							if (response.status === 'success') {
								$(".validate-api-credentials-message").html("Credentials Valid. Please click 'Save Settings' to save Credentials.");
								$("#ssp-settings-submit").prop("disabled", "");
								$validateBtn.val('Valid Credentials');
								$validateBtn.addClass('valid');
							} else {
								$validateBtn.addClass('invalid');
								$(".validate-api-credentials-message").html(response.message);
							}
							$validateBtn.trigger('validated');
						});
				});
			},
			/**
			 * Disconnect Castos checkbox on change, renders a confirmation message to the user.
			 */
			disconnectCastos = function () {
				$('#podmotor_disconnect').on('change', function (event) {
					var $checkbox = $(this);

					// if the change is to uncheck the checkbox
					if (!$checkbox.is(':checked')) {
						return;
					}

					var $message = 'If you disconnect from Castos hosting you will no longer be able to upload media files to the Castos hosting platform. If you’re no longer a Castos customer your media files may no longer be available to your listeners.';
					var user_input = confirm($message);
					if (user_input !== true) {
						// Ensures this code runs AFTER the browser handles click however it wants.
						setTimeout(function () {
							$checkbox.removeAttr('checked');
						}, 0);
						event.preventDefault();
						event.stopPropagation();
					}
				});
			}

		if ($podmotorAccountEmail.length > 0 && $podmotorAccountAPIToken.length > 0) {
			disableSubmitButton();
			validateAPICredentials();
			disconnectCastos();
		}
	}

	function initSubcategoryFiltration(){
		if ($parentCategories.length) {
			$parentCategories.each(filterSubcategoryGroups);
			$parentCategories.on('change', filterSubcategoryGroups);
		}
	}

	function initCastosSync() {
		var $syncBtn = $('#trigger_sync'),
			nonce = $("#podcast_settings_tab_nonce").val(),
			syncClass = '.js-sync-podcast',
			changeStatus = function ($el, status, title = '') {
				var $statusEl = $el.closest(syncClass).find('.js-sync-status');
				$statusEl.removeClass('synced_with_errors success none sending failed').addClass(status);
				if(title){
					$statusEl.find('span').html(title);
				}
			},
			getCheckedPodcasts = function(){
				return $(syncClass + ' input[type=checkbox]:checked');
			},
			getPodcastCheckboxes = function(){
				return $(syncClass + ' input[type=checkbox]');
			},
			updateSyncBtn = function(){
				$syncBtn.prop('disabled', getCheckedPodcasts().length === 0);
			}

		if (!$syncBtn.length) {
			return false;
		}
		updateSyncBtn();

		getPodcastCheckboxes().change(function(){
			updateSyncBtn();
		});

		$syncBtn.click(function(){
			$syncBtn.addClass('loader');

			var $msg = $('.ssp-sync-msg'),
				$checked = getCheckedPodcasts(),
				podcasts = [];

			if (!$msg.length) {
				$msg = $('<span class="ssp-sync-msg"></span>');
				$syncBtn.parent().append($msg);
			}

			$checked.each(function () {
				podcasts.push($(this).val());
			});

			$.ajax({
				method: "GET",
				url: ajaxurl,
				data: {
					action: "sync_castos",
					nonce: nonce,
					podcasts: podcasts
				}
			}).done(function (response) {
				var msg = '<div class="sync-overview">' + response.data.msg + '</div>';
				$.each(response.data.podcasts, function (id, status) {
					changeStatus($('#podcasts_sync_' + id), status.status, status.title);
					msg += '<div class="sync-msg">' + status.msg + '</div>';
				});

				$syncBtn.removeClass('loader');
				$msg.addClass(response.success ? 'success' : 'error');

				$msg.html(msg);
			});
		});
	}

	initCastosAPICredentials();
	initSubcategoryFiltration();
	initCastosSync();
});
