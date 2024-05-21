<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Interfaces\Service;
use SeriouslySimplePodcasting\Traits\URL_Helper;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

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
class Admin_Notifications_Handler implements Service {

	use URL_Helper;
	use Useful_Variables;

	/**
	 * Transient key to store flash notices
	 * */
	const NOTICES_KEY = 'ssp_notices';

	/**
	 * Predefined notices
	 * */
	const NOTICE_API_EPISODE_ERROR = 'api_episode_error';
	const NOTICE_API_EPISODE_SUCCESS = 'api_episode_success';
	const NOTICE_NGINX_ERROR = 'nginx_error';

	/**
	 * Notice types
	 * "info", "warning", "error" or "success"
	 * */
	const INFO = 'info';
	const WARNING = 'warning';
	const ERROR = 'error';
	const SUCCESS = 'success';

	/**
	 * @var Castos_Handler $castos_handler
	 * */
	protected $castos_handler;

	/**
	 * Admin_Notifications_Handler constructor.
	 *
	 * @param Castos_Handler $castos_handler
	 */
	public function __construct( $castos_handler ) {
		$this->init_useful_variables();

		$this->castos_handler = $castos_handler;

		return $this;
	}

	/**
	 * Class bootstrap, loads all action and filter hooks
	 */
	public function bootstrap() {
		add_action( 'current_screen', array( $this, 'check_existing_podcasts' ) );

		add_action( 'current_screen', array( $this, 'maybe_show_nginx_error_notice' ) );

		// Check if a valid permalink structure is set and show a message
		add_action( 'admin_init', array( $this, 'check_valid_permalink' ) );

		// Trigger the Elementor Templates message
		add_action( 'admin_init', array( $this, 'show_elementor_templates_available' ) );

		// Trigger the series helper message
		add_action( 'series_pre_add_form', array( $this, 'show_series_helper_text' ) );

		// Print flash notices
		add_action( 'admin_notices', array( $this, 'display_flash_notices' ), 12 );

		return $this;
	}

	/**
	 * Shows an error notice for sites running on NGINX with the wrong settings for static files.
	 *
	 * @return void
	 */
	public function maybe_show_nginx_error_notice() {

		if ( ! in_array( get_current_screen()->post_type, ssp_post_types() ) || ! $this->is_nginx() ) {
			return;
		}

		$nginx_settings_status = get_transient( 'ssp_nginx_settings_status' );

		if ( 'ok' === $nginx_settings_status ) {
			return;
		}

		if ( 'error' === $nginx_settings_status ) {
			$this->show_nginx_error_notice();

			return;
		}

		$episode_ids = ssp_episode_ids();

		if ( ! isset( $episode_ids[0] ) ) {
			return;
		}

		$id = $episode_ids[0];

		$link = site_url( '/podcast-player/' . $id . '/test-nginx.mp3?ref=test-nginx' );

		$response = $this->get_response( $link );

		if ( ! $response ) {
			return;
		}

		if ( 404 === $response->get_status() ) {
			set_transient( 'ssp_nginx_settings_status', 'error', 10 * MINUTE_IN_SECONDS );
			$this->show_nginx_error_notice();
		} else {
			set_transient( 'ssp_nginx_settings_status', 'ok', DAY_IN_SECONDS );
		}
	}

	/**
	 * Show error notice if NGINX settings are wrong.
	 * */
	protected function show_nginx_error_notice() {
		$messages = $this->get_predefined_notices();
		$notice   = $messages[ self::NOTICE_NGINX_ERROR ];

		$this->add_flash_notice( $notice['msg'], $notice['type'], false );
	}

	/**
	 * Checks if current site is running under nginx or not.
	 *
	 * @return bool
	 */
	protected function is_nginx() {
		$server_type = get_transient( 'ssp_server_type' );
		if ( ! $server_type ) {
			$response = $this->get_response( site_url( '/test.mp3' ) );
			$server   = $response ? $response->get_headers()->offsetGet( 'server' ) : '';
			$server_type = is_string( $server ) && ( false !== strpos( $server, 'nginx' ) ) ? 'nginx' : $server;

			set_transient( 'ssp_server_type', $server_type, DAY_IN_SECONDS );
		}

		return 'nginx' === $server_type;
	}

	/**
	 * Gets host (domain) from the url.
	 *
	 * @param $url
	 *
	 * @return string
	 */
	protected function get_host( $url ) {
		$parsed_url = parse_url( $url );

		return isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
	}

