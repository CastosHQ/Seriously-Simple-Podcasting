/**
 * Castos sync on the Settings → Hosting page: Trigger Sync, refusal confirmation and status polling.
 */
jQuery( document ).ready(function ($) {
	var __ = wp.i18n.__;

	function initCastosSync() {
		var $syncBtn = $( '#trigger_sync' ),
			nonce = $( '#podcast_settings_tab_nonce' ).val(),
			syncClass = '.js-sync-podcast',
			syncLabelSelector = '.ssp-sync-refusal, .ssp-sync-refusal-terminal, .js-sync-status',
			pollInterval = 15 * 1000,
			pollTimeout = 10 * 60 * 1000,
			pollTimer,
			pollTimeoutTimer,
			pollRequest,
			syncRequest,
			pollLive = false,
			getCheckedPodcasts = function () {
				return $( syncClass + ' input[type=checkbox]:checked' );
			},
			getPodcastCheckboxes = function () {
				return $( syncClass + ' input[type=checkbox]' );
			},
			getSyncRow = function (seriesId) {
				return $( '#podcasts_sync_' + seriesId ).closest( syncClass );
			},
			isRowSyncing = function ($row) {
				return $row.find( '.js-sync-status' ).hasClass( 'syncing' );
			},
			getSyncingSeriesIds = function () {
				return $( syncClass ).filter(function () {
					return isRowSyncing( $( this ) );
				}).find( 'input[type=checkbox]' ).map(function () {
					return this.value;
				}).get();
			},
			replaceSyncLabel = function (seriesId, statusHtml) {
				var $row = getSyncRow( seriesId ),
					$label = $row.children( syncLabelSelector ).first(),
					hadFocus = $label.is( document.activeElement ) || $label.has( document.activeElement ).length > 0,
					$focusTarget;

				if ( ! $label.length || ! statusHtml ) {
					return;
				}

				$label.replaceWith( statusHtml );

				// Keep keyboard focus in the row when the focused badge is replaced.
				if ( hadFocus ) {
					$focusTarget = $row.children( syncLabelSelector ).find( 'button, a' ).addBack( 'button, a' ).first();
					( $focusTarget.length ? $focusTarget : $row.find( 'input[type=checkbox]' ).first() ).trigger( 'focus' );
				}
			},
			getSyncMessageBox = function () {
				var $msg = $( '.ssp-sync-msg' );

				if ( ! $msg.length ) {
					$msg = $( '<span class="ssp-sync-msg"></span>' );
					$syncBtn.parent().append( $msg );
				}

				return $msg;
			},
			getSyncErrorMessage = function (response) {
				if ( response && 'string' === typeof response.data && response.data ) {
					return response.data;
				}
				if ( response && 'string' === typeof response.message && response.message ) {
					return response.message;
				}

				return __( 'Could not start the sync. Please try again.', 'seriously-simple-podcasting' );
			},
			showSyncError = function (response) {
				getSyncMessageBox().removeClass( 'success' ).addClass( 'error' ).empty().append(
					$( '<div class="sync-overview"></div>' ).text( getSyncErrorMessage( response ) )
				);
			},
			removeSyncRefreshMessage = function () {
				var $message = $( '.ssp-sync-msg' );

				$message.children( '.sync-overview' ).remove();
				if ( ! $message.children().length ) {
					$message.remove();
				}
			},
			requestSync = function (seriesIds, confirmAction) {
				var data = {
					action: 'sync_castos',
					nonce: nonce,
					podcasts: seriesIds
				};

				// One sync request at a time, so a reopened modal cannot send Connect twice.
				if ( syncRequest ) {
					return;
				}
				if ( confirmAction ) {
					data.confirm_action = confirmAction;
				}

				$syncBtn.addClass( 'loader' );
				syncRequest = $.ajax({
					method: 'GET',
					url: ajaxurl,
					data: data
				}).done(function (response) {
					var result = response ? response.data : null,
						msg;

					if ( ! result || 'object' !== typeof result ) {
						showSyncError( response );
						return;
					}

					// Server-built messages escape any Castos text.
					msg = '<div class="sync-overview">' + result.msg + '</div>';
					$.each( result.podcasts || {}, function (id, status) {
						replaceSyncLabel( id, status.html );
						if ( status.msg ) {
							msg += '<div class="sync-msg">' + status.msg + '</div>';
						}
					});

					getSyncMessageBox().removeClass( 'success error' ).addClass( response.success ? 'success' : 'error' ).html( msg );
					startSyncStatusPolling( true );
				}).fail(function (xhr) {
					showSyncError( xhr.responseJSON );
				}).always(function () {
					syncRequest = null;
					$syncBtn.removeClass( 'loader' );
				});
			},
			stopSyncStatusPolling = function () {
				window.clearInterval( pollTimer );
				window.clearTimeout( pollTimeoutTimer );
				pollTimer = null;
				pollTimeoutTimer = null;
				pollLive = false;
			},
			pollSyncStatuses = function () {
				var seriesIds = getSyncingSeriesIds();

				if ( ! seriesIds.length ) {
					stopSyncStatusPolling();
					return;
				}
				if ( pollRequest ) {
					return;
				}

				pollRequest = $.ajax({
					method: 'GET',
					url: ajaxurl,
					data: {
						action: 'ssp_get_series_sync_statuses',
						nonce: nonce,
						series: seriesIds,
						live: pollLive ? 1 : 0
					}
				}).done(function (response) {
					var seriesStatuses = response && response.success && response.data ? response.data.series : null;

					$.each( seriesStatuses || {}, function (id, status) {
						var $row = getSyncRow( id ),
							wasSyncing = isRowSyncing( $row );

						replaceSyncLabel( id, status.html );
						if ( wasSyncing && 'syncing' !== status.status ) {
							$row.find( 'input[type=checkbox]' ).prop( 'checked', false );
							updateSyncBtn();
							removeSyncRefreshMessage();
						}
					});

					if ( ! getSyncingSeriesIds().length ) {
						stopSyncStatusPolling();
					}
				}).always(function () {
					pollRequest = null;
				});
			},
			startSyncStatusPolling = function (live) {
				if ( ! getSyncingSeriesIds().length ) {
					stopSyncStatusPolling();
					return;
				}

				pollLive = pollLive || live;
				if ( ! pollTimer ) {
					pollTimer = window.setInterval( pollSyncStatuses, pollInterval );
				}
				// A new sync restarts the 10-minute limit.
				if ( live || ! pollTimeoutTimer ) {
					window.clearTimeout( pollTimeoutTimer );
					pollTimeoutTimer = window.setTimeout( stopSyncStatusPolling, pollTimeout );
				}
				if ( live ) {
					pollSyncStatuses();
				}
			},
			updateSyncBtn = function () {
				$syncBtn.prop( 'disabled', getCheckedPodcasts().length === 0 );
			};

		if ( ! $syncBtn.length ) {
			return false;
		}
		updateSyncBtn();

		getPodcastCheckboxes().on( 'change', function () {
			updateSyncBtn();
		});

		$syncBtn.on( 'click', function () {
			var seriesIds = getCheckedPodcasts().map(function () {
				return this.value;
			}).get();

			requestSync( seriesIds );
		});

		$( document ).on( 'click', '.js-sync-refusal-connect', function (event) {
			event.preventDefault();
			requestSync( [ $( this ).closest( syncClass ).find( 'input[type=checkbox]' ).val() ], 'connect' );
		});

		if ( getSyncingSeriesIds().length ) {
			startSyncStatusPolling( false );
		}
	}

	initCastosSync();
});
