<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Interfaces\Service;

/**
 * Manages archive page lifecycle — creation, lookup, routing, and redirects.
 *
 * @package Seriously Simple Podcasting
 * @since 3.15.0
 */
class Archive_Page_Handler implements Service {

	/**
	 * Option name for the podcast archive page ID.
	 */
	const OPTION_PODCAST_PAGE_ID = 'ss_podcasting_podcast_page_id';

	/**
	 * Default slug for the podcast archive page.
	 */
	const PODCAST_ARCHIVE_SLUG = 'ssp-podcast-archive';

	/**
	 * Option name for the archive page notice dismissed flag.
	 */
	const OPTION_NOTICE_DISMISSED = 'ssp_podcast_page_notice_dismissed';

	/**
	 * Returns the podcast archive page ID, or 0 if not set.
	 *
	 * @since 3.15.0
	 *
	 * @return int
	 */
	public function get_podcast_page_id() {
		return (int) get_option( self::OPTION_PODCAST_PAGE_ID );
	}

	/**
	 * Checks whether the podcast archive page is assigned and published.
	 *
	 * @since 3.15.0
	 *
	 * @return bool
	 */
	public function is_podcast_page_assigned() {
		return $this->is_page_assigned( self::OPTION_PODCAST_PAGE_ID );
	}

	/**
	 * Checks whether the option points to a valid published page.
	 *
	 * @since 3.15.0
	 *
	 * @param string $page_id_option Option name that stores the page ID.
	 *
	 * @return bool
	 */
	public function is_page_assigned( $page_id_option ) {
		$page_id = (int) get_option( $page_id_option );

		if ( ! $page_id ) {
			return false;
		}

		$page = get_post( $page_id );

		return $page && 'page' === $page->post_type && 'publish' === $page->post_status;
	}

	/**
	 * Finds an existing page by slug, including trashed pages.
	 *
	 * Pure lookup — does not modify the page or any options.
	 *
	 * @since 3.15.0
	 *
	 * @param string $slug Page slug to search for.
	 *
	 * @return int Page ID, or 0 if not found.
	 */
	public function find_page( $slug ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );

		// Search for trashed page (WordPress appends __trashed to slug).
		if ( ! $page ) {
			$page = get_page_by_path( $slug . '__trashed', OBJECT, 'page' );
		}