	/**
	 * Gets response from the URL
	 *
	 * @param string $url
	 *
	 * @return \WP_HTTP_Requests_Response|null
	 */
	protected function get_response( $url ) {
		$res = wp_remote_head( $url );

		if ( ! is_array( $res ) || ! isset( $res['http_response'] ) || ! $res['http_response'] instanceof \WP_HTTP_Requests_Response ) {
			return null;
		}

		$response = $res['http_response'];

		if ( in_array( $response->get_status(), array( 301, 302 ) ) ) {
			$headers  = $response->get_headers();
			$location = isset( $headers['location'] ) ? $headers['location'] : '';

			return $location ? $this->get_response( $location ) : $response;
		}

		return $response;
	}

	/**
	 * Add a predefined flash notice
	 *
	 * @param string $notice Predefined notice
	 *
	 * @return bool If the notice is added or not
	 * @see NOTICE_API_EPISODE_ERROR
	 * @see NOTICE_API_EPISODE_SUCCESS
	 *
	 */
	public function add_predefined_flash_notice( $notice ) {
		$messages = $this->get_predefined_notices();

		if ( isset( $messages[ $notice ] ) ) {
			$this->add_flash_notice(
				$messages[ $notice ]['msg'],
				$messages[ $notice ]['type']
			);

			return true;
		}

		return false;
	}

	/**
	 * Add a predefined flash notice
	 *
	 * @param string $notice Predefined notice
	 *
	 * @return bool If the notice is added or not
	 * @see NOTICE_API_EPISODE_ERROR
	 * @see NOTICE_API_EPISODE_SUCCESS
	 *
	 */
	public function remove_predefined_flash_notice( $notice ) {
		$messages = $this->get_predefined_notices();

		if ( isset( $messages[ $notice ] ) ) {
			$this->remove_flash_notice (
				$messages[ $notice ]['msg']
			);

			return true;
		}

		return false;
	}

	/**
	 * Add a flash notice
	 *
	 * @param string $notice our notice message
	 * @param string $type This can be "info", "warning", "error" or "success", "warning" as default
	 * @param boolean $dismissible set this to TRUE to add is-dismissible functionality to your notice
	 *
	 * @return void
	 */
	public function add_flash_notice( $notice = "", $type = "warning", $dismissible = true ) {
		$notices = get_transient( self::NOTICES_KEY );
		if ( ! $notices ) {
			$notices = array();
		}

		$dismissible_text = ( $dismissible ) ? "is-dismissible" : "";

		$notices[ $this->get_notice_hash( $notice ) ] = array(
			"notice"      => $notice,
			"type"        => $type,
			"dismissible" => $dismissible_text
		);

		set_transient( self::NOTICES_KEY, $notices, DAY_IN_SECONDS );
	}

	/**
	 * @param string $notice
	 *
	 * @return string
	 */
	public function get_notice_hash( $notice ) {
		return md5( serialize( $notice ) );
	}

	/**
	 * Remove flash notice
	 *
	 * @param string $notice notice message
	 *
	 * @return void
	 */
	public function remove_flash_notice( $notice = "" ) {
		$notices = get_transient( self::NOTICES_KEY );
		$hash = $this->get_notice_hash( $notice );

		if( is_array( $notices ) && array_key_exists( $hash, $notices ) ){
			unset( $notices[ $hash ] );
		}

		set_transient( self::NOTICES_KEY, $notices, DAY_IN_SECONDS );
	}

	/**
	 * Prints flash notices
	 */
	public function display_flash_notices() {
		if ( ! $this->is_ssp_post_page() && ! $this->is_ssp_admin_page() ) {
			return;
		}

		$notices = get_transient( self::NOTICES_KEY );

		if ( ! is_array( $notices ) ) {
			return;
		}

		foreach ( $notices as $notice ) {
			printf( '<div class="notice notice-%1$s %2$s"><p>%3$s</p></div>',
				$notice['type'],
				$notice['dismissible'],
				$notice['notice']
			);
		}

		if ( ! empty( $notices ) ) {
			delete_transient( self::NOTICES_KEY );
		}
	}

	/**
	 * Admin notice for the add series page
	 */
	public function show_series_helper_text() {
		$text = '
		A new Podcast will create an entirely new Podcast Feed. <br>
		Only do this if you want to have multiple shows within your WordPress site. <br>
		If you just want to organize episodes within the same feed we suggest using Tags.';
		echo sprintf( '<div class="notice series-notice notice-warning"><p>%s</p></div>', $text );
	}


