<?php
/**
 * Podcast List shortcode class.
 *
 * @package SeriouslySimplePodcasting
 * @since 3.13.0
 */

namespace SeriouslySimplePodcasting\ShortCodes;

use SeriouslySimplePodcasting\Renderers\Renderer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Podcast List Shortcode
 *
 * @author     Serhiy Zakharchenko
 * @package    SeriouslySimplePodcasting
 * @since      3.13.0
 */
class Podcast_List implements Shortcode {

	/**
	 * Minimum number of columns allowed.
	 *
	 * @since 3.13.0
	 */
	const MIN_COLUMNS = 1;

	/**
	 * Maximum number of columns allowed.
	 *
	 * @since 3.13.0
	 */
	const MAX_COLUMNS = 3;

	/**
	 * Renderer instance.
	 *
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Initializes the podcast list shortcode.
	 *
	 * @since 3.13.0
	 */
	public function __construct() {
		$this->renderer = new Renderer();

		// Register CSS assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Registers CSS assets for the podcast list shortcode.
	 *
	 * @since 3.13.0
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			'ssp-podcast-list-shortcode',
			esc_url( SSP_PLUGIN_URL . 'assets/css/podcast-list.css' ),
			array(),
			SSP_VERSION
		);
	}

	/**
	 * Enqueues CSS assets for the podcast list shortcode.
	 *
	 * @since 3.13.0
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! wp_style_is( 'ssp-podcast-list-shortcode', 'enqueued' ) ) {
			wp_enqueue_style( 'ssp-podcast-list-shortcode' );
		}
	}

	/**
	 * Renders the podcast list shortcode with the provided attributes.
	 *
	 * @since 3.13.0
	 *
	 * @param  array $params  Shortcode attributes.
	 * @return string          HTML output
	 */
	public function shortcode( $params ) {
		$defaults = array(
			'ids'                => '',
			'columns'            => 1,
			'sort_by'            => 'id',
			'sort'               => 'asc',
			'clickable'          => 'button',
			'hide_button'        => 'false',
			'show_description'   => 'true',
			'show_episode_count' => 'true',
			'description_words'  => 0,
			'description_chars'  => 0,
		);

		$args = shortcode_atts( $defaults, $params, 'ssp_podcasts' );

		// Enqueue CSS only when shortcode is used.
		$this->enqueue_assets();

		// Validate and sanitize parameters.
		$columns            = $this->validate_columns_parameter( $args['columns'] );
		$sort_by            = $this->validate_sort_by_parameter( $args['sort_by'] );
		$sort               = $this->validate_sort_parameter( $args['sort'] );
		$clickable          = $this->validate_clickable_parameter( $args['clickable'] );
		$hide_button        = $this->validate_hide_button_parameter( $args['hide_button'] );
		$show_description   = $this->validate_show_description_parameter( $args['show_description'] );
		$show_episode_count = $this->validate_show_episode_count_parameter( $args['show_episode_count'] );
		$description_words  = $this->validate_description_words_parameter( $args['description_words'] );
		$description_chars  = $this->validate_description_chars_parameter( $args['description_chars'] );

		// Auto-adjustment: if hide_button=true and clickable=button, set clickable=title.
		if ( $hide_button && 'button' === $clickable ) {
			$clickable = 'title';
		}

		// Process display options for template.
		$show_button   = ! $hide_button && 'button' === $clickable;
		$wrapper_class = 'ssp-podcast-card';
		if ( 'card' === $clickable ) {
			$wrapper_class .= ' ssp-podcast-card-clickable';
		}
		$columns_class = 'ssp-podcasts-columns-' . $columns;

		// Get podcasts based on IDs parameter.
		$podcasts = $this->get_podcasts( $args['ids'], $sort_by, $sort );

		// Allow themes to filter podcast data before rendering.
		$podcasts = array_map(
			function ( $podcast, $index ) {
				return apply_filters( 'ssp/podcast_list/card_data', $podcast, $index );
			},
			$podcasts,
			array_keys( $podcasts )
		);

		// Process descriptions with truncation if needed.
		if ( $show_description && ( $description_words > 0 || $description_chars > 0 ) ) {
			$podcasts = $this->truncate_descriptions( $podcasts, $description_words, $description_chars );
		}

		// Prepare template data.
		$template_data = array(
			'podcasts'           => $podcasts,
			'columns'            => $columns,
			'clickable'          => $clickable,
			'hide_button'        => $hide_button,
			'show_button'        => $show_button,
			'show_description'   => $show_description,
			'show_episode_count' => $show_episode_count,
			'wrapper_class'      => $wrapper_class,
			'columns_class'      => $columns_class,
		);

		// Render the template.
		return $this->renderer->fetch( 'podcast-list', $template_data );
	}

	/**
	 * Retrieves and processes podcast data for the shortcode display.
	 *
	 * @since 3.13.0
	 *
	 * @param string $ids     Comma-separated podcast IDs.
	 * @param string $sort_by Sort by parameter (id, name, episode_count).
	 * @param string $sort    Sort direction (asc, desc).
	 * @return array Array of podcast data.
	 */
	private function get_podcasts( $ids = '', $sort_by = 'id', $sort = 'asc' ) {
		// Early return for specific IDs - ignore all other parameters.
		if ( ! empty( $ids ) ) {
			return $this->get_podcasts_by_ids( $ids );
		}

		// Prepare additional arguments for get_terms() based on sort_by parameter.
		$additional_args = array();

		// Use database-level sorting for ID and name sorting.
		if ( 'id' === $sort_by || 'name' === $sort_by ) {
			$additional_args['orderby'] = 'id' === $sort_by ? 'term_id' : 'name';
			$additional_args['order']   = strtoupper( $sort );
		}

		// Get podcasts using existing function with additional args.
		$podcasts = ssp_get_podcasts( false, $additional_args );

		// Process each podcast into our data structure.
		$podcasts_data = $this->process_podcasts_data( $podcasts );

		// Sort podcasts for episode_count using PHP sorting.
		if ( 'episode_count' === $sort_by ) {
			$podcasts_data = $this->sort_podcasts( $podcasts_data, $sort_by, $sort );
		}

		return $podcasts_data;
	}

	/**
	 * Retrieves and processes podcast data by specific IDs in exact order.
	 *
	 * @since 3.13.0
	 *
	 * @param string $ids Comma-separated podcast IDs.
	 * @return array Array of podcast data in the exact order of provided IDs.
	 */
	private function get_podcasts_by_ids( $ids ) {
		if ( empty( $ids ) ) {
			return array();
		}

		$podcast_ids = array_map( 'intval', explode( ',', $ids ) );
		$podcast_ids = array_filter( $podcast_ids );

		if ( empty( $podcast_ids ) ) {
			return array();
		}

		// Get podcasts by specific IDs using include parameter with exact order.
		$additional_args = array(
			'include' => $podcast_ids,
			'orderby' => 'include', // Maintain exact order of IDs provided.
		);
		$podcasts        = ssp_get_podcasts( false, $additional_args );

		// Process each podcast into our data structure.
		return $this->process_podcasts_data( $podcasts );
	}

	/**
	 * Processes podcast term objects into standardized data structure.
	 *
	 * @since 3.13.0
	 *
	 * @param array $podcasts Array of podcast term objects.
	 * @return array Array of processed podcast data.
	 */
	private function process_podcasts_data( $podcasts ) {
		$podcasts_data = array();
		foreach ( $podcasts as $podcast ) {
			if ( is_wp_error( $podcast ) ) {
				continue;
			}

			$podcast_data = array(
				'id'            => $podcast->term_id,
				'name'          => $podcast->name,
				'description'   => $podcast->description,
				'slug'          => $podcast->slug,
				'episode_count' => $this->get_episode_count( $podcast->term_id ),
				'cover_image'   => $this->get_cover_image( $podcast->term_id ),
				'url'           => $this->get_podcast_url( $podcast ),
			);

			$podcasts_data[] = $podcast_data;
		}

		return $podcasts_data;
	}

	/**
	 * Sorts podcasts by the exact order of IDs provided.
	 *
	 * @since 3.13.0
	 *
	 * @param array  $podcasts_data Array of podcast data.
	 * @param string $ids           Comma-separated podcast IDs.
	 * @return array Sorted array of podcast data.
	 */
	private function sort_podcasts_by_ids_order( $podcasts_data, $ids ) {
		if ( empty( $ids ) || empty( $podcasts_data ) ) {
			return $podcasts_data;
		}

		$podcast_ids = array_map( 'intval', explode( ',', $ids ) );
		$podcast_ids = array_filter( $podcast_ids );

		if ( empty( $podcast_ids ) ) {
			return $podcasts_data;
		}

		// Create a mapping of podcast ID to podcast data.
		$podcasts_by_id = array();
		foreach ( $podcasts_data as $podcast ) {
			$podcasts_by_id[ $podcast['id'] ] = $podcast;
		}

		// Sort podcasts according to the order of IDs provided.
		$sorted_podcasts = array();
		foreach ( $podcast_ids as $id ) {
			if ( isset( $podcasts_by_id[ $id ] ) ) {
				$sorted_podcasts[] = $podcasts_by_id[ $id ];
			}
		}

		return $sorted_podcasts;
	}

	/**
	 * Filters podcast collection to include only specified IDs.
	 *
	 * @since 3.13.0
	 *
	 * @param array  $podcasts Array of podcast term objects.
	 * @param string $ids      Comma-separated podcast IDs.
	 * @return array Filtered array of podcast term objects.
	 */
	private function filter_podcasts_by_ids( $podcasts, $ids ) {
		if ( empty( $ids ) ) {
			return $podcasts;
		}

		$podcast_ids = array_map( 'intval', explode( ',', $ids ) );
		$podcast_ids = array_filter( $podcast_ids );

		if ( empty( $podcast_ids ) ) {
			return array();
		}

		return array_filter(
			$podcasts,
			function ( $podcast ) use ( $podcast_ids ) {
				return in_array( $podcast->term_id, $podcast_ids, true );
			}
		);
	}

	/**
	 * Counts published episodes for a specific podcast.
	 *
	 * @since 3.13.0
	 *
	 * @param int $podcast_id Podcast term ID.
	 * @return int Episode count.
	 */
	private function get_episode_count( $podcast_id ) {
		$episodes = ssp_episode_repository()->get_podcast_episodes( $podcast_id );
		return count( $episodes );
	}

	/**
	 * Retrieves the cover image URL for a specific podcast.
	 *
	 * @since 3.13.0
	 *
	 * @param int $podcast_id Podcast term ID.
	 * @return string Cover image URL or default image.
	 */
	private function get_cover_image( $podcast_id ) {
		$term = get_term( $podcast_id, ssp_series_taxonomy() );
		if ( is_wp_error( $term ) || ! $term ) {
			return '';
		}

		// First, try to get the podcast (series taxonomy) image.
		$podcast_image = ssp_get_podcast_image_src( $term, 'medium' );

		// If podcast image exists and is not the default no-image, use it.
		if ( ! empty( $podcast_image ) && false === strpos( $podcast_image, 'no-image.png' ) ) {
			return $podcast_image;
		}

		// Fallback to feed image if podcast image is not available or is default.
		$settings_handler = ssp_get_service( 'settings_handler' );
		$feed_image       = $settings_handler->get_feed_image( $podcast_id );

		if ( ! empty( $feed_image ) ) {
			return $feed_image;
		}

		// Final fallback to podcast image (even if it's the default no-image).
		return $podcast_image;
	}

	/**
	 * Generates the public URL for a podcast term.
	 *
	 * @since 3.13.0
	 *
	 * @param object $podcast Podcast term object.
	 * @return string Podcast URL.
	 */
	private function get_podcast_url( $podcast ) {
		$url = get_term_link( $podcast, ssp_series_taxonomy() );

		// Return the URL or empty string if there's an error.
		return is_wp_error( $url ) ? '' : $url;
	}

	/**
	 * Validates and sanitizes the show_description parameter to ensure it's a boolean.
	 *
	 * @since 3.13.0
	 *
	 * @param mixed $show_description The show_description parameter value.
	 * @return bool Validated show_description value.
	 */
	private function validate_show_description_parameter( $show_description ) {
		// Convert string values to boolean.
		if ( 'true' === $show_description || true === $show_description || '1' === $show_description || 1 === $show_description ) {
			return true;
		}

		return false;
	}

	/**
	 * Validates and sanitizes the show_episode_count parameter to ensure it's a boolean.
	 *
	 * @since 3.13.0
	 *
	 * @param mixed $show_episode_count The show_episode_count parameter value.
	 * @return bool Validated show_episode_count value.
	 */
	private function validate_show_episode_count_parameter( $show_episode_count ) {
		// Convert string values to boolean.
		if ( 'true' === $show_episode_count || true === $show_episode_count || '1' === $show_episode_count || 1 === $show_episode_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Validates and sanitizes the clickable parameter to ensure it's a valid option.
	 *
	 * @since 3.13.0
	 *
	 * @param mixed $clickable The clickable parameter value.
	 * @return string Validated clickable value (button, card, title).
	 */
	private function validate_clickable_parameter( $clickable ) {
		$valid_options = array( 'button', 'card', 'title' );

		if ( in_array( $clickable, $valid_options, true ) ) {
			return $clickable;
		}

		// Fall back to default if invalid.
		return 'button';
	}

	/**
	 * Validates and sanitizes the hide_button parameter to ensure it's a boolean.
	 *
	 * @since 3.13.0
	 *
	 * @param mixed $hide_button The hide_button parameter value.
	 * @return bool Validated hide_button value.
	 */
	private function validate_hide_button_parameter( $hide_button ) {
		// Convert string values to boolean.
		if ( 'true' === $hide_button || true === $hide_button || '1' === $hide_button || 1 === $hide_button ) {
			return true;
		}

		return false;
	}

	/**
	 * Validates and sanitizes the sort_by parameter to ensure it's a valid option.
	 *
	 * @since 3.13.0
	 *
	 * @param mixed $sort_by The sort_by parameter value.
	 * @return string Validated sort_by value (id, name, episode_count).
	 */
	private function validate_sort_by_parameter( $sort_by ) {
		$valid_options = array( 'id', 'name', 'episode_count' );

		if ( in_array( $sort_by, $valid_options, true ) ) {
			return $sort_by;
		}

		// Fall back to default if invalid.
		return 'id';
	}

	/**
	 * Validates and sanitizes the sort parameter to ensure it's a valid direction.
	 *
	 * @since 3.13.0
	 *
	 * @param mixed $sort The sort parameter value.
	 * @return string Validated sort value (asc, desc).
	 */
	private function validate_sort_parameter( $sort ) {
		$valid_options = array( 'asc', 'desc' );

		if ( in_array( $sort, $valid_options, true ) ) {
			return $sort;
		}

		// Fall back to default if invalid.
		return 'asc';
	}

	/**
	 * Sorts podcast data array based on the specified criteria and direction.
	 *
	 * @since 3.13.0
	 *
	 * @param array  $podcasts_data Array of podcast data.
	 * @param string $sort_by       Sort by parameter (id, name, episode_count).
	 * @param string $sort          Sort direction (asc, desc).
	 * @return array Sorted array of podcast data.
	 */
	private function sort_podcasts( $podcasts_data, $sort_by, $sort ) {
		if ( empty( $podcasts_data ) ) {
			return $podcasts_data;
		}

		// Define the comparison function based on sort_by parameter.
		$comparison_function = function ( $a, $b ) use ( $sort_by ) {
			switch ( $sort_by ) {
				case 'name':
					return strcasecmp( $a['name'], $b['name'] );
				case 'episode_count':
					return $a['episode_count'] - $b['episode_count'];
				case 'id':
				default:
					return $a['id'] - $b['id'];
			}
		};

		// Sort the array.
		usort( $podcasts_data, $comparison_function );

		// Reverse the array if descending order is requested.
		if ( 'desc' === $sort ) {
			$podcasts_data = array_reverse( $podcasts_data );
		}

		return $podcasts_data;
	}

	/**
	 * Validates and sanitizes the columns parameter to ensure it's within the allowed range.
	 *
	 * @since 3.13.0
	 *
	 * @param mixed $columns The columns parameter value.
	 * @return int Validated columns value (MIN_COLUMNS to MAX_COLUMNS).
	 */
	private function validate_columns_parameter( $columns ) {
		// Convert to integer and ensure it's within valid range.
		$columns = intval( $columns );

		// Ensure columns is within the allowed range.
		if ( $columns < self::MIN_COLUMNS ) {
			$columns = self::MIN_COLUMNS;
		} elseif ( $columns > self::MAX_COLUMNS ) {
			$columns = self::MAX_COLUMNS;
		}

		return $columns;
	}

	/**
	 * Validates and sanitizes the description_words parameter to ensure it's a non-negative integer.
	 *
	 * @since 3.13.0
	 *
	 * @param mixed $description_words The description_words parameter value.
	 * @return int Validated description_words value (0 or positive integer).
	 */
	private function validate_description_words_parameter( $description_words ) {
		// Convert to integer and ensure it's non-negative.
		$description_words = intval( $description_words );

		// Ensure description_words is non-negative.
		if ( $description_words < 0 ) {
			$description_words = 0;
		}

		return $description_words;
	}

	/**
	 * Validates and sanitizes the description_chars parameter to ensure it's a non-negative integer.
	 *
	 * @since 3.13.0
	 *
	 * @param mixed $description_chars The description_chars parameter value.
	 * @return int Validated description_chars value (0 or positive integer).
	 */
	private function validate_description_chars_parameter( $description_chars ) {
		// Convert to integer and ensure it's non-negative.
		$description_chars = intval( $description_chars );

		// Ensure description_chars is non-negative.
		if ( $description_chars < 0 ) {
			$description_chars = 0;
		}

		return $description_chars;
	}

	/**
	 * Truncates podcast descriptions based on word or character limits using WordPress native functions.
	 *
	 * @since 3.13.0
	 *
	 * @param array $podcasts          Array of podcast data.
	 * @param int   $description_words Maximum number of words (0 = no limit).
	 * @param int   $description_chars Maximum number of characters (0 = no limit).
	 * @return array Array of podcast data with truncated descriptions.
	 */
	private function truncate_descriptions( $podcasts, $description_words, $description_chars ) {
		foreach ( $podcasts as $index => $podcast ) {
			if ( empty( $podcast['description'] ) ) {
				continue;
			}

			$description = $podcast['description'];

			// Priority: description_chars > description_words.
			if ( $description_chars > 0 ) {
				// Use WordPress native function for character-based truncation.
				$description = $this->truncate_by_characters( $description, $description_chars );
			} elseif ( $description_words > 0 ) {
				// Use WordPress native function for word-based truncation.
				$description = $this->truncate_by_words( $description, $description_words );
			}

			$podcasts[ $index ]['description'] = $description;
		}

		return $podcasts;
	}

	/**
	 * Truncates text by character count using WordPress native functions.
	 *
	 * @since 3.13.0
	 *
	 * @param string $text  Text to truncate.
	 * @param int    $limit Maximum number of characters.
	 * @return string Truncated text.
	 */
	private function truncate_by_characters( $text, $limit ) {
		// Strip HTML tags for accurate character counting.
		$stripped_text = wp_strip_all_tags( $text );

		// Use WordPress native mb_strimwidth function for proper UTF-8 character truncation.
		return mb_strimwidth( $stripped_text, 0, $limit, '…' );
	}

	/**
	 * Truncates text by word count using WordPress native functions.
	 *
	 * @since 3.13.0
	 *
	 * @param string $text  Text to truncate.
	 * @param int    $limit Maximum number of words.
	 * @return string Truncated text.
	 */
	private function truncate_by_words( $text, $limit ) {
		// Use WordPress native wp_trim_words function for proper word truncation.
		return wp_trim_words( $text, $limit, '…' );
	}
}
