<?php

namespace SeriouslySimplePodcasting\Handlers;

/**
 * SSP Roles Handler
 *
 * @package Seriously Simple Podcasting
 */
class Roles_Handler {

	const PODCAST_EDITOR = 'podcast_editor';

	const PODCAST_MANAGER = 'podcast_manager';

	const MANAGE_PODCAST = 'manage_podcast';


	public function __construct() {
		add_action( 'admin_init', array( $this, 'manage_default_roles' ), 1 );

		// Adds podcast roles
		add_filter( 'admin_init', array( $this, 'add_podcast_editor_role' ) );
		add_filter( 'admin_init', array( $this, 'add_podcast_manager_role' ) );

		// Allows manage_podcast capability to save podcast settings
		add_filter( 'option_page_capability_ss_podcasting', function () {
			return self::MANAGE_PODCAST;
		} );
	}


	/**
	 * Removes custom roles when the plugin is deactivated
	 * */
	public static function remove_custom_roles() {
		remove_role( self::PODCAST_EDITOR );
		remove_role( self::PODCAST_MANAGER );
	}

	/**
	 * Adds podcast_editor role for managing the episodes
	 */
	public function add_podcast_editor_role() {
		$role_title = __( 'Podcast Editor', 'seriously-simple-podcasting' );
		$this->maybe_add_podcast_role( self::PODCAST_EDITOR, $role_title );
	}

	/**
	 * Adds podcast_manager role for managing the episodes and podcast settings
	 */
	public function add_podcast_manager_role() {
		$role_title = __( 'Podcast Manager', 'seriously-simple-podcasting' );
		$additional_caps = array(
			self::MANAGE_PODCAST => true,
			'manage_podcast_tax' => true,
		);
		$this->maybe_add_podcast_role( self::PODCAST_MANAGER, $role_title, $additional_caps );
	}

	/**
	 * Adds role if it doesn't exist yet
	 *
	 * @param string $role
	 * @param string $role_title
	 * @param array $additional_caps
	 *
	 * @return \WP_Role|null
	 */
	protected function maybe_add_podcast_role( $role, $role_title, $additional_caps = array() ) {
		if ( $this->role_exists( $role ) ) {
			return null;
		}

		// capabilities to get to the admin area and upload files
		$initial_caps = array(
			'read'         => true,
			'upload_files' => true,
		);

		$podcast_caps = $this->get_podcast_capabilities();

		// prepare capabilities to the array('capability' => true) structure
		$podcast_caps = array_map( function () {
			return true;
		}, array_flip( $podcast_caps ) );

		$caps = array_merge( $initial_caps, $podcast_caps, $additional_caps );

		$caps = apply_filters( 'ssp_podcast_role_capabilities', $caps, $role );

		return add_role( $role, $role_title, $caps );
	}

	/**
	 * Add capabilities to edit podcast settings to admins and editors.
	 */
	public function manage_default_roles() {
		// Roles you'd like to have administer the podcast settings page.
		$podcast_managers = apply_filters( 'ssp_manage_podcast', array( 'editor', 'administrator' ) );

		// Loop through each role and assign capabilities.
		foreach ( $podcast_managers as $the_role ) {

			if ( ! $this->role_exists( $the_role ) ) {
				continue;
			}

			$role = get_role( $the_role );

			$caps     = $this->get_podcast_capabilities();
			$tax_caps = $this->get_podcast_tax_capabilities();
			$caps     = array_merge( $caps, $tax_caps );

			// add the possibility to manage the podcast settings
			$caps[ self::MANAGE_PODCAST ] = self::MANAGE_PODCAST;

			$caps = array_unique( $caps );
			$caps = apply_filters( 'ssp_podcast_manager_capabilities', $caps, $the_role );

			foreach ( $caps as $cap ) {
				$this->maybe_add_cap( $role, $cap );
			}
		}
	}

	/**
	 * Check to see if the given role has a cap, and add if it doesn't exist.
	 *
	 * @param  \WP_Role $role User Cap object, part of WP_User.
	 * @param  string $cap Cap to test against.
	 *
	 * @return void
	 */
	public function maybe_add_cap( $role, $cap ) {
		if ( ! $role->has_cap( $cap ) ) {
			$role->add_cap( $cap );
		}
	}


	/**
	 * This function provides a podcast CPT capabilities
	 *
	 * @return array
	 * */
	public function get_podcast_capabilities() {
		return array(
			'edit_post'              => 'edit_podcast',
			'read_post'              => 'read_podcast',
			'delete_post'            => 'delete_podcast',
			'edit_posts'             => 'edit_podcasts',
			'edit_others_posts'      => 'edit_others_podcasts',
			'publish_posts'          => 'publish_podcasts',
			'read_private_posts'     => 'read_private_podcasts',
			'delete_posts'           => 'delete_podcasts',
			'delete_private_posts'   => 'delete_private_podcasts',
			'delete_published_posts' => 'delete_published_podcasts',
			'delete_others_posts'    => 'delete_others_podcasts',
			'edit_private_posts'     => 'edit_private_podcasts',
			'edit_published_posts'   => 'edit_published_podcasts',
			'create_posts'           => 'create_podcasts',
		);
	}

	/**
	 * This function provides a podcast CPT capabilities
	 *
	 * @return array
	 */
	public function get_podcast_tax_capabilities() {
		$caps = array(
			'manage_terms'  => 'manage_podcast_tax',
			'edit_terms'    => 'manage_podcast_tax',
			'delete_terms'  => 'manage_podcast_tax',
			'assign_terms'  => 'edit_podcast',
		);

		return apply_filters( 'ssp_podcast_tax_capabilities', $caps );
	}


	/**
	 * Checks if a user role exists
	 *
	 * @param $role
	 *
	 * @return bool
	 */
	public function role_exists( $role ) {
		if ( ! empty( $role ) ) {
			return $GLOBALS['wp_roles']->is_role( $role );
		}

		return false;
	}
}
