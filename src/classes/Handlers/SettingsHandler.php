<?php

namespace SeriouslySimplePodcasting\Handlers;

class SettingsHandler {

	public function __construct() {
		// Add settings page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
	}

	/**
	 * Add settings page to menu
	 *
	 * @return void
	 */
	public function add_menu_item() {
		add_submenu_page(
			'edit.php?post_type=podcast',
			__( 'Podcast Settings', 'seriously-simple-podcasting' ),
			__( 'Settings', 'seriously-simple-podcasting' ),
			'manage_podcast',
			'podcast_settings',
			array(
				$this,
				'settings_page',
			)
		);

		add_submenu_page(
			'edit.php?post_type=podcast',
			__( 'Extensions', 'seriously-simple-podcasting' ),
			__( 'Extensions', 'seriously-simple-podcasting' ),
			'manage_podcast',
			'podcast_settings&tab=extensions',
			array(
				$this,
				'settings_page',
			)
		);

		add_submenu_page(
			null,
			__( 'Upgrade', 'seriously-simple-podcasting' ),
			__( 'Upgrade', 'seriously-simple-podcasting' ),
			'manage_podcast',
			'upgrade',
			array(
				$this,
				'show_upgrade_page',
			)
		);
	}

}
