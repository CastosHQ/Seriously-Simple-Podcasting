/**
 * Shared SSP admin modal behavior.
 */
(function () {
	'use strict';

	var modalSelector = '[data-ssp-modal]';
	var modalOpenerSelector = '[data-ssp-modal-open]';
	var modalCloseSelector = '[data-ssp-modal-close]';

	function getClosest( element, selector ) {
		if ( ! element || 1 !== element.nodeType || ! element.closest ) {
			return null;
		}

		return element.closest( selector );
	}

	function getModalFromOpener( opener ) {
		var modalId = opener.getAttribute( 'data-ssp-modal-open' );
		if ( ! modalId ) {
			return null;
		}

		if ( '#' === modalId.charAt( 0 ) ) {
			modalId = modalId.slice( 1 );
		}

		var modal = document.getElementById( modalId );
		return modal && modal.hasAttribute( 'data-ssp-modal' ) ? modal : null;
	}

	function isModalOpen( modal ) {
		return modal.hasAttribute( 'open' );
	}

	function setOpenerExpanded( opener, expanded ) {
		if ( opener && opener.hasAttribute( 'aria-expanded' ) ) {
			opener.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
		}
	}

	function restoreFocus( modal ) {
		var opener = modal.sspModalOpener;

		setOpenerExpanded( opener, false );
		if ( opener && document.contains( opener ) ) {
			opener.focus();
		}

		modal.sspModalOpener = null;
	}

	function bindModal( modal ) {
		if ( modal.sspModalBound ) {
			return;
		}

		modal.addEventListener( 'close', function () {
			restoreFocus( modal );
		} );
		modal.addEventListener( 'cancel', function ( event ) {
			event.preventDefault();
			closeModal( modal );
		} );
		modal.sspModalBound = true;
	}

	function openModal( opener ) {
		var modal = getModalFromOpener( opener );
		if ( ! modal || isModalOpen( modal ) ) {
			return;
		}

		bindModal( modal );
		modal.sspModalOpener = opener;
		setOpenerExpanded( opener, true );

		if ( 'function' === typeof modal.showModal ) {
			modal.showModal();
		} else {
			modal.setAttribute( 'open', '' );
		}

		var closeButton = modal.querySelector( modalCloseSelector );
		if ( closeButton ) {
			closeButton.focus();
		}

		modal.dispatchEvent( new window.CustomEvent( 'ssp-modal-open', { bubbles: true } ) );
	}

	function closeModal( modal ) {
		if ( ! modal || ! isModalOpen( modal ) ) {
			return;
		}

		if ( 'function' === typeof modal.close ) {
			modal.close();
			return;
		}

		modal.removeAttribute( 'open' );
		restoreFocus( modal );
	}

	function getOpenModal() {
		var modals = document.querySelectorAll( modalSelector + '[open]' );
		return modals.length ? modals[ modals.length - 1 ] : null;
	}

	function isOutsideModal( event, modal ) {
		var rect = modal.getBoundingClientRect();
		return event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom;
	}

	function init() {
		document.addEventListener( 'click', function ( event ) {
			var closeButton = getClosest( event.target, modalCloseSelector );
			if ( closeButton ) {
				var closeModalElement = getClosest( closeButton, modalSelector );
				if ( closeModalElement ) {
					event.preventDefault();
					closeModal( closeModalElement );
				}
				return;
			}

			var opener = getClosest( event.target, modalOpenerSelector );
			if ( opener ) {
				event.preventDefault();
				openModal( opener );
				return;
			}

			var modal = getOpenModal();
			if ( modal && ( event.target === modal ? isOutsideModal( event, modal ) : ! modal.contains( event.target ) ) ) {
				closeModal( modal );
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' !== event.key && 27 !== event.which ) {
				return;
			}

			var modal = getOpenModal();
			if ( modal ) {
				event.preventDefault();
				closeModal( modal );
			}
		} );
	}

	window.sspModal = {
		close: closeModal
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}());
