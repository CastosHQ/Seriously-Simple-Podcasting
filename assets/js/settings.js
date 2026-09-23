/**
 * SSP settings functions
 * Created by Jonathan Bossenger on 2017/01/20.
 * Updated by Serhiy Zakharchenko from 2021
 */

jQuery(document).ready(function($) {
	var $podmotorAccountAPIToken = $("#podmotor_account_api_token"),
		$parentCategories = $('.js-parent-category');

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
	  var $connectBtn = $(".castos-connect"),
	  disableConnectButton = function(){
		  $connectBtn.prop("disabled", "disabled");
	  },
	  enableConnectButton = function(){
		  $connectBtn.prop("disabled", "").removeClass('disabled');
	  },
	  connectButtonStates = function () {
		  if ($podmotorAccountAPIToken.length) {
			  $connectBtn.show();
		  }
		  $podmotorAccountAPIToken.on("focus change paste keydown keyup", function () {
			  $podmotorAccountAPIToken.val() ? enableConnectButton() : disableConnectButton();
		  });

		  $podmotorAccountAPIToken.on("focus", function(){
			  $('.connect-castos-message').html('').removeClass('error');
		  });
	  },
	  /**
	   * Validate the api credentials
	   */
	  initConnect = function () {
		  $connectBtn.on('click', function (e) {
			  e.preventDefault();
			  e.stopPropagation();
			  
			  if ($connectBtn.hasClass('disabled')) {
				  return;
			  }

			  $connectBtn.prop('disabled', 'disabled');
			  $connectBtn.trigger('connecting');

			  var podmotor_account_api_token = $('#podmotor_account_api_token').val(),
				  nonce = $('#podcast_settings_tab_nonce').val(),
				  $msg = $('.connect-castos-message');

			  if ($msg.length) {
				  $msg.html('').removeClass('error');
			  } else {
				  $msg = $('<span class="connect-castos-message"></span>');
				  $connectBtn.parent().append($msg);
			  }

			  $connectBtn.addClass('loader');

			  $.ajax({
				  method: 'GET',
				  url: ajaxurl,
				  data: {
					  action: 'connect_castos',
					  api_token: podmotor_account_api_token,
					  nonce: nonce,
				  },
			  })
			  .done(function (response) {
				  $connectBtn.trigger('connected', response);
				  if (response.status === 'success') {
					  $connectBtn.addClass('connected');
					  if ( ! $connectBtn.data( 'no-reload' ) ) {
						  window.location.reload();
					  } else {
						  $msg.html( response.message );
					  }
				  } else {
					  $connectBtn.removeClass('loader');
					  $msg.addClass('error');
					  $msg.html(response.message);
				  }
			  })
		  })
	  },
	  /**
		* Disconnect Castos checkbox on change, renders a confirmation message to the user.
		*/
	  initDisconnect = function () {
		  var $disconnect = $('#disconnect_castos');
		  $disconnect.on('click', function (event) {
			  var $message = __( 'If you disconnect from Castos hosting you will no longer be able to upload media files to the Castos hosting platform. If you’re no longer a Castos customer your media files may no longer be available to your listeners.', 'seriously-simple-podcasting' );
			  var user_input = confirm($message);
			  if (user_input === true) {
				  $disconnect.addClass('loader');
				  $disconnect.parent().find('label').remove();
				  $.ajax({
					  method: 'GET',
					  url: ajaxurl,
					  data: {
						  action: 'disconnect_castos',
						  nonce:  $('#podcast_settings_tab_nonce').val(),
					  },
				  })
					  .done(function (response) {
						  window.location.reload();
					  })
			  }
		  });
	  }

		if ($podmotorAccountAPIToken.length > 0) {
			connectButtonStates();
			initConnect();
		}

		initDisconnect();
	}

	function initSubcategoryFiltration(){
		if ($parentCategories.length) {
			$parentCategories.each(filterSubcategoryGroups);
			$parentCategories.on('change', filterSubcategoryGroups);
		}
	}

	initCastosAPICredentials();
	initSubcategoryFiltration();
});
