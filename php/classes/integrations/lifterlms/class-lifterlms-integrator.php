<?php
/**
 * Lifter LMS integrator.
 */

namespace SeriouslySimplePodcasting\Integrations\LifterLMS;

use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Helpers\Log_Helper;
use SeriouslySimplePodcasting\Integrations\Abstract_Integrator;
use SeriouslySimplePodcasting\Traits\Singleton;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lifter LMS integrator.
 *
 * @author Sergiy Zakharchenko
 * @package SeriouslySimplePodcasting
 * @since 2.12.0
 */
class LifterLMS_Integrator extends Abstract_Integrator {

	const SINGLE_SYNC_EVENT = 'ssp_lifterlms_single_sync';

	const SINGLE_SYNC_DATA_OPTION = 'ssp_lifterlms_single_sync_data';

	use Singleton;

	/**
	 * @var Feed_Handler
	 * */
	protected $feed_handler;

	/**
	 * @var Castos_Handler
	 * */
	protected $castos_handler;

	/**
	 * @var Log_Helper
	 * */
	protected $logger;

	/**
	 * @var array
	 * */
	protected $castos_podcasts;


	/**
	 * Class Paid_Memberships_Pro_Integrator constructor.
	 *
	 * @param Feed_Handler $feed_handler
	 * @param Castos_Handler $castos_handler
	 * @param Log_Helper $logger
	 */
	public function init( $feed_handler, $castos_handler, $logger ) {
		if ( ! $this->check_dependencies( array( 'LifterLMS', 'LLMS_Student' ), array( 'llms_page_restricted' ) ) ) {
			return;
		}

		$this->feed_handler   = $feed_handler;
		$this->castos_handler = $castos_handler;
		$this->logger         = $logger;

		if ( is_admin() && ! ssp_is_ajax() ) {
			$this->init_integration_settings();
		} else {
			$integration_enabled = ssp_get_option( 'enable_lifterlms_integration' );
			if ( $integration_enabled ) {
				$this->protect_private_series();
			}
		}

		$this->init_subscribers_sync();
	}


	/**
	 * Inits subscribers sync.
	 * There are 3 cases when sync is needed:
	 * 1. When user is enrolled in course.
	 * 2. When user is removed from course.
	 * 3. When Series -> Course association is changed (bulk sync).
	 */
	protected function init_subscribers_sync() {

		// Sync users when their Membership Level is changed (from admin panel, when registered or cancelled).
		add_filter( 'llms_user_enrolled_in_course', array(
			$this,
			'sync_subscriber_on_user_enrolled_in_course'
		), 10, 2 );

		add_filter( 'llms_user_removed_from_course', array(
			$this,
			'sync_subscriber_on_user_removed_from_course'
		), 10, 2 );

		add_action( self::SINGLE_SYNC_EVENT, array( $this, 'process_single_sync_events' ) );


		// Schedule the bulk sync when Series -> Membership Level association is changed.
		add_filter( 'allowed_options', function ( $allowed_options ) {
			// Option ss_podcasting_is_lifterlms_integration is just a marker that PMPro integration settings have been saved.
			// If so, we can do the sync magic.
			if ( isset( $allowed_options['ss_podcasting'] ) ) {
				$key = array_search( 'ss_podcasting_is_lifterlms_integration', $allowed_options['ss_podcasting'] );
				if ( false !== $key ) {
					unset( $allowed_options['ss_podcasting'][ $key ] );
					$this->schedule_bulk_sync_subscribers();
				}
			}

			return $allowed_options;
		}, 20 );

		// Run the scheduled bulk sync.
		add_action( 'ssp_bulk_sync_lifterlms_subscribers', array( $this, 'bulk_sync_subscribers' ) );
	}

	public function process_single_sync_events(){
		$single_sync_events = get_option( self::SINGLE_SYNC_DATA_OPTION, array() );

		foreach ( $single_sync_events as $event ) {
			$user_id           = $event['user_id'];
			$add_series_ids    = $this->get_series_ids_by_course( $event['add_course_id'] );
			$revoke_series_ids = $this->get_series_ids_by_course( $event['revoke_course_id'] );

			$this->sync_user( $user_id, $revoke_series_ids, $add_series_ids );
		}
	}