		return $page ? (int) $page->ID : 0;
	}

	/**
	 * Creates a new WordPress page and stores its ID in the given option.
	 *
	 * @since 3.15.0
	 *
	 * @param string $page_id_option Option name to store the page ID.
	 * @param string $slug          Page slug.
	 * @param string $page_title    Page title.
	 * @param string $page_content  Page content (block markup).
	 *
	 * @return int Page ID, or 0 on failure.
	 */
	public function create_page( $page_id_option, $slug, $page_title, $page_content ) {
		$page_id = wp_insert_post( array(
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => get_current_user_id() ?: 1,
			'post_name'      => $slug,
			'post_title'     => $page_title,
			'post_content'   => $page_content,
			'comment_status' => 'closed',
		) );

		if ( ! $page_id || is_wp_error( $page_id ) ) {
			return 0;
		}

		update_option( $page_id_option, (int) $page_id );

		return (int) $page_id;
	}

	/**
	 * Replaces the main query with the assigned archive page when the request
	 * is the podcast CPT archive.
	 *
	 * Fires on `template_redirect`, before WP's tag-template dispatch — swapping
	 * `is_post_type_archive` → `is_page` causes core to naturally resolve the
	 * page template (via `get_page_template()`), which handles block themes,
	 * classic themes, and `_wp_page_template` meta out of the box.
	 *
	 * @since 3.15.0
	 *
	 * @return void
	 */
	public function maybe_serve_archive_page() {
		if ( ! is_post_type_archive( SSP_CPT_PODCAST ) ) {
			return;
		}

		$page_id = $this->get_podcast_page_id();

		if ( ! $page_id ) {
			return;
		}

		$page = get_post( $page_id );

		if ( ! $page || 'page' !== $page->post_type || 'publish' !== $page->post_status ) {
			return;
		}

		global $wp_query;

		$wp_query->posts             = array( $page );
		$wp_query->post_count        = 1;
		$wp_query->found_posts       = 1;
		$wp_query->max_num_pages     = 1;
		$wp_query->post              = $page;
		$wp_query->queried_object    = $page;
		$wp_query->queried_object_id = $page_id;

		$wp_query->is_page              = true;
		$wp_query->is_singular          = true;
		$wp_query->is_archive           = false;
		$wp_query->is_post_type_archive = false;

		$wp_query->rewind_posts();

		wp_enqueue_style( 'ssp-podcast-list' );
	}

	/**
	 * Redirects the archive page's own URL to the podcast archive URL.
	 *
	 * The archive page uses a non-public slug (e.g. "ssp-podcast-archive") to avoid
	 * conflicts with the CPT archive rewrite rules. If someone visits the page directly,
	 * redirect them to the canonical /podcast/ URL.
	 *
	 * @since 3.15.0
	 *
	 * @return void
	 */
	public function maybe_redirect_archive_page_to_podcast_url() {
		$page_id = $this->get_podcast_page_id();

		if ( ! $page_id || ! is_page( $page_id ) ) {
			return;
		}

		$archive_url = get_post_type_archive_link( SSP_CPT_PODCAST );

		if ( $archive_url ) {
			wp_safe_redirect( $archive_url, 302 );
			exit;
		}
	}

	/**
	 * Returns the default content for the podcast archive page.
	 *
	 * Uses the Gutenberg block when the block editor is available,
	 * falls back to the [ssp_episode_list] shortcode for Classic Editor.
	 *
	 * @since 3.15.0
	 *
	 * @return string
	 */
	protected function get_podcast_archive_page_content() {
		if ( function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( 'page' ) ) {
			$content = '<!-- wp:seriously-simple-podcasting/podcast-list {"featuredImage":false,"excerpt":true,"player":true,"titleSize":"24"} /-->';
		} else {
			$content = '[ssp_episode_list display_image="false" display_excerpt="true" display_player="true" title_size="24"]';
		}

		/**
		 * Filters the default content for newly created podcast archive pages.
		 *
		 * @since 3.15.0
		 *
		 * @param string $content Default block markup or shortcode.
		 */
		return apply_filters( 'ssp_podcast_archive_page_content', $content );
	}

	/**
	 * Finds or creates the podcast archive page.
	 *
	 * @since 3.15.0
	 *
	 * @return int Page ID, or 0 on failure.
	 */
	public function create_podcast_archive_page() {
		if ( $this->is_podcast_page_assigned() ) {
			return $this->get_podcast_page_id();
		}

		// Only find/create when no page was ever assigned.
		// A stale option (trashed/deleted page) means the user had a page — don't override their choice.
		if ( get_option( self::OPTION_PODCAST_PAGE_ID ) ) {
			return 0;
		}

		return $this->find_or_create_podcast_page();
	}

	/**
	 * Creates the podcast archive page on explicit user request.
	 *
	 * Unlike create_podcast_archive_page(), this method bypasses the stale-option
	 * guard — the user clicked "Set up now" and expects a page to be created.
	 * Restores the previous option value if creation fails.
	 *
	 * @since 3.15.0
	 *
	 * @return int Page ID, or 0 on failure.
	 */
	public function setup_podcast_archive_page() {
		if ( $this->is_podcast_page_assigned() ) {
			return $this->get_podcast_page_id();
		}

		$old_page_id = get_option( self::OPTION_PODCAST_PAGE_ID );

		delete_option( self::OPTION_PODCAST_PAGE_ID );

		$page_id = $this->find_or_create_podcast_page();

		if ( ! $page_id && $old_page_id ) {
			update_option( self::OPTION_PODCAST_PAGE_ID, $old_page_id );
		}

		return $page_id;
	}

	/**
	 * Handles the "Set up now" action from the archive page notice.
	 *
	 * Creates the archive page and returns a result array for the caller
	 * to translate into a flash notice and redirect.
	 *
	 * @since 3.15.0
	 *
	 * @return array{success: bool, message: string, redirect: string}
	 */
	public function handle_setup_action() {
		$page_id = $this->setup_podcast_archive_page();

		if ( ! $page_id ) {
			return array(
				'success'  => false,
				'message'  => __( 'Could not create the episodes page. Please try again or create one manually.', 'seriously-simple-podcasting' ),
				'redirect' => wp_get_referer() ?: admin_url(),
			);
		}

		return array(
			'success'  => true,
			'message'  => sprintf(
				/* translators: %s: link to edit the page */
				__( 'Your episodes page is ready! You can edit it %s.', 'seriously-simple-podcasting' ),
				'<a href="' . esc_url( get_edit_post_link( $page_id, 'raw' ) ) . '">' . __( 'here', 'seriously-simple-podcasting' ) . '</a>'
			),
			'redirect' => admin_url( 'edit.php?post_type=podcast&page=podcast_settings' ),
		);
	}

	/**
	 * Handles the "Dismiss" action from the archive page notice.
	 *
	 * @since 3.15.0
	 *
	 * @return array{redirect: string}
	 */
	public function handle_dismiss_action() {
		update_option( self::OPTION_NOTICE_DISMISSED, true );

		return array(
			'redirect' => wp_get_referer() ?: admin_url(),
		);
	}

	/**
	 * Checks whether the archive page upgrade notice should be displayed.
	 *
	 * Returns true when no page is assigned and the notice hasn't been dismissed.
	 *
	 * @since 3.15.0
	 *
	 * @return bool
	 */
	public function should_show_archive_page_notice() {
		if ( $this->is_archive_page_notice_dismissed() ) {
			return false;
		}

		return ! $this->is_podcast_page_assigned();
	}

	/**
	 * Checks whether the archive page upgrade notice has been dismissed.
	 *
	 * @since 3.15.0
	 *
	 * @return bool
	 */
	public function is_archive_page_notice_dismissed() {
		return (bool) get_option( self::OPTION_NOTICE_DISMISSED );
	}

	/**
	 * Finds an existing page by slug or creates a new one.
	 *
	 * @since 3.15.0
	 *
	 * @return int Page ID, or 0 on failure.
	 */
	protected function find_or_create_podcast_page() {
		$slug    = apply_filters( 'ssp_podcast_archive_page_slug', self::PODCAST_ARCHIVE_SLUG );
		$page_id = $this->find_page( $slug );

		if ( $page_id ) {
			// Ensure the found page is published — it may be in draft, pending, or trash.
			$page = get_post( $page_id );

			if ( $page && 'publish' !== $page->post_status ) {
				wp_update_post( array(
					'ID'          => $page_id,
					'post_status' => 'publish',
					'post_name'   => $slug,
				) );
			}

			update_option( self::OPTION_PODCAST_PAGE_ID, $page_id );

			$this->fire_page_created_action( $page_id );

			return $page_id;
		}

		$page_id = $this->create_page(
			self::OPTION_PODCAST_PAGE_ID,
			$slug,
			__( 'Episode List', 'seriously-simple-podcasting' ),
			$this->get_podcast_archive_page_content()
		);

		if ( $page_id ) {
			$this->fire_page_created_action( $page_id );
		}

		return $page_id;
	}

	/**
	 * Fires the archive page created action.
	 *
	 * @since 3.15.0
	 *
	 * @param int $page_id Created or assigned page ID.
	 */
	protected function fire_page_created_action( $page_id ) {
		/**
		 * Fires after the podcast archive page has been created or assigned.
		 *
		 * @since 3.15.0
		 *
		 * @param int $page_id The archive page ID.
		 */
		do_action( 'ssp_archive_page_created', $page_id );
	}
}
