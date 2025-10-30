<?php
/**
 * Podcast List shortcode class.
 *
 * @package SeriouslySimplePodcasting
 * @since 3.13.0
 */

namespace SeriouslySimplePodcasting\ShortCodes;

use SeriouslySimplePodcasting\Handlers\Settings_Handler;
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
			'ids'                 => '',
			'columns'             => 1,
			'sort_by'             => 'id',
			'sort'                => 'asc',
			'clickable'           => 'button',
			'show_button'         => 'true',
			'show_description'    => 'true',
			'show_episode_count'  => 'true',
			'description_words'   => 0,
			'description_chars'   => 0,
			'background'          => '#f8f9fa',
			'background_hover'    => '#e9ecef',
			'button_color'        => '#343a40',
			'button_hover_color'  => '#495057',
			'button_text_color'   => '#ffffff',
			'button_text'         => __( 'Listen Now', 'seriously-simple-podcasting' ),
			'title_color'         => '#6c5ce7',
			'episode_count_color' => '#6c757d',
			'description_color'   => '#6c757d',
		);

        $args = shortcode_atts( $defaults, $params, 'ssp_podcasts' );

		// Enqueue CSS only when shortcode is used.
		$this->enqueue_assets();

		// Validate and sanitize parameters.
		$columns             = $this->validate_columns_parameter( $args['columns'] );
		$sort_by             = $this->validate_sort_by_parameter( $args['sort_by'] );
		$sort                = $this->validate_sort_parameter( $args['sort'] );
		$clickable           = $this->validate_clickable_parameter( $args['clickable'] );
		$show_button         = $this->validate_show_button_parameter( $args['show_button'] );
		$show_description    = $this->validate_show_description_parameter( $args['show_description'] );
		$show_episode_count  = $this->validate_show_episode_count_parameter( $args['show_episode_count'] );
		$description_words   = $this->validate_description_words_parameter( $args['description_words'] );
		$description_chars   = $this->validate_description_chars_parameter( $args['description_chars'] );
		$background          = $this->validate_background_parameter( $args['background'] );
		$background_hover    = $this->validate_background_parameter( $args['background_hover'] );
		$button_color        = $this->validate_background_parameter( $args['button_color'] );
		$button_hover_color  = $this->validate_background_parameter( $args['button_hover_color'] );
		$button_text_color   = $this->validate_background_parameter( $args['button_text_color'] );
		$button_text         = $this->validate_button_text_parameter( $args['button_text'] );
		$title_color         = $this->validate_background_parameter( $args['title_color'] );
		$episode_count_color = $this->validate_background_parameter( $args['episode_count_color'] );
		$description_color   = $this->validate_background_parameter( $args['description_color'] );

		// Auto-adjustment: if show_button=false and clickable=button, set clickable=title.
		if ( ! $show_button && 'button' === $clickable ) {
			$clickable = 'title';
		}

		// Process display options for template.
		$show_button_template = $show_button && 'button' === $clickable;
		$wrapper_class        = 'ssp-podcast-card';
		if ( 'card' === $clickable ) {
			$wrapper_class .= ' ssp-podcast-card-clickable';
		}
		$columns_class = 'ssp-podcasts-columns-' . $columns;

		// Get podcasts based on IDs parameter.
        $podcasts = $this->get_podcasts( $args['ids'], $sort_by, $sort );

		// Process descriptions with truncation if needed.
		if ( $show_description && ( $description_words > 0 || $description_chars > 0 ) ) {
			$podcasts = $this->truncate_descriptions( $podcasts, $description_words, $description_chars );
		}

		// Generate CSS variables for all colors.
		$css_vars = $this->generate_css_variables( $background, $background_hover, $button_color, $button_hover_color, $button_text_color, $title_color, $episode_count_color, $description_color );

		// Prepare template data.
		$template_data = compact(
			'podcasts',
			'columns',
			'clickable',
			'show_button',
			'show_description',
			'show_episode_count',
			'button_text',
			'wrapper_class',
			'columns_class',
			'css_vars'
		);

		/**
		 * Allow themes and plugins to modify template data before rendering.
		 *
		 * @filter `ssp/podcast_list/template_data` Allow themes and plugins to modify template data before rendering
		 * @param array $template_data Template data array containing all variables passed to the template
		 * @param array $args Original shortcode arguments
		 */
		$template_data = apply_filters( 'ssp/podcast_list/template_data', $template_data, $args );

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
        // If specific IDs provided
        if ( ! empty( $ids ) ) {
            $podcasts_data = $this->get_podcasts_by_ids( $ids, $sort_by, $sort );

            // Episode count sorting is not supported by DB ordering; sort in PHP
            if ( 'episode_count' === $sort_by ) {
                $podcasts_data = $this->sort_podcasts( $podcasts_data, $sort_by, $sort );
            }

            return $podcasts_data;
        }

		// Prepare additional arguments for get_terms() based on sort_by parameter.
		$additional_args = array();

		// Use database-level sorting for ID and name sorting.
		if ( 'id' === $sort_by || 'name' === $sort_by ) {
			$additional_args['orderby'] = 'id' === $sort_by ? 'term_id' : 'name';
			$additional_args['order']   = strtoupper( $sort );
		}

		// Get podcasts using existing function with additional args.
		$podcasts = ssp_get_podcasts( true, $additional_args );

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
    private function get_podcasts_by_ids( $ids, $sort_by = 'id', $sort = 'asc' ) {
		if ( empty( $ids ) ) {
			return array();
		}

		$podcast_ids = array_map( 'intval', explode( ',', $ids ) );
		$podcast_ids = array_filter( $podcast_ids );

		if ( empty( $podcast_ids ) ) {
			return array();
		}

        // Get podcasts by specific IDs using include; choose order strategy based on sort_by
        $additional_args = array(
            'include' => $podcast_ids,
        );

        $orderby_map = array(
            'id'   => 'term_id',
            'name' => 'name',
        );

        if ( isset( $orderby_map[ $sort_by ] ) ) {
            $additional_args['orderby'] = $orderby_map[ $sort_by ];
            $additional_args['order']   = strtoupper( $sort ) === 'ASC' ? 'ASC' : 'DESC';
        } else {
            // Default to include order when not sorting by DB-supported fields
            $additional_args['orderby'] = 'include';
        }
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
				'description'   => $this->get_podcast_description( $podcast->term_id, $podcast->description ),
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
	 * Retrieves the settings handler service.
	 *
	 * @since 3.13.1
	 *
	 * @return Settings_Handler Settings handler instance.
	 */
	private function get_settings_handler() {
		return ssp_get_service( 'settings_handler' );
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
		$settings_handler = $this->get_settings_handler();
		$feed_image       = $settings_handler->get_feed_image( $podcast_id );

		if ( ! empty( $feed_image ) ) {
			return $feed_image;
		}

		// Final fallback to podcast image (even if it's the default no-image).
		return $podcast_image;
	}

	/**
	 * Retrieves the description for a specific podcast with fallback logic.
	 *
	 * @since 3.13.0
	 *
	 * @param int    $podcast_id Podcast term ID.
	 * @param string $term_description Term description from the podcast taxonomy.
	 * @return string Podcast description with fallback logic applied.
	 */
	private function get_podcast_description( $podcast_id, $term_description = '' ) {
		// First, check if term description exists and is not just whitespace.
		$term_description = trim( $term_description );
		if ( ! empty( $term_description ) ) {
			return $term_description;
		}

		// Fallback to feed description if term description is empty or whitespace.
		$settings_handler = $this->get_settings_handler();
		$feed_description = $settings_handler->get_feed_option( 'data_description', $podcast_id, '' );

		// Return feed description if it's not empty, otherwise return empty string.
		return trim( $feed_description );
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
	 * Validates and sanitizes the show_button parameter to ensure it's a boolean.
	 *
	 * @since 3.13.0
	 *
	 * @param mixed $show_button The show_button parameter value.
	 * @return bool Validated show_button value.
	 */
	private function validate_show_button_parameter( $show_button ) {
		// Explicitly false values.
		if ( 'false' === $show_button || false === $show_button || '0' === $show_button || 0 === $show_button ) {
			return false;
		}

		// For everything else (true values, invalid values, empty values), fall back to default (true).
		return true;
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
		$valid_options = array( 'id', 'name', 'episode_count', 'manual' );

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

		// Prefer mb_strimwidth when available; otherwise fall back to wp_html_excerpt.
		if ( function_exists( 'mb_strimwidth' ) ) {
			return mb_strimwidth( $stripped_text, 0, $limit, '…' );
		}
		// wp_html_excerpt handles multibyte safely and appends the specified ellipsis.
		return wp_html_excerpt( $stripped_text, $limit, '…' );
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

	/**
	 * Validates and sanitizes the background parameter to ensure it's a valid color value.
	 *
	 * @since 3.13.0
	 *
	 * @param mixed $background The background parameter value.
	 * @return string Validated background color value.
	 */
	private function validate_background_parameter( $background ) {
		// Sanitize the color value using WordPress sanitize_hex_color function.
		$sanitized_color = sanitize_hex_color( $background );

		// If sanitize_hex_color returns empty, try to validate as CSS color.
		if ( empty( $sanitized_color ) ) {
			// Allow common CSS color formats: hex, rgb, rgba, hsl, hsla, named colors.
			$background = trim( $background );
			if ( preg_match( '/^(#[0-9a-fA-F]{3,6}|rgb\([^)]+\)|rgba\([^)]+\)|hsl\([^)]+\)|hsla\([^)]+\)|[a-zA-Z]+)$/', $background ) ) {
				return $background;
			}
		}

		// Return sanitized hex color or default if invalid.
		return ! empty( $sanitized_color ) ? $sanitized_color : '#f8f9fa';
	}

	/**
	 * Validates and sanitizes the button text parameter.
	 *
	 * @since 3.13.0
	 *
	 * @param string $button_text The button text parameter.
	 * @return string Sanitized button text.
	 */
	private function validate_button_text_parameter( $button_text ) {
		// Sanitize the button text using WordPress sanitize_text_field function.
		$sanitized_text = sanitize_text_field( $button_text );

		// Return sanitized text or translatable default if empty.
		return ! empty( $sanitized_text ) ? $sanitized_text : __( 'Listen Now', 'seriously-simple-podcasting' );
	}

	/**
	 * Generates CSS variables for all color parameters.
	 *
	 * @since 3.13.0
	 *
	 * @param string $background         Background color value.
	 * @param string $background_hover   Hover background color value.
	 * @param string $button_color       Button background color value.
	 * @param string $button_hover_color Button hover background color value.
	 * @param string $button_text_color  Button text color value.
	 * @param string $title_color        Title color value.
	 * @param string $episode_count_color Episode count color value.
	 * @param string $description_color  Description color value.
	 * @return string CSS variables string.
	 */
	private function generate_css_variables( $background, $background_hover, $button_color, $button_hover_color, $button_text_color, $title_color, $episode_count_color, $description_color ) {
		$css_vars = array(
			'--ssp-podcast-card-bg'       => $background,
			'--ssp-podcast-card-hover-bg' => $background_hover,
			'--ssp-button-bg'             => $button_color,
			'--ssp-button-hover-bg'       => $button_hover_color,
			'--ssp-button-text'           => $button_text_color,
			'--ssp-title-color'           => $title_color,
			'--ssp-episode-count-color'   => $episode_count_color,
			'--ssp-description-color'     => $description_color,
		);

		$css_string = '';
		foreach ( $css_vars as $var => $value ) {
			$css_string .= sprintf( '%s: %s; ', $var, esc_attr( $value ) );
		}

		return trim( $css_string );
	}
}