	/**
	 * @param int $user_id
	 * @param int $course_id
	 *
	 * @return void
	 */
	public function sync_subscriber_on_user_enrolled_in_course( $user_id, $course_id ) {
		$single_sync_events = get_option( self::SINGLE_SYNC_DATA_OPTION, array() );

		// Make sure that if there are multiple events, we don't miss any.
		$single_sync_events[] = array(
			'user_id'          => $user_id,
			'add_course_id'    => $course_id,
			'revoke_course_id' => '',
		);

		update_option( self::SINGLE_SYNC_DATA_OPTION, $single_sync_events );

		$this->schedule_single_sync( 0 );
	}

	/**
	 * @param int $user_id
	 * @param int $course_id
	 *
	 * @return void
	 */
	public function sync_subscriber_on_user_removed_from_course( $user_id, $course_id ) {
		$single_sync_events = get_option( self::SINGLE_SYNC_DATA_OPTION, array() );

		// Make sure that if there are multiple events, we don't miss any.
		$single_sync_events[] = array(
			'user_id'          => $user_id,
			'add_course_id'    => '',
			'revoke_course_id' => $course_id,
		);

		update_option( self::SINGLE_SYNC_DATA_OPTION, $single_sync_events );

		$this->schedule_single_sync( 0 );
	}

	/**
	 * Schedule single sync.
	 *
	 * @param int $delay Schedule delay in minutes.
	 *
	 * @return void
	 */
	protected function schedule_single_sync( $delay = 5 ){
		if ( ! wp_next_scheduled( self::SINGLE_SYNC_EVENT ) ) {
			wp_schedule_single_event( time() + $delay * MINUTE_IN_SECONDS, self::SINGLE_SYNC_EVENT );
		}
	}


	/**
	 * Schedule bulk sync subscribers.
	 */
	protected function schedule_bulk_sync_subscribers() {
		if ( ! wp_next_scheduled( 'ssp_bulk_sync_lifterlms_subscribers' ) ) {
			// 1. Save old membership level map: [['level' => ['users']['series']]]
			update_option( 'ssp_lifterlms_users_series_map', $this->get_users_series_map(), false );

			// 2. Schedule a task to add/revoke users
			wp_schedule_single_event( time(), 'ssp_bulk_sync_lifterlms_subscribers' );
		}
	}


	/**
	 * Gets the map users and series [['user_id' => ['series1_id', 'series2_id']]].
	 *
	 * @return array
	 */
	protected function get_users_series_map() {
		$map = array();

		$users = get_users( array( 'fields' => array( 'ID' ) ) );

		foreach ( $users as $user ) {
			$student      = new \LLMS_Student( $user->ID );
			$user_courses = $student->get_courses( array( 'status' => 'enrolled' ) );

			$user_series = array();
			foreach ( $user_courses['results'] as $course_id ) {
				$series = $this->get_series_ids_by_course( $course_id );

				$user_series = array_merge( $user_series, $series );
			}

			$map[ $user->ID ] = array_unique( $user_series );
		}

		return $map;
	}


	/**
	 * Bulk sync subscribers after settings change.
	 */
	public function bulk_sync_subscribers() {
		$old_map = get_option( 'ssp_lifterlms_users_series_map', array() );

		$new_map = $this->get_users_series_map();

		foreach ( $new_map as $user_id => $new_series ) {
			$old_series = isset( $old_map[ $user_id ] ) ? $old_map[ $user_id ] : array();

			$add_series    = array_diff( $new_series, $old_series );
			$revoke_series = array_diff( $old_series, $new_series );

			if ( $add_series || $revoke_series ) {
				$this->sync_user( $user_id, $revoke_series, $add_series );
			}
		}
	}


	/**
	 * Gets IDs of the series attached to the Membership Level.
	 *
	 * @param int $course_id
	 *
	 * @return array
	 */
	protected function get_series_ids_by_course( $course_id ) {

		$series_ids = array();

		if ( empty( $course_id ) ) {
			return $series_ids;
		}

		$series_terms = $this->get_series();

		foreach ( $series_terms as $series ) {
			$course_ids = $this->get_series_course_ids( array( $series ) );

			if ( in_array( $course_id, $course_ids ) ) {
				$series_ids[] = $series->term_id;
			}
		}

		return $series_ids;
	}


	/**
	 * Inits integration settings.
	 * */
	protected function init_integration_settings() {
		// Use priority 12 because Podcast and Series post types registered on 11.
		add_action( 'init', array( $this, 'integration_settings' ), 12 );
	}


