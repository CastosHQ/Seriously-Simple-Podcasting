<?php

namespace SeriouslySimplePodcasting\Handlers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 *
 * @author      Hugh Lashbrooke
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       1.0
 */
class Admin_Notifications_Handler {

	/**
	 * Admin_Notifications_Handler constructor.
	 */
	public function __construct() {
		$this->bootstrap();
	}

	/**
	 * Class bootstrap, loads all action and filter hooks
	 */
	public function bootstrap() {
		add_action( 'current_screen', array( $this, 'check_existing_podcasts' ) );

		add_action( 'current_screen', array( $this, 'second_line_themes' ) );

		add_action( 'admin_init', array( $this, 'start_importing_existing_podcasts' ) );

		// Check if a valid permalink structure is set and show a message
		add_action( 'admin_init', array( $this, 'check_valid_permalink' ) );

		// Check if the podcast feed category update message needs to trigger
		add_action( 'admin_init', array( $this, 'check_category_update_required' ) );


	}

	/**
	 * Check if there are existing podcasts to be uploaded to Seriously Simple Hosting
	 */
	public function check_existing_podcasts() {
		/**
		 * Only trigger this if we're connected to Seriously Simple Hosting
		 */
		if ( ! ssp_is_connected_to_podcastmotor() ) {
			return;
		}

		/**
		 * Only show this notice on the All Episodes page
		 */
		$current_screen = get_current_screen();
		if ( 'edit-podcast' !== $current_screen->id ) {
			return;
		}

		/**
		 * Only trigger this if the ss_podcasting_podmotor_import_podcasts option hasn't been set
		 */
		$ss_podcasting_podmotor_import_podcasts = get_option( 'ss_podcasting_podmotor_import_podcasts', '' );
		if ( ! empty( $ss_podcasting_podmotor_import_podcasts ) ) {
			return;
		}

		// check if there is at least one podcast to import
		$podcast_query = ssp_get_existing_podcasts();
		if ( $podcast_query->have_posts() ) {
			add_action( 'admin_notices', array( $this, 'existing_podcasts_notice' ) );
		}
	}