	/**
	 * Show an error if the block assets have failed
	 * @todo: investigate if it works and maybe replace it with add_predefined_flash_notice();
	 */
	public function blocks_error_notice() {
		?>
		<div class="notice notice-info">
			<p><?php _e( 'An error has occurred loading the block editor assets. Please report this to the plugin developer.', 'seriousy-simple-podcasting' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Check if there are existing podcasts to be uploaded to Seriously Simple Hosting
	 */
	public function check_existing_podcasts() {
		/**
		 * Only trigger this if we're connected to Seriously Simple Hosting
		 */
		if ( ! ssp_is_connected_to_castos() ) {
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
		$podcast_query = ssp_get_not_synced_episodes( 1 );
		if ( $podcast_query->have_posts() ) {
			add_action( 'admin_notices', array( $this, 'existing_episodes_notice' ) );
		}
	}

	/**
	 * Show 'existing podcast' notice
	 */
	public function existing_episodes_notice() {
		$hosting_tab_url    = ssp_get_tab_url( 'castos-hosting' );
		$ignore_message_url = add_query_arg( array(
			'podcast_import_action' => 'ignore',
			'nonce'                 => wp_create_nonce( 'podcast_import_action' ),
		) );
		$message            = '';
		$message            .= '<p>You\'ve connected to your Castos account, and you have existing podcasts that can be synced.</p>';
		$message            .= '<p>You can <a href="' . $hosting_tab_url . '">sync your existing podcasts to Castos now.</a></p>';
		$message            .= '<p>Alternatively you can <a href="' . $ignore_message_url . '">dismiss this message.</a></p>';
		?>
		<div class="notice notice-info">
			<p><?php _e( $message, 'seriousy-simple-podcasting' ); ?></p>
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

	public function show_elementor_templates_available() {
		// only show this on podcast list pages
		$post_type = ( isset( $_GET['post_type'] ) ? filter_var( $_GET['post_type'], FILTER_DEFAULT ) : '' );
		if ( empty( $post_type ) || SSP_CPT_PODCAST !== $post_type ) {
			return;
		}
		// only show this is elementor is installed
		if ( ! ssp_is_elementor_ok() ) {
			return;
		}
		// only show if the user hasn't already disabled this notice
		$ss_podcasting_elementor_templates_disabled = get_option( 'ss_podcasting_elementor_templates_disabled', 'false' );
		if ( 'true' === $ss_podcasting_elementor_templates_disabled ) {
			return;
		}

		add_action( 'admin_notices', array( $this, 'show_elementor_templates_notice' ) );
	}

	public function show_elementor_templates_notice() {

		$elementor_templates_link = sprintf(
			wp_kses(
			// translators: Placeholder is the url to dismiss the message
				__( 'Using Elementor? Seriously Simple Podcasting now has built in Elementor templates to build podcast specific pages. <a href="%s">Click here to install them now.</a> ', 'seriously-simple-podcasting' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => true,
					),
				)
			),
			esc_url( admin_url( 'edit.php?post_type=' . SSP_CPT_PODCAST . '&page=podcast_settings&tab=extensions' ) )
		);

		$ignore_message_url  = add_query_arg( array( 'ssp_disable_elementor_template_notice' => 'true' ) );
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
		<div class="notice notice-info is-dismissible">
			<p><?php echo $elementor_templates_link; // phpcs:ignore ?></p>
			<p><?php echo $ignore_message_link; ?></p>
		</div>
		<?php
	}

	/**
	 * Get predefined notices
	 * @return array
	 * */
	public function get_predefined_notices() {
		$notices = array(
			self::NOTICE_API_EPISODE_SUCCESS => array(
				'msg'  => __( 'Your episode was successfully synced to your Castos account', 'seriously-simple-podcasting' ),
				'type' => self::SUCCESS,
			),
			self::NOTICE_API_EPISODE_ERROR   => array(
				'msg'  => __( "An error occurred in syncing this episode to your Castos account. <br>
								We will keep attempting to sync your episode over the next 24 hours. <br>
								If you don't see this episode in your Castos account at that time please contact our support team at hello@castos.com", 'seriously-simple-podcasting' ),
				'type' => self::ERROR,
			),
			self::NOTICE_NGINX_ERROR         => array(
				'msg'  => sprintf( __(
					"We've detected that your website is using NGINX.
					In order for Seriously Simple Podcasting to play your episodes, you'll need to reach out to your web host or system administrator and follow the instructions outlined in this <a href='%s'>help document.</a>",
					'seriously-simple-podcasting'
				), esc_url( 'https://support.castos.com/article/298-bypass-rules-for-nginx-hosted-websites' ) ),
				'type' => self::ERROR,
			),
		);

		return apply_filters( 'ssp_predefined_notices', $notices );
	}

}