	/**
	 * Protects private series.
	 * */
	protected function protect_private_series() {
		add_action( 'ssp_before_feed', array( $this, 'protect_feed_access' ) );
		add_filter( 'template_include', array( $this, 'protect_content' ) );
	}


	/**
	 * This code was partially copied and modified from LLMS_Template_Loader::template_loader()
	 * */
	public function protect_content( $template ) {

		// We need to protect series and their episodes
		$current_series = $this->get_current_page_related_series();

		if ( empty( $current_series ) ) {
			return $template;
		}

		// We need to protect only private series
		$private_series = array();

		foreach ( $current_series as $series ) {
			if ( $this->is_series_protected_in_castos( $series->term_id ) ) {
				$private_series[] = $series;
			}
		}

		// Let's find courses that are related to private series
		// If user don't have access to any of those courses then don't provide access to them
		$course_ids = $this->get_series_course_ids( $private_series );

		$restrict_filter = function ( $results, $post_id ) {
			$results['restriction_id'] = $post_id;
			$results['reason']         = 'enrollment_lesson';

			return $results;
		};

		add_filter( 'llms_page_restricted_before_check_access', $restrict_filter, 10, 2 );

		foreach ( $course_ids as $course_id ) {

			$page_restricted = llms_page_restricted( $course_id );

			if ( ! $page_restricted['is_restricted'] ) {
				continue;
			}
			/**
			 * Generic action triggered when content is restricted.
			 *
			 * @param array $page_restricted Restriction information from `llms_page_restricted()`.
			 *
			 * @see llms_content_restricted_by_{$page_restricted['reason']} A specific hook triggered by a specific restriction reason.
			 *
			 * @since Unknown
			 *
			 */
			do_action( 'lifterlms_content_restricted', $page_restricted );

			/**
			 * Action triggered when content is restricted for the specified reason.
			 *
			 * The dynamic portion of this hook, `{$page_restricted['reason']}` refers to the restriction reason
			 * code generated by `llms_page_restricted()`.
			 *
			 * @param array $page_restricted Restriction information from `llms_page_restricted()`.
			 *
			 * @see llms_content_restricted A generic hook triggered at the same time.
			 *
			 * @since Unknown
			 *
			 */
			do_action( "llms_content_restricted_by_enrollment_lesson", $page_restricted );
		}

		remove_filter( 'llms_page_restricted_before_check_access', $restrict_filter );

		return $template;
	}


	/**
	 * Protects access to private feeds.
	 * */
	public function protect_feed_access() {
		$series_slug = $this->feed_handler->get_series_slug();
		if ( empty( $series_slug ) ) {
			return;
		}

		$series = get_term_by( 'slug', $this->feed_handler->get_series_slug(), 'series' );

		// Do not protect unprotected series
		if ( ! $this->is_series_protected_in_castos( $series->term_id ) ) {
			return;
		}

		// Series is protected, does user have access?
		$has_access = true;
		$related_course_ids = $this->get_series_course_ids( array( $series ) );

		if ( $related_course_ids ) {
			$has_access = $this->has_access( wp_get_current_user(), $related_course_ids );
		}

		if ( ! $has_access ) {
			$description = __( 'This content is Private. To access this podcast, contact the site owner.', 'seriously-simple-podcasting' );
			$this->feed_handler->render_feed_no_access( $series->term_id, $description );
			exit();
		}
	}


