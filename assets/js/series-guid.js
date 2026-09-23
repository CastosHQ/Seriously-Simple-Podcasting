/**
 * Series GUID generation behavior.
 */
(function () {
	'use strict';

	var modalSelector = '[data-ssp-modal]';
	var generateGuidButtonSelector = '[data-ssp-generate-series-guid]';
	var generateGuidSpinnerSelector = '[data-ssp-generate-series-guid-spinner]';
	var generateGuidErrorSelector = '[data-ssp-generate-series-guid-error]';
	var seriesGuidValueSelector = '[data-ssp-series-guid-value]';
	var generateGuidCurrentSelector = '[data-ssp-generate-series-guid-current]';
	var generateGuidControlSelector = '[data-ssp-generate-series-guid-control]';
	var generateGuidSuccessSelector = '[data-ssp-generate-series-guid-success]';

	function getGenerateGuidConfig() {
		return window.ssp_series_guid || null;
	}

	function setGenerateGuidSaving( button, isSaving ) {
		var spinner = button.querySelector( generateGuidSpinnerSelector );

		button.disabled = isSaving;
		button.setAttribute( 'aria-busy', isSaving ? 'true' : 'false' );

		if ( ! spinner ) {
			return;
		}

		if ( isSaving ) {
			spinner.classList.add( 'is-active' );
		} else {
			spinner.classList.remove( 'is-active' );
		}
	}

	function clearGenerateGuidError( modal ) {
		var error = modal.querySelector( generateGuidErrorSelector );
		if ( ! error ) {
			return;
		}

		error.textContent = '';
		error.hidden = true;
	}

	function showGenerateGuidError( modal, message ) {
		var error = modal.querySelector( generateGuidErrorSelector );
		if ( ! error ) {
			return;
		}

		error.textContent = message;
		error.hidden = false;
	}

	function getGenerateGuidErrorMessage( response, config ) {
		var defaultMessage = config && config.error_message ? config.error_message : 'The podcast GUID could not be saved. Please try again.';

		if ( ! response ) {
			return defaultMessage;
		}

		if ( 'string' === typeof response.data && response.data ) {
			return response.data;
		}

		if ( response.data && 'string' === typeof response.data.message && response.data.message ) {
			return response.data.message;
		}

		if ( 'string' === typeof response.message && response.message ) {
			return response.message;
		}

		return defaultMessage;
	}

	function updateGeneratedGuid( modal, button, guid, config ) {
		var row = button.closest( '.term-series-guid-wrap' );
		var fieldGuid;
		var modalGuid;
		var control;
		var successMessage;

		if ( ! row ) {
			return false;
		}

		fieldGuid = row.querySelector( seriesGuidValueSelector );
		modalGuid = modal.querySelector( generateGuidCurrentSelector );
		control = row.querySelector( generateGuidControlSelector );
		successMessage = row.querySelector( generateGuidSuccessSelector );

		if ( fieldGuid ) {
			fieldGuid.textContent = guid;
		}
		if ( modalGuid ) {
			modalGuid.textContent = guid;
		}
		if ( successMessage ) {
			successMessage.textContent = config.saved_message || '✓ New GUID saved.';
			successMessage.hidden = false;
		}

		window.sspModal.close( modal );

		if ( control ) {
			control.hidden = true;
		}

		return true;
	}

	function generateSeriesGuid( button ) {
		var modal = button.closest( modalSelector );
		var config = getGenerateGuidConfig();
		var seriesId = button.getAttribute( 'data-series-id' );
		var request;

		if ( ! modal || button.disabled ) {
			return;
		}

		if ( ! config || ! config.ajax_url || ! config.nonce || ! seriesId ) {
			showGenerateGuidError( modal, getGenerateGuidErrorMessage( null, config ) );
			return;
		}

		clearGenerateGuidError( modal );
		setGenerateGuidSaving( button, true );

		request = new window.XMLHttpRequest();
		request.open( 'POST', config.ajax_url, true );
		request.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8' );
		request.onload = function () {
			var response = null;

			try {
				response = JSON.parse( request.responseText );
			} catch ( error ) {
				response = null;
			}

			setGenerateGuidSaving( button, false );

			if ( request.status >= 200 && request.status < 300 && response && true === response.success && response.data && 'string' === typeof response.data.guid && response.data.guid ) {
				if ( updateGeneratedGuid( modal, button, response.data.guid, config ) ) {
					return;
				}
			}

			showGenerateGuidError( modal, getGenerateGuidErrorMessage( response, config ) );
		};
		request.onerror = function () {
			setGenerateGuidSaving( button, false );
			showGenerateGuidError( modal, getGenerateGuidErrorMessage( null, config ) );
		};
		request.send(
			'action=' + encodeURIComponent( config.action || 'ssp_generate_series_guid' ) +
			'&nonce=' + encodeURIComponent( config.nonce ) +
			'&series_id=' + encodeURIComponent( seriesId )
		);
	}

	function init() {
		document.addEventListener( 'click', function ( event ) {
			var generateGuidButton = event.target.closest ? event.target.closest( generateGuidButtonSelector ) : null;
			if ( generateGuidButton ) {
				event.preventDefault();
				generateSeriesGuid( generateGuidButton );
			}
		} );

		document.addEventListener( 'ssp-modal-open', function ( event ) {
			clearGenerateGuidError( event.target );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}());
