<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 *
 * @author      Hugh Lashbrooke
 * @category    Class
 * @package     SeriouslySimplePodcasting/Classes
 * @since       1.0
 */
class SSP_Admin {
	private $version;
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $template_path;
	private $token;
	private $home_url;
	private $script_suffix;

	/**
	 * Constructor
	 * @param 	string $file Plugin base file
	 */
	public function __construct( $file, $version ) {

		$this->version = $version;

		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->template_path = trailingslashit( $this->dir ) . 'templates/';
		$this->home_url = trailingslashit( home_url() );
		$this->token = 'podcast';

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Regsiter podcast post type and taxonomies
		add_action( 'init', array( $this, 'register_post_type' ), 1 );

		// Register podcast feed
		add_action( 'init', array( $this, 'add_feed' ), 1 );

		// Hide WP SEO footer text for podcast RSS feed
		add_filter( 'wpseo_include_rss_footer', array( $this, 'hide_wp_seo_rss_footer' ) );

		// Handle v1.x feed URL as well as feed URLs for default permalinks
		add_action( 'init', array( $this, 'redirect_old_feed' ) );

		// Setup custom permalink structures
		add_action( 'init', array( $this, 'setup_permastruct' ), 10 );

		if ( is_admin() ) {

			add_action( 'admin_init', array( $this, 'update_enclosures' ) );

			// Episode meta box
			add_action( 'admin_init', array( $this, 'register_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'meta_box_save' ), 10, 1 );

			// Episode edit screen
			add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
			add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

			// Admin JS & CSS
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ), 10 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 10 );

			// Episodes list table
			add_filter( 'manage_edit-' . $this->token . '_columns', array( $this, 'register_custom_column_headings' ), 10, 1 );
			add_action( 'manage_posts_custom_column', array( $this, 'register_custom_columns' ), 10, 2 );

			// Series list table
			add_filter( 'manage_edit-series_columns' , array( $this, 'edit_series_columns' ) );
            add_filter( 'manage_series_custom_column' , array( $this, 'add_series_columns' ), 1, 3 );

            // Dashboard widgets
            add_filter( 'dashboard_glance_items', array( $this, 'glance_items' ), 10, 1 );

            // Appreciation links
            add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );

            // Add footer text to dashboard
            add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

            // Clear the cache on post save.
            add_action( 'save_post', array( $this, 'invalidate_cache' ), 10, 2 );

		}

		// Add ajax action for plugin rating
		add_action( 'wp_ajax_ssp_rated', array( $this, 'rated' ) );

		// Add ajax action for customising episode embed code
		add_action( 'wp_ajax_update_episode_embed_code', array( $this, 'update_episode_embed_code' ) );

		// Setup activation and deactivation hooks
		register_activation_hook( $file, array( $this, 'activate' ) );
		register_deactivation_hook( $file, array( $this, 'deactivate' ) );