	/**
	 * Check if user has access to the episode. Took the logic from PMPro.
	 *
	 * @return bool
	 * @see pmpro_has_membership_access()
	 */
	protected function has_access( $user, $course_ids ) {
		if ( empty( $course_ids ) ) {
			return true;
		}

		$student = new \LLMS_Student( $user );

		foreach ( $course_ids as $course_id ) {
			if ( ! $student->is_enrolled( $course_id ) ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Gets series level ids.
	 *
	 * @param WP_Term[] $series_terms
	 *
	 * @return int[]
	 */
	protected function get_series_course_ids( $series_terms ) {
		$course_ids = array();

		foreach ( $series_terms as $series ) {
			$courses    = (array) ssp_get_option( sprintf( 'series_%s_lifterlms_courses', $series->term_id  ), null );
			$current_course_ids = array();
			foreach ( $courses as $course ) {
				$current_course_ids[] = (int) str_replace( 'course_', '', $course );
			}

			$course_ids = array_merge( $course_ids, $current_course_ids );
		}

		return array_filter( $course_ids );
	}


	/**
	 * Gets array of episode series terms.
	 *
	 * @param $post_id
	 *
	 * @return WP_Term[]
	 */
	protected function get_episode_series( $post_id ) {
		$series = wp_get_post_terms( $post_id, 'series' );

		if ( is_wp_error( $series ) ) {
			return [];
		}

		return $series;
	}


	/**
	 * Inits integration settings.
	 */
	public function integration_settings() {

		if ( ! $this->needs_integration_settings() ) {
			return;
		}

		$args = $this->get_integration_settings();

		if ( ! ssp_is_connected_to_castos() ) {
			$msg = __( 'Please <a href="%s">connect to Castos hosting</a> to enable integrations', 'seriously-simple-podcasting' );
			$msg = sprintf( $msg, admin_url( 'edit.php?post_type=podcast&page=podcast_settings&tab=castos-hosting' ) );

			$args['description'] = $msg;
			$args['fields']      = array();
		}

		$this->add_integration_settings( $args );
	}


	/**
	 * Checks if we need to obtain the dynamic integration settings.
	 *
	 * @return bool
	 */
	protected function needs_integration_settings() {
		global $pagenow;

		return 'options.php' === $pagenow || 'podcast_settings' === filter_input( INPUT_GET, 'page' );
	}


	/**
	 * Gets integration settings.
	 *
	 * @return array
	 */
	protected function get_integration_settings() {
		$series = $this->get_series();

		$settings = array(
			'id'          => 'lifterlms',
			'title'       => __( 'LifterLMS', 'seriously-simple-podcasting' ),
			'description' => __( 'Select which Podcast you would like to be available only
								to Members via LifterLMS.', 'seriously-simple-podcasting' ),
			'fields'      => array(
				array(
					'id'   => 'is_lifterlms_integration',
					'type' => 'hidden',
				),
				array(
					'id'    => 'enable_lifterlms_integration',
					'type'  => 'checkbox',
					'label' => __( 'Enable integration', 'seriously-simple-podcasting' ),
					'description' => __( 'Enable LifterLMS integration', 'seriously-simple-podcasting' ),
				),
			),
		);


		if ( ! ssp_get_option( 'enable_lifterlms_integration' ) ) {
			$settings['description'] = '';

			return $settings;
		}

		$checkbox_options = array();

		$courses = $this->get_courses();

		foreach ( $courses as $course ) {
			$checkbox_options[ 'course_' . $course->ID ] = $course->post_title;
		}

		foreach ( $series as $series_item ) {
			$series_item_settings = array(
				'id'      => sprintf( 'series_%s_lifterlms_courses', $series_item->term_id ),
				'label'   => $series_item->name,
				'type'    => 'select2_multi',
				'options' => $checkbox_options,
				'description' => 'Require enrollment to course',
			);

			if ( ! $this->is_series_protected_in_castos( $series_item->term_id ) ) {
				$series_item_settings['type']        = 'info';
				$series_item_settings['description'] = 'Please first make this podcast private in your Castos dashboard';
			}

			$settings['fields'][] = $series_item_settings;
		}

		return $settings;
	}


	/**
	 * @return \WP_Post[]
	 */
	protected function get_courses() {
		$args = array(
			'post_type'   => 'course',
			'post_status' => 'publish',
		);

		return get_posts( $args );
	}


	/**
	 * Check if the series is protected on Castos side.
	 *
	 * @param int $series_id
	 * @param bool $default
	 *
	 * @return bool|mixed
	 */
	protected function is_series_protected_in_castos( $series_id, $default = false ) {
		$podcasts = $this->get_castos_podcasts();

		foreach ( $podcasts as $podcast ) {
			if ( isset( $podcast['series_id'] ) && $series_id === $podcast['series_id'] ) {
				return $podcast['is_feed_protected'];
			}
		}

		// Return true
		return $default;
	}


	/**
	 * @return array
	 */
	protected function get_castos_podcasts() {
		if ( is_null( $this->castos_podcasts ) ) {
			$podcasts              = $this->castos_handler->get_podcasts();
			$this->castos_podcasts = isset( $podcasts['data']['podcast_list'] ) ?
				$podcasts['data']['podcast_list'] :
				array();
		}

		return $this->castos_podcasts;
	}
}