	/**
	 * Show 'existing podcast' notice
	 */
	public function existing_podcasts_notice() {
		$podcast_import_url = add_query_arg( array(
			'post_type' => $this->token,
			'page'      => 'podcast_settings',
			'tab'       => 'import'
		) );
		$ignore_message_url = add_query_arg( array( 'podcast_import_action' => 'ignore' ) );
		$message            = '';
		$message            .= '<p>You\'ve connected to your Castos account and you have existing podcasts that can be imported.</p>';
		$message            .= '<p>You can <a href="' . $podcast_import_url . '">import your existing podcasts to Castos.</a></p>';
		$message            .= '<p>Alternatively you can <a href="' . $ignore_message_url . '">dismiss this message.</a></p>';
		?>
		<div class="notice notice-info">
			<p><?php _e( $message, 'ssp' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Setup podcast import
	 */
	public function start_importing_existing_podcasts() {
		if ( isset( $_GET['podcast_import_action'] ) && 'start' == $_GET['podcast_import_action'] ) {
			update_option( 'ss_podcasting_podmotor_import_podcasts', 'true' );
			$castos_handler = new Castos_Handler();
			$reponse          = $castos_handler->insert_podmotor_queue();
			if ( 'success' === $reponse['status'] ) {
				update_option( 'ss_podcasting_podmotor_queue_id', $reponse['queue_id'] );
			}
			add_action( 'admin_notices', array( $this, 'importing_podcasts_notice' ) );
		}
	}

	/**
	 * Show 'importing podcasts' notice
	 */
	public function importing_podcasts_notice() {
		$message = '';
		$message .= '<p>We\'re importing your podcast episodes and media files to Castos now. Check your email for an update when this process is finished</p>';
		$message .= '<p>The import process takes place as a background task, so you may dismiss this message.</p>';
		?>
		<div class="notice notice-info is-dismissible">
			<p><?php _e( $message, 'ssp' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Checks to see if a valid permalink structure is in place
	 */
	public function check_valid_permalink() {
		$permalink_structure = get_option( 'permalink_structure', '' );
		if ( empty( $permalink_structure ) ) {
			add_action( 'admin_notices', array( $this, 'invalid_permalink_structure_notice' ) );
		}
	}

	/**
	 * Show 'invalid permalink structure' notice
	 */
	public function invalid_permalink_structure_notice() {
		$message = '';
		$message .= '<p>You\'ve not set a valid permalink structure. This will affect your Podcast feed url.</p>';
		$message .= '<p>Please set a permalink structure in the <em>\'Settings -> Permalinks\'</em> admin menu.</p>';
		?>
		<div class="notice notice-info is-dismissible">
			<p><?php _e( $message, 'ssp' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Checks to see if we're on a version higher than 1.20.6
	 */
	public function check_category_update_required() {
		// check if the user has dismissed this notice previously
		$ssp_categories_update_dismissed = get_option( 'ssp_categories_update_dismissed', 'false' );
		if ( 'true' === $ssp_categories_update_dismissed ) {
			return;
		}
		// trigger the notice
		add_action( 'admin_notices', array( $this, 'categories_update_notice' ) );
	}

	/**
	 * Show 'categories need updating' notice
	 */
	public function categories_update_notice() {
		$feed_settings_url = add_query_arg(
			array(
				'post_type'                     => $this->token,
				'page'                          => 'podcast_settings',
				'tab'                           => 'feed-details',
				'ssp_dismiss_categories_update' => 'true',
			),
			admin_url( 'edit.php' )
		);

		$ignore_message_url = add_query_arg( array( 'ssp_dismiss_categories_update' => 'true' ) );

		$message            = __( 'Seriously Simple Podcasting\'s feed categories have been updated.', 'seriously-simple-podcasting' );
		$feed_settings_link = sprintf(
			wp_kses(
			// translators: Placeholder is the url to the Feed details
				__( 'Please check your <a href="%s">Feed details</a>  to update your categories.', 'seriously-simple-podcasting' ),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			),
			esc_url( $feed_settings_url )
		);
		$ignore_message_link = sprintf(
			wp_kses(
			// translators: Placeholder is the url to dismiss the message
				__( 'Alternatively you can <a href="%s">dismiss this message</a>.', 'seriously-simple-podcasting' ),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			),
			esc_url( $ignore_message_url )
		);
		?>
		<div class="notice notice-info">
			<p><?php echo $message; ?></p>
			<p><?php echo $feed_settings_link; ?></p>
			<p><?php echo $ignore_message_link; ?></p>
		</div>
		<?php
	}

	/**
	 * If the plugin has just been activated, show the Second Line Themes notice.
	 */
	public function second_line_themes() {
		/**
		 * Only show this notice on the All Episodes page and on the Themes page
		 */
		$current_screen  = get_current_screen();
		$allowed_screens = array( 'themes', 'edit-podcast' );
		if ( ! in_array( $current_screen->id, $allowed_screens, true ) ) {
			return;
		}

		/**
		 * Only show this notice once on either the themes page or the podcast list page
		 */
		$viewed_option = get_option( 'ss_podcasting_second_line_themes_' . $current_screen->id, 'false' );
		if ( 'true' === $viewed_option ) {
			return;
		}
		/**
		 * Set the viewed option, so this notice won't appear again on this page
		 */
		update_option( 'ss_podcasting_second_line_themes_' . $current_screen->id, 'true' );

		add_action( 'admin_notices', array( $this, 'second_line_themes_notice' ) );
	}

	/**
	 * Show Second Line Themes notice
	 */
	public function second_line_themes_notice() {

		$second_line_themes_link = sprintf(
			wp_kses(
				// translators: Placeholder is the url to dismiss the message
				__( 'Looking for a dedicated podcast theme to use with Seriously Simple Podcasting? Check out  <a href="%s" target="_blank">Second Line Themes.</a> ', 'seriously-simple-podcasting' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => true,
					),
				)
			),
			esc_url( 'https://secondlinethemes.com/?utm_source=ssp-notice' )
		);

		?>
		<div class="notice notice-info is-dismissible">
			<p><?php echo $second_line_themes_link; // phpcs:ignore ?></p>
		</div>
		<?php
	}
}