		add_action( 'init', array( $this, 'update' ), 11 );
	}

	/**
	 * Setup custom permalink structures
	 * @return void
	 */
	public function setup_permastruct() {

		// Episode download & player URLs
		add_rewrite_rule( '^podcast-download/([^/]*)/([^/]*)/?', 'index.php?podcast_episode=$matches[1]', 'top' );
		add_rewrite_rule( '^podcast-player/([^/]*)/([^/]*)/?', 'index.php?podcast_episode=$matches[1]&podcast_ref=player', 'top' );

		// Custom query variables
		add_rewrite_tag( '%podcast_episode%', '([^&]+)' );
		add_rewrite_tag( '%podcast_ref%', '([^&]+)' );

		// Series feed URLs
		$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
		add_rewrite_rule( '^feed/' . $feed_slug . '/([^/]*)/?', 'index.php?feed=podcast&podcast_series=$matches[1]', 'top' );
		add_rewrite_tag( '%podcast_series%', '([^&]+)' );
	}

	/**
	 * Register 'podcast' post type
	 * @return void
	 */
	public function register_post_type() {

		$labels = array(
			'name' => _x( 'Podcast', 'post type general name' , 'seriously-simple-podcasting' ),
			'singular_name' => _x( 'Podcast', 'post type singular name' , 'seriously-simple-podcasting' ),
			'add_new' => _x( 'Add New', 'podcast' , 'seriously-simple-podcasting' ),
			'add_new_item' => sprintf( __( 'Add New %s' , 'seriously-simple-podcasting' ), __( 'Episode' , 'seriously-simple-podcasting' ) ),
			'edit_item' => sprintf( __( 'Edit %s' , 'seriously-simple-podcasting' ), __( 'Episode' , 'seriously-simple-podcasting' ) ),
			'new_item' => sprintf( __( 'New %s' , 'seriously-simple-podcasting' ), __( 'Episode' , 'seriously-simple-podcasting' ) ),
			'all_items' => sprintf( __( 'All %s' , 'seriously-simple-podcasting' ), __( 'Episodes' , 'seriously-simple-podcasting' ) ),
			'view_item' => sprintf( __( 'View %s' , 'seriously-simple-podcasting' ), __( 'Episode' , 'seriously-simple-podcasting' ) ),
			'search_items' => sprintf( __( 'Search %a' , 'seriously-simple-podcasting' ), __( 'Episodes' , 'seriously-simple-podcasting' ) ),
			'not_found' =>  sprintf( __( 'No %s Found' , 'seriously-simple-podcasting' ), __( 'Episodes' , 'seriously-simple-podcasting' ) ),
			'not_found_in_trash' => sprintf( __( 'No %s Found In Trash' , 'seriously-simple-podcasting' ), __( 'Episodes' , 'seriously-simple-podcasting' ) ),
			'parent_item_colon' => '',
			'menu_name' => __( 'Podcast' , 'seriously-simple-podcasting' ),
			'filter_items_list' => sprintf( __( 'Filter %s list' , 'seriously-simple-podcasting' ), __( 'Episode' , 'seriously-simple-podcasting' ) ),
			'items_list_navigation' => sprintf( __( '%s list navigation' , 'seriously-simple-podcasting' ), __( 'Episode' , 'seriously-simple-podcasting' ) ),
			'items_list' => sprintf( __( '%s list' , 'seriously-simple-podcasting' ), __( 'Episode' , 'seriously-simple-podcasting' ) ),
		);

		$slug = apply_filters( 'ssp_archive_slug', __( 'podcast' , 'seriously-simple-podcasting' ) );

		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => true,
			'query_var' => true,
			'can_export' => true,
			'rewrite' => array( 'slug' => $slug, 'feeds' => true ),
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'comments', 'author', 'custom-fields', 'publicize' ),
			'menu_position' => 5,
			'menu_icon' => 'dashicons-microphone',
			'show_in_rest' => true,
		);

		$args = apply_filters( 'ssp_register_post_type_args', $args );

		register_post_type( $this->token, $args );

		$this->register_taxonomies();
	}

	/**
	 * Register taxonomies
	 * @return void
	 */
	private function register_taxonomies() {

		$podcast_post_types = ssp_post_types( true );

        $series_labels = array(
            'name' => __( 'Podcast Series' , 'seriously-simple-podcasting' ),
            'singular_name' => __( 'Series', 'seriously-simple-podcasting' ),
            'search_items' =>  __( 'Search Series' , 'seriously-simple-podcasting' ),
            'all_items' => __( 'All Series' , 'seriously-simple-podcasting' ),
            'parent_item' => __( 'Parent Series' , 'seriously-simple-podcasting' ),
            'parent_item_colon' => __( 'Parent Series:' , 'seriously-simple-podcasting' ),
            'edit_item' => __( 'Edit Series' , 'seriously-simple-podcasting' ),
            'update_item' => __( 'Update Series' , 'seriously-simple-podcasting' ),
            'add_new_item' => __( 'Add New Series' , 'seriously-simple-podcasting' ),
            'new_item_name' => __( 'New Series Name' , 'seriously-simple-podcasting' ),
            'menu_name' => __( 'Series' , 'seriously-simple-podcasting' ),
            'view_item' => __( 'View Series' , 'seriously-simple-podcasting' ),
            'popular_items' => __( 'Popular Series' , 'seriously-simple-podcasting' ),
            'separate_items_with_commas' => __( 'Separate series with commas' , 'seriously-simple-podcasting' ),
            'add_or_remove_items' => __( 'Add or remove Series' , 'seriously-simple-podcasting' ),
            'choose_from_most_used' => __( 'Choose from the most used Series' , 'seriously-simple-podcasting' ),
            'not_found' => __( 'No Series Found' , 'seriously-simple-podcasting' ),
            'items_list_navigation' => __( 'Series list navigation' , 'seriously-simple-podcasting' ),
            'items_list' => __( 'Series list' , 'seriously-simple-podcasting' ),
        );

        $series_args = array(
            'public' => true,
            'hierarchical' => true,
            'rewrite' => array( 'slug' => apply_filters( 'ssp_series_slug', 'series' ) ),
            'labels' => $series_labels,
            'show_in_rest' => true,
        );

        $series_args = apply_filters( 'ssp_register_taxonomy_args', $series_args, 'series' );

        register_taxonomy( 'series', $podcast_post_types, $series_args );

        // Add Tags to podcast post type
        if ( apply_filters( 'ssp_use_post_tags', true ) ) {
        	register_taxonomy_for_object_type( 'post_tag', $this->token );
        }
    }

    /**
	 * Register columns for podcast list table
	 * @param  array $defaults Default columns
	 * @return array           Modified columns
	 */
	public function register_custom_column_headings( $defaults ) {
		$new_columns = apply_filters( 'ssp_admin_columns_episodes', array( 'series' => __( 'Series' , 'seriously-simple-podcasting' ) , 'image' => __( 'Image' , 'seriously-simple-podcasting' ) ) );

		// remove date column
		unset( $defaults['date'] );

		// add new columns before last default one
		$columns = array_slice( $defaults, 0, -1 ) + $new_columns + array_slice( $defaults, -1 );

		return $columns;
	}

    /**
     * Display column data in podcast list table
     * @param  string  $column_name Name of current column
     * @param  integer $id          ID of episode
     * @return void
     */
	public function register_custom_columns( $column_name, $id ) {
		global $ss_podcasting;

		switch ( $column_name ) {

			case 'series':
				$terms = wp_get_post_terms( $id , 'series' );
				$term_names = wp_list_pluck( $terms, 'name' );
				echo join( ', ', $term_names );
			break;

			case 'image':
				$value = $ss_podcasting->get_image( $id, 40 );
				echo $value;
			break;

			default:
			break;

		}
	}

	/**
	 * Register solumns for series list table
	 * @param  array $columns Default columns
	 * @return array          Modified columns
	 */
	public function edit_series_columns( $columns ) {

        unset( $columns['description'] );
        unset( $columns['posts'] );

        $columns['series_feed_url'] = __( 'Series feed URL' , 'seriously-simple-podcasting' );
        $columns['posts'] = __( 'Episodes' , 'seriously-simple-podcasting' );

        $columns = apply_filters( 'ssp_admin_columns_series', $columns );

        return $columns;
    }

    /**
     * Display column data in series list table
     *
     * @param string  $column_data Default column content
     * @param string  $column_name Name of current column
     * @param integer $term_id     ID of term
     *
     * @return string
     */
    public function add_series_columns( $column_data , $column_name , $term_id ) {

        switch ( $column_name ) {
            case 'series_feed_url':
            	$series = get_term( $term_id, 'series' );
            	$series_slug = $series->slug;

            	if ( get_option( 'permalink_structure' ) ) {
					$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
					$feed_url = $this->home_url . 'feed/' . $feed_slug . '/' . $series_slug;
				} else {
					$feed_url = add_query_arg(
						array(
							'feed' => $this->token,
							'podcast_series' => $series_slug
						),
						$this->home_url
					);
				}

                $column_data = '<a href="' . esc_attr( $feed_url ) . '" target="_blank">' . esc_html( $feed_url ) . '</a>';
            break;
        }

        return $column_data;
    }

    /**
     * Create custom dashboard message
     * @param  array $messages Default messages
     * @return array           Modified messages
     */
	public function updated_messages( $messages ) {
	  global $post, $post_ID;

	  $messages[ $this->token ] = array(
	    0 => '',
	    1 => sprintf( __( 'Episode updated. %sView episode%s.' , 'seriously-simple-podcasting' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    2 => __( 'Custom field updated.' , 'seriously-simple-podcasting' ),
	    3 => __( 'Custom field deleted.' , 'seriously-simple-podcasting' ),
	    4 => __( 'Episode updated.' , 'seriously-simple-podcasting' ),
	    5 => isset($_GET['revision']) ? sprintf( __( 'Episode restored to revision from %s.' , 'seriously-simple-podcasting' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
	    6 => sprintf( __( 'Episode published. %sView episode%s.' , 'seriously-simple-podcasting' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    7 => __( 'Episode saved.' , 'seriously-simple-podcasting' ),
	    8 => sprintf( __( 'Episode submitted. %sPreview episode%s.' , 'seriously-simple-podcasting' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
	    9 => sprintf( __( 'Episode scheduled for: %1$s. %2$sPreview episode%3$s.' , 'seriously-simple-podcasting' ), '<strong>' . date_i18n( __( 'M j, Y @ G:i' , 'seriously-simple-podcasting' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    10 => sprintf( __( 'Episode draft updated. %sPreview episode%s.' , 'seriously-simple-podcasting' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
	  );

	  return $messages;
	}

	/**
	 * Register podcast episode details meta boxes
	 * @return void
	 */
	public function register_meta_boxes () {

		// Get all podcast post types
		$podcast_post_types = ssp_post_types( true );

		// Add meta box to each post type
		foreach ( (array) $podcast_post_types as $post_type ) {
			add_action( 'add_meta_boxes_' . $post_type, array( $this, 'meta_box_setup' ), 10, 1 );
		}
	}

	/**
	 * Create meta box on episode edit screen
	 * @return void
	 */
	public function meta_box_setup ( $post ) {
		global $pagenow;

		add_meta_box( 'podcast-episode-data', __( 'Podcast Episode Details' , 'seriously-simple-podcasting' ), array( $this, 'meta_box_content' ), $post->post_type, 'normal', 'high' );

		if( 'post.php' == $pagenow && 'publish' == $post->post_status && function_exists( 'get_post_embed_html' ) ) {
			add_meta_box( 'episode-embed-code', __( 'Episode Embed Code' , 'seriously-simple-podcasting' ), array( $this, 'embed_code_meta_box_content' ), $post->post_type, 'side', 'low' );
		}

		// Allow more metaboxes to be added
		do_action( 'ssp_meta_boxes', $post );

	}

	/**
	 * Get content for episode embed code meta box
	 * @param  object $post Current post object
	 * @return void
	 */
	public function embed_code_meta_box_content ( $post ) {

		// Get post embed code
		$embed_code = get_post_embed_html( 500, 350, $post );

		// Generate markup for meta box
		$html = '<p><em>' . __( 'Customise the size of your episode embed below, then copy the HTML to your clipboard.', 'seriously-simple-podcasting' ) . '</em></p>';
		$html .= '<p><label for="episode_embed_code_width">' . __( 'Width:', 'seriously-simple-podcasting' ) . '</label> <input id="episode_embed_code_width" class="episode_embed_code_size_option" type="number" value="500" length="3" min="0" step="1" /> &nbsp;&nbsp;&nbsp;&nbsp;<label for="episode_embed_code_height">' . __( 'Height:', 'seriously-simple-podcasting' ) . '</label> <input id="episode_embed_code_height" class="episode_embed_code_size_option" type="number" value="350" length="3" min="0" step="1" /></p>';
		$html .= '<p><textarea readonly id="episode_embed_code">' . esc_textarea( $embed_code ) . '</textarea></p>';

		echo $html;
	}

	/**
	 * Update the epiaode embed code via ajax
	 * @return void
	 */
	public function update_episode_embed_code () {

		// Make sure we have a valid post ID
		if( empty( $_POST['post_id'] ) ) {
			return;
		}

		// Get info for embed code
		$post_id = (int) $_POST['post_id'];
		$width = (int) $_POST['width'];
		$height = (int) $_POST['height'];

		// Generate embed code
		echo get_post_embed_html( $width, $height, $post_id );

		// Exit after ajax request
		exit;
	}

	/**
	 * Load content for episode meta box
	 * @return void
	 */
	public function meta_box_content() {
		global $post_id;

		$field_data = $this->custom_fields();

		$html = '';

		$html .= '<input type="hidden" name="seriouslysimple_' . $this->token . '_nonce" id="seriouslysimple_' . $this->token . '_nonce" value="' . wp_create_nonce( plugin_basename( $this->dir ) ) . '" />';

		if ( 0 < count( $field_data ) ) {
			$html .= '<table class="form-table">' . "\n";
			$html .= '<tbody>' . "\n";

			$html .= '<input id="seriouslysimple_post_id" type="hidden" value="'. $post_id . '" />';

			foreach ( $field_data as $k => $v ) {
				$data = $v['default'];
				$saved = get_post_meta( $post_id, $k, true );
				if ( $saved ) {
					$data = $saved;
				}

				$class = '';
				if ( isset( $v['class'] ) ) {
					$class = $v['class'];
				}

				$disabled = false;
				if ( isset( $v['disabled'] ) && $v['disabled'] ) {
					$disabled = true;
				}

				if ( $k == 'audio_file' ) {
					$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="text" id="upload_audio_file" class="regular-text" value="' . esc_attr( $data ) . '" /> <input type="button" class="button" id="upload_audio_file_button" value="' . __( 'Upload File' , 'seriously-simple-podcasting' ) . '" data-uploader_title="' . __( 'Choose a file', 'seriously-simple-podcasting' ) . '" data-uploader_button_text="' . __( 'Insert podcast file', 'seriously-simple-podcasting' ) . '" />' . "\n";
					$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
					$html .= '</td><tr/>' . "\n";
				} else {
					if ( $v['type'] == 'checkbox' ) {
						$html .= '<tr valign="top"><th scope="row">' . $v['name'] . '</th><td><input name="' . esc_attr( $k ) . '" type="checkbox" class="' . esc_attr( $class ) . '" id="' . esc_attr( $k ) . '" ' . checked( 'on' , $data , false ) . ' /> <label for="' . esc_attr( $k ) . '"><span class="description">' . $v['description'] . '</span></label>' . "\n";
						$html .= '</td><tr/>' . "\n";
					} elseif ( $v['type'] == 'radio' ) {
						$html .= '<tr valign="top"><th scope="row">' . $v['name'] . '</th><td>' ."\n";
						foreach( $v['options'] as $option => $label ) {

							$html .= '<input style="vertical-align: bottom;" name="' . esc_attr( $k ) . '" type="radio" class="' . esc_attr( $class ) . '" id="' . esc_attr( $k ) . '_' . esc_attr( $option ) . '" ' . checked( $option , $data , false ) . ' value="' . esc_attr( $option ) . '" /> <label style="margin-right:10px;" for="' . esc_attr( $k ) . '_' . esc_attr( $option ) . '">' . esc_html( $label ) . '</label>' . "\n";
						}
						$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
						$html .= '</td><tr/>' . "\n";
					} elseif ( $v['type'] == 'datepicker' ) {
						$display_date = '';
						if( $data ) {
							$display_date = date( 'j F, Y', strtotime( $data ) );
						}
						$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '_display">' . $v['name'] . '</label></th><td class="hasDatepicker"><input type="text" id="' . esc_attr( $k ) . '_display" class="ssp-datepicker ' . esc_attr( $class ) . '" value="' . esc_attr( $display_date ) . '" />' . "\n";
						$html .= '<input name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" type="hidden" value="' . esc_attr( $data ) . '" />' . "\n";
						$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
						$html .= '</td><tr/>' . "\n";
					} else {
						$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="' . esc_attr( $class ) . '" value="' . esc_attr( $data ) . '" />' . "\n";
						$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
						$html .= '</td><tr/>' . "\n";
					}
				}
			}

			$html .= '</tbody>' . "\n";
			$html .= '</table>' . "\n";
		}

		echo $html;
	}

	/**
	 * Save episoe meta box content
	 * @param  integer $post_id ID of post
	 * @return mixed
	 */
	public function meta_box_save( $post_id ) {
		global $ss_podcasting;

		$podcast_post_types = ssp_post_types( true );

		// Post type check
		if ( ! in_array( get_post_type(), $podcast_post_types ) ) {
			return false;
		}

		// Security check
		if ( ! isset( $_POST['seriouslysimple_' . $this->token . '_nonce'] ) || ! ( isset( $_POST['seriouslysimple_' . $this->token . '_nonce'] ) && wp_verify_nonce( $_POST['seriouslysimple_' . $this->token . '_nonce'], plugin_basename( $this->dir ) ) ) ) {
			return $post_id;
		}

		// User capability check
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		$field_data = $this->custom_fields();
		$enclosure = '';

		foreach ( $field_data as $k => $field ) {

			if( 'embed_code' == $k ) {
				continue;
			}

			$val = '';
			if ( isset( $_POST[ $k ] ) ) {
				$val = strip_tags( trim( $_POST[ $k ] ) );
			}

			if ( $k == 'audio_file' ) {
				$enclosure = $val;
			}

			update_post_meta( $post_id, $k, $val );
		}

		if ( $enclosure ) {

			// Get file duration
			if ( get_post_meta( $post_id, 'duration', true ) == '' ) {
				$duration = $ss_podcasting->get_file_duration( $enclosure );
				if ( $duration ) {
					update_post_meta( $post_id , 'duration' , $duration );
				}
			}

			// Get file size
			if ( get_post_meta( $post_id, 'filesize', true ) == '' ) {
				$filesize = $ss_podcasting->get_file_size( $enclosure );
				if ( $filesize ) {

					if ( isset( $filesize['formatted'] ) ) {
						update_post_meta( $post_id, 'filesize', $filesize['formatted'] );
					}

					if ( isset( $filesize['raw'] ) ) {
						update_post_meta( $post_id, 'filesize_raw', $filesize['raw'] );
					}

				}
			}

			// Save podcast file to 'enclosure' meta field for standards-sake
			update_post_meta( $post_id, 'enclosure', $enclosure );

		}

	}

	/**
	 * Setup custom fields for episodes
	 * @return array Custom fields
	 */
	public function custom_fields() {
		global $pagenow;
		$fields = array();

		$fields['episode_type'] = array(
			'name' => __( 'Episode type:' , 'seriously-simple-podcasting' ),
		    'description' => '',
		    'type' => 'radio',
		    'default' => 'audio',
		    'options' => array( 'audio' => __( 'Audio', 'seriously-simple-podcasting' ), 'video' => __( 'Video', 'seriously-simple-podcasting' ) ),
		    'section' => 'info',
		);

		// In v1.14+ the `audio_file` field can actually be either audio or video, but we're keeping the field name here for backwards compatibility
		$fields['audio_file'] = array(
		    'name' => __( 'Podcast file:' , 'seriously-simple-podcasting' ),
		    'description' => __( 'Upload the primary podcast file or paste the file URL here.' , 'seriously-simple-podcasting' ),
		    'type' => 'url',
		    'default' => '',
		    'section' => 'info',
		);

		$fields['duration'] = array(
		    'name' => __( 'Duration:' , 'seriously-simple-podcasting' ),
		    'description' => __( 'Duration of podcast file for display (calculated automatically if possible).' , 'seriously-simple-podcasting' ),
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info',
		);

		$fields['filesize'] = array(
		    'name' => __( 'File size:' , 'seriously-simple-podcasting' ),
		    'description' => __( 'Size of the podcast file for display (calculated automatically if possible).' , 'seriously-simple-podcasting' ),
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info',
		);

		$fields['date_recorded'] = array(
		    'name' => __( 'Date recorded:' , 'seriously-simple-podcasting' ),
		    'description' => __( 'The date on which this episode was recorded.' , 'seriously-simple-podcasting' ),
		    'type' => 'datepicker',
		    'default' => '',
		    'section' => 'info',
		);

		$fields['explicit'] = array(
		    'name' => __( 'Explicit:' , 'seriously-simple-podcasting' ),
		    'description' => __( 'Mark this episode as explicit.' , 'seriously-simple-podcasting' ),
		    'type' => 'checkbox',
		    'default' => '',
		    'section' => 'info',
		);

		$fields['block'] = array(
		    'name' => __( 'Block:' , 'seriously-simple-podcasting' ),
		    'description' => __( 'Block this episode from appearing in the iTunes & Google Play podcast libraries.' , 'seriously-simple-podcasting' ),
		    'type' => 'checkbox',
		    'default' => '',
		    'section' => 'info',
		);

		return apply_filters( 'ssp_episode_fields', $fields );
	}

	/**
	 * Adding podcast episodes to 'At a glance' dashboard widget
	 * @param  array $items Existing items
	 * @return array        Updated items
	 */
	public function glance_items( $items = array() ) {

		$num_posts = count( ssp_episodes( -1, '', false, 'glance' ) );

		$post_type_object = get_post_type_object( $this->token );

		$text = _n( '%s Episode', '%s Episodes', $num_posts, 'seriously-simple-podcasting' );
		$text = sprintf( $text, number_format_i18n( $num_posts ) );

		if ( $post_type_object && current_user_can( $post_type_object->cap->edit_posts ) ) {
			$items[] = sprintf( '<a class="%1$s-count" href="edit.php?post_type=%1$s">%2$s</a>', $this->token, $text ) . "\n";
		} else {
			$items[] = sprintf( '<span class="%1$s-count">%2$s</span>', $this->token, $text ) . "\n";
		}

		return $items;
	}

	/**
	 * Adding appreciation links to the SSP record in the plugin list table
	 * @param  array  $plugin_meta Default plugin meta links
	 * @param  string $plugin_file Plugin file
	 * @param  array  $plugin_data Array of plugin data
	 * @param  string $status      Plugin status
	 * @return array               Modified plugin meta links
	 */
	public function plugin_row_meta ( $plugin_meta = array(), $plugin_file = '', $plugin_data = array(), $status = '' ) {

		if ( ! isset( $plugin_data['slug'] ) || 'seriously-simple-podcasting' != $plugin_data['slug'] ) {
			return $plugin_meta;
		}

		$plugin_meta['docs'] = '<a href="http://www.seriouslysimplepodcasting.com/documentation/" target="_blank">' . __( 'Documentation', 'seriously-simple-podcasting' ) . '</a>';
		$plugin_meta['addons'] = '<a href="http://www.seriouslysimplepodcasting.com/add-ons/" target="_blank">' . __( 'Add-ons', 'seriously-simple-podcasting' ) . '</a>';
		$plugin_meta['review'] = '<a href="https://wordpress.org/support/view/plugin-reviews/' . $plugin_data['slug'] . '?rate=5#postform" target="_blank">' . __( 'Write a review', 'seriously-simple-podcasting' ) . '</a>';

		return $plugin_meta;
	}

	/**
	 * Modify the 'enter title here' text
	 * @param  string $title Default text
	 * @return string        Modified text
	 */
	public function enter_title_here( $title ) {
		if ( get_post_type() == $this->token ) {
			$title = __( 'Enter episode title here', 'seriously-simple-podcasting' );
		}
		return $title;
	}

	/**
	 * Load admin CSS
	 * @return void
	 */
	public function enqueue_admin_styles() {
		wp_register_style( 'ssp-admin', esc_url( $this->assets_url . 'css/admin.css' ), array(), $this->version );
		wp_enqueue_style( 'ssp-admin' );

		// Datepicker
		wp_enqueue_style( 'jquery-ui-datepicker', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css' );
	}

	/**
	 * Load admin JS
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		wp_register_script( 'ssp-admin', esc_url( $this->assets_url . 'js/admin' . $this->script_suffix . '.js' ), array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ), $this->version );
		wp_enqueue_script( 'ssp-admin' );
	}

	/**
	 * Ensure thumbnail support on site
	 * @return void
	 */
	public function ensure_post_thumbnails_support() {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			add_theme_support( 'post-thumbnails' );
		}
	}

	/**
	 * Load plugin text domain
	 * @return void
	 */
	public function load_localisation() {
		load_plugin_textdomain( 'seriously-simple-podcasting', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Load localisation
	 * @return void
	 */
	public function load_plugin_textdomain() {
	    $domain = 'seriously-simple-podcasting';
	    // The "plugin_locale" filter is also used in load_plugin_textdomain()
	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

    /**
     * Register podcast feed
     * @return void
     */
    public function add_feed() {
    	$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
		add_feed( $feed_slug, array( $this, 'feed_template' ) );
	}

	/**
	 * Hide RSS footer created by WordPress SEO from podcast RSS feed
	 * @param  boolean $include_footer Default inclusion value
	 * @return boolean                 Modified inclusion value
	 */
	public function hide_wp_seo_rss_footer ( $include_footer = true ) {

		if ( is_feed( 'podcast' ) ) {
			$include_footer = false;
		}

		return $include_footer;
	}

	/**
	 * Load feed template
	 * @return void
	 */
    public function feed_template() {
    	global $wp_query;

    	// Prevent 404 on feed
    	$wp_query->is_404 = false;
    	status_header( 200 );

    	$file_name = 'feed-podcast.php';

    	$user_template_file = apply_filters( 'ssp_feed_template_file', trailingslashit( get_stylesheet_directory() ) . $file_name );

		// Any functions hooked in here must NOT output any data or else feed will break
		do_action( 'ssp_before_feed' );

    	// Load user feed template if it exists, otherwise use plugin template
    	if ( file_exists( $user_template_file ) ) {
    		require( $user_template_file );
    	} else {
    		require( $this->template_path . $file_name );
    	}

    	// Any functions hooked in here must NOT output any data or else feed will break
    	do_action( 'ssp_after_feed' );
	}

	/**
	 * Redirect feed URLs created prior to v1.8 to ensure backwards compatibility
	 * @return void
	 */
	public function redirect_old_feed() {
		if ( isset( $_GET['feed'] ) && in_array( $_GET['feed'], array( 'podcast', 'itunes' ) ) ) {
			$this->feed_template();
			exit;
		}
	}

	/**
	 * Flush rewrite rules on plugin acivation
	 * @return void
	 */
	public function activate() {

		// Setup all custom URL rules
		$this->register_post_type();
		$this->add_feed();
		$this->setup_permastruct();

		// Flush permalinks
		flush_rewrite_rules( true );
	}

	/**
	 * Flush rewrite rules on plugin deacivation
	 * @return void
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Run functions on plugin update/activation
	 * @return void
	 */
	public function update () {

		$previous_version = get_option( 'ssp_version', '1.0' );

		if ( version_compare( $previous_version, '1.13.1', '<' ) ) {
			flush_rewrite_rules();
		}

		update_option( 'ssp_version', $this->version );
	}

	/**
	 * Update 'enclosure' meta field to 'audio_file' meta field
	 * @return void
	 */
	public function update_enclosures () {

		// Allow forced re-run of update if necessary
		if ( isset( $_GET['ssp_update_enclosures'] ) ) {
			delete_option( 'ssp_update_enclosures' );
		}

		// Check if update has been run
		$update_run = get_option( 'ssp_update_enclosures', false );

		if ( $update_run ) {
			return;
		}

		// Get IDs of all posts with enclosures
		$args = array(
			'post_type' => 'any',
			'post_status' => 'any',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'enclosure',
					'compare' => '!=',
					'value' => '',
				),
			),
			'fields' => 'ids',
		);

		$posts_with_enclosures = get_posts( $args );

		if ( 0 == count( $posts_with_enclosures ) ) {
			return;
		}

		// Add `audio_file` meta field to all posts with enclosures
		foreach ( (array) $posts_with_enclosures as $post_id ) {

			// Get existing enclosure
			$enclosure = get_post_meta( $post_id, 'enclosure', true );

			// Add audio_file field
			if ( $enclosure ) {
				update_post_meta( $post_id, 'audio_file', $enclosure );
			}

		}

		// Mark update as having been run
		update_option( 'ssp_update_enclosures', 'run' );
	}

	/**
	 * Add rating link to admin footer on SSP settings pages
	 * @param  string $footer_text Default footer text
	 * @return string              Modified footer text
	 */
	public function admin_footer_text( $footer_text ) {

		// Check to make sure we're on a SSP settings page
		if ( ( isset( $_GET['page'] ) && 'podcast_settings' == esc_attr( $_GET['page'] ) ) && apply_filters( 'ssp_display_admin_footer_text', true ) ) {

			// Change the footer text
			if ( ! get_option( 'ssp_admin_footer_text_rated' ) ) {
				$footer_text = sprintf( __( 'If you like %1$sSeriously Simple Podcasting%2$s please leave a %3$s&#9733;&#9733;&#9733;&#9733;&#9733;%4$s rating. A huge thank you in advance!', 'seriously-simple-podcasting' ), '<strong>', '</strong>', '<a href="https://wordpress.org/support/view/plugin-reviews/seriously-simple-podcasting?filter=5#postform" target="_blank" class="ssp-rating-link" data-rated="' . __( 'Thanks!', 'seriously-simple-podcasting' ) . '">', '</a>' );
				$footer_text .= "<script type='text/javascript'>
					jQuery('a.ssp-rating-link').click(function() {
						jQuery.post( '" . admin_url( 'admin-ajax.php' ) . "', { action: 'ssp_rated' } );
						jQuery(this).parent().text( jQuery(this).data( 'rated' ) );
					});
				</script>";
			} else {
				$footer_text = sprintf( __( '%1$sThank you for publishing with %2$sSeriously Simple Podcasting%3$s.%4$s', 'seriously-simple-podcasting' ), '<span id="footer-thankyou">', '<a href="http://www.seriouslysimplepodcasting.com/" target="_blank">', '</a>', '</span>' );
			}

		}

		return $footer_text;
	}

	/**
	 * Indicate that plugin has been rated
	 * @return void
	 */
	public function rated() {
		update_option( 'ssp_admin_footer_text_rated', 1 );
		die();
	}

	/**
	 * Clear the cache on post save.
	 * @param  int    $id   POST ID
	 * @param  object $post WordPress Post Object
	 * @return void
	 */
	public function invalidate_cache( $id, $post ){

		if ( in_array( $post->post_type, ssp_post_types( true ) ) ){
			wp_cache_delete( 'episodes',    'ssp' );
			wp_cache_delete( 'episode_ids', 'ssp' );
		}

	}


}