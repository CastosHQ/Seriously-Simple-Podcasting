jQuery( document ).ready( function( $ ) {
	var initOnboardingMenu = function() {
		var $firstMenuItem = $( '#menu-posts-podcast' ).find( '.wp-submenu a.wp-first-item' );

		if ( $firstMenuItem.length && $firstMenuItem.attr( 'href' ).includes( 'page=ssp-onboarding-1' ) ) {
			$firstMenuItem.closest('ul').find('li').not('.wp-first-item').hide();
		}
	}

	initOnboardingMenu();
} );
