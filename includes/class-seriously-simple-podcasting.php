<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Main plugin class
 *
 * @author      Hugh Lashbrooke
 * @category    Class
 * @package     SeriouslySimplePodcasting/Classes
 * @since       1.0
 */
class Seriously_Simple_Podcasting {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $template_path;
	private $token;
	private $home_url;

	/**
	 * Constructor
	 * @param 	string $file Plugin base file
	 * @return 	void
	 */
	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->template_path = trailingslashit( $this->dir ) . 'templates/';
		$this->home_url = trailingslashit( home_url() );
		$this->token = 'podcast';

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Regsiter podcast post type
		add_action('init', array( $this, 'register_post_type' ) );

		// Register podcast feed
		add_action( 'init', array( $this, 'add_feed' ) );

		// Handle v1.x feed URL
		add_action( 'init', array( $this, 'redirect_old_feed' ) );

		// Use built-in templates if selected
		$template_option = get_option( 'ss_podcasting_use_templates' );
		if( ( $template_option && $template_option == 'on' ) ) {
			add_action( 'template_redirect' , array( $this, 'page_templates' ) , 10 );
			add_action( 'widgets_init', array( $this, 'register_widget_area' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		// Add meta data to start of podcast content
		add_filter( 'the_content', array( $this, 'content_meta_data' ) );

		// Add RSS meta tag to site header
		add_action( 'wp_head' , array( $this, 'rss_meta_tag' ) );

		// Add podcast episode to main query loop if setting is activated
		$include_in_main_query = get_option('ss_podcasting_include_in_main_query');
		if( $include_in_main_query && $include_in_main_query == 'on' ) {
			add_filter( 'pre_get_posts' , array( $this, 'add_to_home_query' ) );
		}

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'meta_box_setup' ), 20 );
			add_action( 'save_post', array( $this, 'meta_box_save' ) );
			add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
			add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ), 10 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 10 );
			add_filter( 'manage_edit-' . $this->token . '_columns', array( $this, 'register_custom_column_headings' ), 10, 1 );
			add_action( 'manage_posts_custom_column', array( $this, 'register_custom_columns' ), 10, 2 );
			add_filter( 'manage_edit-series_columns' , array( $this, 'edit_series_columns' ) );
            add_filter( 'manage_series_custom_column' , array( $this, 'add_series_columns' ) , 1 , 3 );
		}

		// Add podcast image size
		add_action( 'after_setup_theme', array( $this, 'ensure_post_thumbnails_support' ) );
		add_action( 'after_setup_theme', array( $this, 'register_image_sizes' ) );

		if( is_file_download() ) {
			add_action( 'wp', array( $this, 'download_file' ), 1 );
		}

		// Fluch rewrite rules on plugin activation
		register_activation_hook( $file, array( $this, 'rewrite_flush' ) );

	}

	/**
	 * Flush reqrite rules on plugin acivation
	 * @return void
	 */
	public function rewrite_flush() {
		$this->register_post_type();
		$this->add_feed();
		flush_rewrite_rules();
	}

	/**
	 * Register 'podcast' post type
	 * @return void
	 */
	public function register_post_type() {

		$labels = array(
			'name' => _x( 'Podcast', 'post type general name' , 'ss-podcasting' ),
			'singular_name' => _x( 'Podcast', 'post type singular name' , 'ss-podcasting' ),
			'add_new' => _x( 'Add New', $this->token , 'ss-podcasting' ),
			'add_new_item' => sprintf( __( 'Add New %s' , 'ss-podcasting' ), __( 'Episode' , 'ss-podcasting' ) ),
			'edit_item' => sprintf( __( 'Edit %s' , 'ss-podcasting' ), __( 'Episode' , 'ss-podcasting' ) ),
			'new_item' => sprintf( __( 'New %s' , 'ss-podcasting' ), __( 'Episode' , 'ss-podcasting' ) ),
			'all_items' => sprintf( __( 'All %s' , 'ss-podcasting' ), __( 'Episodes' , 'ss-podcasting' ) ),
			'view_item' => sprintf( __( 'View %s' , 'ss-podcasting' ), __( 'Episode' , 'ss-podcasting' ) ),
			'search_items' => sprintf( __( 'Search %a' , 'ss-podcasting' ), __( 'Episodes' , 'ss-podcasting' ) ),
			'not_found' =>  sprintf( __( 'No %s Found' , 'ss-podcasting' ), __( 'Episodes' , 'ss-podcasting' ) ),
			'not_found_in_trash' => sprintf( __( 'No %s Found In Trash' , 'ss-podcasting' ), __( 'Episodes' , 'ss-podcasting' ) ),
			'parent_item_colon' => '',
			'menu_name' => __( 'Podcast' , 'ss-podcasting' )
		);

		$slug = __( 'podcast' , 'ss-podcasting' );
		$custom_slug = get_option( 'ss_podcasting_slug' );
		if( $custom_slug && strlen( $custom_slug ) > 0 && $custom_slug != '' ) {
			$slug = $custom_slug;
		}

		$slug = apply_filters( 'ssp_archive_slug', $slug );

		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => $slug, 'feeds' => true ),
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'comments', 'author', 'custom-fields' ),
			'menu_position' => 5,
			'menu_icon' => ''
		);

		register_post_type( $this->token, $args );

		$this->register_taxonomies();
	}

	/**
	 * Register taxonomies
	 * @return void
	 */
	private function register_taxonomies() {

        $series_labels = array(
            'name' => __( 'Series' , 'ss-podcasting' ),
            'singular_name' => __( 'Series', 'ss-podcasting' ),
            'search_items' =>  __( 'Search Series' , 'ss-podcasting' ),
            'all_items' => __( 'All Series' , 'ss-podcasting' ),
            'parent_item' => __( 'Parent Series' , 'ss-podcasting' ),
            'parent_item_colon' => __( 'Parent Series:' , 'ss-podcasting' ),
            'edit_item' => __( 'Edit Series' , 'ss-podcasting' ),
            'update_item' => __( 'Update Series' , 'ss-podcasting' ),
            'add_new_item' => __( 'Add New Series' , 'ss-podcasting' ),
            'new_item_name' => __( 'New Series Name' , 'ss-podcasting' ),
            'menu_name' => __( 'Series' , 'ss-podcasting' )
        );

        $series_args = array(
            'public' => true,
            'hierarchical' => true,
            'rewrite' => array( 'slug' => apply_filters( 'ssp_series_slug', 'series' ) ),
            'labels' => $series_labels
        );

        register_taxonomy( 'series', $this->token, $series_args );

        $keywords_labels = array(
            'name' => __( 'Keywords' , 'ss-podcasting' ),
            'singular_name' => __( 'Keyword', 'ss-podcasting' ),
            'search_items' =>  __( 'Search Keywords' , 'ss-podcasting' ),
            'all_items' => __( 'All Keywords' , 'ss-podcasting' ),
            'parent_item' => __( 'Parent Keyword' , 'ss-podcasting' ),
            'parent_item_colon' => __( 'Parent Keyword:' , 'ss-podcasting' ),
            'edit_item' => __( 'Edit Keyword' , 'ss-podcasting' ),
            'update_item' => __( 'Update Keyword' , 'ss-podcasting' ),
            'add_new_item' => __( 'Add New Keyword' , 'ss-podcasting' ),
            'new_item_name' => __( 'New Keyword Name' , 'ss-podcasting' ),
            'menu_name' => __( 'Keywords' , 'ss-podcasting' )
        );

        $keywords_args = array(
            'public' => true,
            'hierarchical' => false,
            'rewrite' => array( 'slug' => apply_filters( 'ssp_keywords_slug', 'keyword' ) ),
            'labels' => $keywords_labels
        );

        register_taxonomy( 'keywords', $this->token, $keywords_args );
    }

    /**
	 * Register columns for podcast list table
	 * @param  array $defaults Default columns
	 * @return array           Modified columns
	 */
	public function register_custom_column_headings( $defaults ) {
		$new_columns = apply_filters( 'ssp_admin_columns_episodes', array( 'series' => __( 'Series' , 'ss-podcasting' ) , 'image' => __( 'Image' , 'ss-podcasting' ) ) );

		$last_item = '';

		if ( isset( $defaults['date'] ) ) { unset( $defaults['date'] ); }

		if ( count( $defaults ) > 2 ) {
			$last_item = array_slice( $defaults, -1 );
			array_pop( $defaults );
		}
		$defaults = array_merge( $defaults, $new_columns );

		if ( $last_item != '' ) {
			foreach ( $last_item as $k => $v ) {
				$defaults[$k] = $v;
				break;
			}
		}

		return $defaults;
	}

    /**
     * Display column data in podcast list table
     * @param  string  $column_name Name of current column
     * @param  integer $id          ID of episode
     * @return void
     */
	public function register_custom_columns( $column_name, $id ) {
		global $wpdb, $post;

		$meta = get_post_custom( $id );

		switch ( $column_name ) {

			case 'series':
				$value = '';

				$terms = wp_get_post_terms( $id , 'series' );

				$i = 0;
				foreach( $terms as $term ) {
					if( $i > 0 ) { $value .= ', '; }
					else { ++$i; }
					$value .= $term->name;
				}

				echo $value;
			break;

			case 'image':
				$value = '';

				$value = $this->get_image( $id, 40 );

				echo $value;
			break;

			default:
			break;

		}
	}

	/**
	 * Register solumns for series list table
	 * @param  array $defaults Default columns
	 * @return array           Modified columns
	 */
	public function edit_series_columns( $columns ) {

        unset( $columns['description'] );
        unset( $columns['posts'] );

        $columns['series_feed_url'] = __( 'Series feed URL' , 'ss-podcasting' );
        $columns['posts'] = __( 'Episodes' , 'ss-podcasting' );

        $columns = apply_filters( 'ssp_admin_columns_series', $columns );

        return $columns;
    }

    /**
     * Display column data in series list table
     * @param string  $column_data Default column content
     * @param string  $column_name Name of current column
     * @param integer $term_id     ID of term
     */
    public function add_series_columns( $column_data , $column_name , $term_id ) {

        switch ( $column_name ) {
            case 'series_feed_url':
            	$series = get_term( $term_id, 'series' );
            	$series_slug = $series->slug;
            	$feed_url = $this->site_url . 'feed/' . $this->token . '/?podcast_series=' . $series_slug;
                $column_data = '<a href="' . $feed_url . '" target="_blank">' . $feed_url . '</a>';
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
	    0 => '', // Unused. Messages start at index 1.
	    1 => sprintf( __( 'Episode updated. %sView episode%s.' , 'ss-podcasting' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    2 => __( 'Custom field updated.' , 'ss-podcasting' ),
	    3 => __( 'Custom field deleted.' , 'ss-podcasting' ),
	    4 => __( 'Episode updated.' , 'ss-podcasting' ),
	    /* translators: %s: date and time of the revision */
	    5 => isset($_GET['revision']) ? sprintf( __( 'Episode restored to revision from %s.' , 'ss-podcasting' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
	    6 => sprintf( __( 'Episode published. %sView episode%s.' , 'ss-podcasting' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    7 => __( 'Episode saved.' , 'ss-podcasting' ),
	    8 => sprintf( __( 'Episode submitted. %sPreview episode%s.' , 'ss-podcasting' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
	    9 => sprintf( __( 'Episode scheduled for: %1$s. %2$sPreview episode%3$s.' , 'ss-podcasting' ), '<strong>' . date_i18n( __( 'M j, Y @ G:i' , 'ss-podcasting' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    10 => sprintf( __( 'Episode draft updated. %sPreview episode%s.' , 'ss-podcasting' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
	  );

	  return $messages;
	}

	/**
	 * Create meta box on episode edit screen
	 * @return void
	 */
	public function meta_box_setup () {
		add_meta_box( 'episode-data', __( 'Episode Details' , 'ss-podcasting' ), array( $this, 'meta_box_content' ), $this->token, 'normal', 'high' );

		// Allow more metaboxes to be added
		do_action( 'ssp_meta_boxes' );
	}

	/**
	 * Load content for episode meta box
	 * @return void
	 */
	public function meta_box_content() {
		global $post_id;
		$fields = get_post_custom( $post_id );
		$field_data = $this->custom_fields();

		$html = '';

		$html .= '<input type="hidden" name="seriouslysimple_' . $this->token . '_nonce" id="seriouslysimple_' . $this->token . '_nonce" value="' . wp_create_nonce( plugin_basename( $this->dir ) ) . '" />';

		if ( 0 < count( $field_data ) ) {
			$html .= '<table class="form-table">' . "\n";
			$html .= '<tbody>' . "\n";

			$html .= '<input id="seriouslysimple_post_id" type="hidden" value="'. $post_id . '" />';

			foreach ( $field_data as $k => $v ) {
				$data = $v['default'];
				if ( isset( $fields[$k] ) && isset( $fields[$k][0] ) ) {
					$data = $fields[$k][0];
				}

				if( $k == 'enclosure' ) {
					$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input type="button" class="button" id="upload_audio_file_button" value="'. __( 'Upload File' , 'ss-podcasting' ) . '" data-uploader_title="Choose a file" data-uploader_button_text="Insert audio file" /><input name="' . esc_attr( $k ) . '" type="text" id="upload_audio_file" class="regular-text" value="' . esc_attr( $data ) . '" />' . "\n";
					$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
					$html .= '</td><tr/>' . "\n";
				} else {
					if( $v['type'] == 'checkbox' ) {
						$html .= '<tr valign="top"><th scope="row">' . $v['name'] . '</th><td><input name="' . esc_attr( $k ) . '" type="checkbox" id="' . esc_attr( $k ) . '" ' . checked( 'on' , $data , false ) . ' /> <label for="' . esc_attr( $k ) . '"><span class="description">' . $v['description'] . '</span></label>' . "\n";
						$html .= '</td><tr/>' . "\n";
					} else {
						$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />' . "\n";
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
	 * @return void
	 */
	public function meta_box_save( $post_id ) {
		global $post, $messages;

		// Verify
		if ( ( get_post_type() != $this->token ) || ! wp_verify_nonce( $_POST['seriouslysimple_' . $this->token . '_nonce'], plugin_basename( $this->dir ) ) ) {
			return $post_id;
		}

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
		$fields = array_keys( $field_data );

		foreach ( $fields as $f ) {

			if( isset( $_POST[$f] ) ) {
				${$f} = strip_tags( trim( $_POST[$f] ) );
			}

			// Escape the URLs.
			if ( 'url' == $field_data[$f]['type'] ) {
				${$f} = esc_url( ${$f} );
			}

			if( $f == 'enclosure' ) { $enclosure = ${$f}; }

			if ( ${$f} == '' ) {
				delete_post_meta( $post_id , $f , get_post_meta( $post_id , $f , true ) );
			} else {
				update_post_meta( $post_id , $f , ${$f} );
			}
		}

		if( isset( $enclosure ) && strlen( $enclosure ) > 0 ) {

			// Get file Duration
			if ( get_post_meta( $post_id , 'duration' , true ) == '' ) {
				$duration = $this->get_file_duration( $enclosure );
				if( $duration ) {
					update_post_meta( $post_id , 'duration' , $duration );
				}
			}

			// Get file size
			if ( get_post_meta( $post_id , 'filesize' , true ) == '' ) {
				$filesize = $this->get_file_size( $enclosure );
				if( $filesize ) {
					update_post_meta( $post_id , 'filesize' , $filesize['formatted'] );
					update_post_meta( $post_id , 'filesize_raw' , $filesize['raw'] );
				}
			}

		}

	}

	/**
	 * Setup custom fields for episodes
	 * @return array Custom fields
	 */
	public function custom_fields() {
		$fields = array();

		$fields['enclosure'] = array(
		    'name' => __( 'Audio file:' , 'ss-podcasting' ),
		    'description' => __( 'Upload the primary podcast audio file. If the file is hosted on another server simply paste the URL here.' , 'ss-podcasting' ),
		    'type' => 'url',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['duration'] = array(
		    'name' => __( 'Duration:' , 'ss-podcasting' ),
		    'description' => __( 'Duration of audio file as it will be displayed on the site. <b>This will be calculated automatically if possible - fill in a value here to override the automatic calculation.</b> This will ONLY work for files uploaded to this server - if a value is not calculated then you can fill in your own data. Leave the field blank to force a recalculation.' , 'ss-podcasting' ),
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['filesize'] = array(
		    'name' => __( 'File size:' , 'ss-podcasting' ),
		    'description' => __( 'Size of the audio file as it will be displayed on the site. <b>This will be calculated automatically if possible - fill in a value here to override the automatic calculation.</b> If a value is not calculated then you can fill in your own data. Leave the field blank to force a recalculation.' , 'ss-podcasting' ),
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['explicit'] = array(
		    'name' => __( 'Explicit:' , 'ss-podcasting' ),
		    'description' => __( 'Mark this episode as explicit.' , 'ss-podcasting' ),
		    'type' => 'checkbox',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['block'] = array(
		    'name' => __( 'Block from iTunes:' , 'ss-podcasting' ),
		    'description' => __( 'Block this episode from appearing in iTunes.' , 'ss-podcasting' ),
		    'type' => 'checkbox',
		    'default' => '',
		    'section' => 'info'
		);

		return apply_filters( 'ssp_episode_fields', $fields );
	}

	/**
	 * Modify the 'enter title here' text
	 * @param  string $title Default text
	 * @return string        Modified text
	 */
	public function enter_title_here( $title ) {
		if ( get_post_type() == $this->token ) {
			$title = __( 'Enter the episode title here' , 'ss-podcasting' );
		}
		return $title;
	}

	/**
	 * Load admin CSS
	 * @return void
	 */
	public function enqueue_admin_styles() {
		wp_register_style( 'ss_podcasting-admin', esc_url( $this->assets_url . 'css/admin.css' ), array(), '1.0.0' );
		wp_enqueue_style( 'ss_podcasting-admin' );
	}

	/**
	 * Load admin JS
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		wp_register_script( 'ss_podcasting-admin', esc_url( $this->assets_url . 'js/admin.js' ), array( 'jquery' ), '2.0.0' );
		wp_enqueue_script( 'ss_podcasting-admin' );
	}

	/**
	 * Load frontend CSS
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_register_style( 'ss_podcasting', esc_url( $this->assets_url . 'css/style.css' ), array(), '1.7.5' );
		wp_enqueue_style( 'ss_podcasting' );
	}

	/**
	 * Load podcast page template
	 * @return void
	 */
	public function page_templates() {

		// Single podcast template
		if( is_single() && get_post_type() == 'podcast' ) {
			include( $this->template_path . 'single-podcast.php' );
			exit;
		}

		// Podcast archive template
		if( is_post_type_archive( 'podcast' ) || is_tax( 'series' ) ) {
			include( $this->template_path . 'archive-podcast.php' );
			exit;
		}

	}

	/**
	 * Get download link for episode
	 * @param  integer $episode_id ID of episode
	 * @return string              Episode download link
	 */
	public function get_episode_download_link( $episode_id ) {
		$file = $this->get_enclosure( $episode_id );
		$link = add_query_arg( array( 'podcast_episode' => $file ), $this->home_url );
		return apply_filters( 'ssp_episode_download_link', $link, $episode_id, $file );
	}

	/**
	 * Dislpay episode meta data
	 * @param  string $content Episode content
	 * @return string          Updated episode content
	 */
	public function content_meta_data( $content ) {

		$hide_content_meta = get_option( 'ss_podcasting_hide_content_meta' );
		if( ! $hide_content_meta ) {

			if( ( ( get_post_type() == 'podcast' && is_single() ) || is_post_type_archive( 'podcast' ) ) && ! is_feed( 'podcast' ) ) {

				$id = get_the_ID();
				$file = $this->get_enclosure( $id );

				$meta = '';

				if( $file ) {
					$link = $this->get_episode_download_link( $id );
					$duration = get_post_meta( $id , 'duration' , true );
					$size = get_post_meta( $id , 'filesize' , true );
					if( ! $size || strlen( $size ) == 0 || $size == '' ) {
						$size = $this->get_file_size( $file );
						$size = $size['formatted'];
						if( $size ) {
							update_post_meta( $post_id, 'filesize', $size['formatted'] );
							update_post_meta( $post_id, 'filesize_raw', $size['raw'] );
						}
					}

					if( is_single() ) {
						$meta .= '<div class="podcast_player">' . $this->audio_player( $file ) . '</div>';
					}

					$meta .= '<div class="podcast_meta"><aside>';
					if( $link && strlen( $link ) > 0 ) { $meta .= '<a href="' . esc_url( $link ) . '" title="' . get_the_title() . ' ">' . __( 'Download file' , 'ss-podcasting' ) . '</a>'; }
					if( $duration && strlen( $duration ) > 0 ) { if( $link && strlen( $link ) > 0 ) { $meta .= ' | '; } $meta .= __( 'Duration' , 'ss-podcasting' ) . ': ' . $duration; }
					if( $size && strlen( $size ) > 0 ) { if( ( $duration && strlen( $duration ) > 0 ) || ( $file && strlen( $file ) > 0 ) ) { $meta .= ' | '; } $meta .= __( 'Size' , 'ss-podcasting' ) . ': ' . $size; }
					$meta .= '</aside></div>';
				}

				$meta = apply_filters( 'ssp_episode_meta', $meta, $post_id );

				$content = $meta . $content;

			}
		}

		return $content;

	}

	/**
	 * Add podcast to home page query
	 * @param object $query The query object
	 */
	public function add_to_home_query( $query ) {
		if ( ! is_admin() ) {
			if ( $query->is_home() && $query->is_main_query() ) {
				$query->set( 'post_type', array( 'post', 'podcast' ) );
			}
		}
	}

	/**
	 * Get size of media file
	 * @param  string  $file File name & path
	 * @return boolean       File sizeo n success, boolean false on failure
	 */
	public function get_file_size( $file = '' ) {

		if( $file ) {

			$data = wp_remote_head( $file, array( 'timeout' => 10, 'redirection' => 5 ) );

			if( is_array( $data ) && isset( $data['headers']['content-length'] ) ) {

				$raw = $data['headers']['content-length'];
				$formatted = $this->format_bytes( $raw );

				$size = array(
					'raw' => $raw,
					'formatted' => $formatted
				);

				return apply_filters( 'ssp_file_size', $size, $file );

			}

		}

		return false;
	}

	/**
	 * Get duration of audio file
	 * Uses getid3 class for calculation audio duration - http://www.getid3.org/
	 * @param  string $file File name & path
	 * @return mixed        File duration on success, boolean false on failure
	 */
	public function get_file_duration( $file ) {

		if( $file ) {

			if( ! class_exists( 'getid3' ) ) {
				require_once( $this->assets_dir . '/getid3/getid3.php' );
			}

			$getid3 = new getid3();

			// Identify file by root path and not URL (required for getID3 class)
			$site_root = trailingslashit( ABSPATH );
			$file = str_replace( $this->home_url, $site_root, $file );

			$info = $getid3->analyze( $file );

			$duration = false;

			if( isset( $info['playtime_string'] ) && strlen( $info['playtime_string'] ) > 0 ) {
				$duration = $info['playtime_string'];
			} else {
				if( isset( $info['playtime_seconds'] ) && strlen( $info['playtime_seconds'] ) > 0 ) {
					$duration = gmdate( 'H:i:s' , $info['playtime_seconds'] );
				}
			}

			return apply_filters( 'ssp_file_duration', $duration, $file );

		}

		return false;
	}

	/**
	 * Format filesize for display
	 * @param  integer $size      Raw file size
	 * @param  integer $precision Level of precision for formatting
	 * @return mixed              Formatted file size on success, false on failure
	 */
	protected function format_bytes( $size , $precision = 2 ) {

		if( $size ) {

		    $base = log ( $size ) / log( 1024 );
		    $suffixes = array( '' , 'k' , 'M' , 'G' , 'T' );
		    $bytes = round( pow( 1024 , $base - floor( $base ) ) , $precision ) . $suffixes[ floor( $base ) ];

		    return apply_filters( 'ssp_file_size_formatted', $bytes, $size );
		}

		return false;
	}

	/**
	 * Format duration of audio track for display
	 * @param  integer $duration Raw duration in seconds
	 * @return mixed             Formatted duration on success, 0 on failure
	 */
	public function format_duration( $duration = '' ) {

		$length = false;

		if( $duration ) {
			sscanf( $duration , "%d:%d:%d" , $hours , $minutes , $seconds );
			$length = isset( $seconds ) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;

			if( ! $length ) {
				$length = (int) $duration;
			}

			return apply_filters( 'ssp_file_duration_formatted', $length, $duration );
		}

		return 0;
	}

	/**
	 * Get MIME type of attachment file
	 * @param  string $attachment Attachment URL
	 * @return mixed              MIME type on success, false on failure
	 */
	public function get_attachment_mimetype( $attachment = '' ) {

		if( $attachment ) {
		    global $wpdb;

		    $prefix = $wpdb->prefix;

		    $attachment = $wpdb->get_col($wpdb->prepare( 'SELECT ID FROM ' . $prefix . 'posts' . ' WHERE guid="' . $attachment . '";' ) );

		    if( $attachment[0] ) {
			    $id = $attachment[0];

			    $mime_type = get_post_mime_type( $id );

			    return apply_filters( 'ssp_attachment_mimetype', $mime_type, $id );
			}

		}

		return false;

	}

	/**
	 * Load audio player for given file
	 * @param  string $src Source of audio file
	 * @return mixed       Audio player HTML on success, false on failure
	 */
	public function audio_player( $src = '' ) {

		if( $src ) {
			global $wp_version;
			if( $wp_version && version_compare( $wp_version, '3.6', '>=' ) ) {
				return wp_audio_shortcode( array( 'src' => $src ) );
			}
		}

		return false;
	}

	/**
	 * Get episode image
	 * @param  integer $id   ID of episode
	 * @param  string  $size Image size
	 * @return string        Image HTML markup
	 */
	protected function get_image( $id = 0, $size = 'podcast-thumbnail' ) {
		$response = '';

		if ( has_post_thumbnail( $id ) ) {
			// If not a string or an array, and not an integer, default to 200x9999.
			if ( is_int( $size ) || ( 0 < intval( $size ) ) ) {
				$size = array( intval( $size ), intval( $size ) );
			} elseif ( ! is_string( $size ) && ! is_array( $size ) ) {
				$size = array( 200, 9999 );
			}
			$response = get_the_post_thumbnail( intval( $id ), $size );
		}

		return apply_filters( 'ssp_episode_image', $response, $id );
	}

	/**
	 * Get podcast
	 * @param  string/array $args Arguments to be passed to the query.
	 * @return array/boolean      Array if true, boolean if false.
	 */
	public function get_podcast( $args = '' ) {
		$defaults = array(
			'title' => '',
			'content' => 'series',
			'series' => ''
		);

		$args = wp_parse_args( $args, $defaults );
		$args = apply_filters( 'ssp_get_podcast_args', $args );

		if( $args['content'] == 'episodes' ) {

			// The Query Arguments.
			$query_args = array();
			$query_args['post_type'] = 'podcast';
			$query_args['posts_per_page'] = -1;
			$query_args['suppress_filters'] = 0;

			if ( $args['series'] != '' ) {
				$query_args[ 'series' ] = $args[ 'series' ];
			}

			// The Query.
			$query = get_posts( $query_args );

			// The Display.
			if ( ! is_wp_error( $query ) && is_array( $query ) && count( $query ) > 0 ) {
				foreach ( $query as $k => $v ) {
					// Get the URL.
					$query[$k]->url = get_permalink( $v->ID );
				}
			} else {
				$query = false;
			}

		} else {

			$terms = get_terms( 'series' );

			if( count( $terms ) > 0) {
				foreach ( $terms as $term ) {
					$query[ $term->term_id ] = new stdClass();
					$query[ $term->term_id ]->title = $term->name;
		    		$query[ $term->term_id ]->url = get_term_link( $term );
		    		$posts = get_posts( array(
		    			'post_type' => 'podcast',
		    			'posts_per_page' => -1,
		    			'series' => $term->slug
		    			)
		    		);
		    		$count = count( $posts );
		    		$query[ $term->term_id ]->count = $count;
			    }
			}

		}

		$query['content'] = $args['content'];

		return $query;
	}

	/**
	 * Get episode enclosure
	 * @param  integer $episode_id ID of episode
	 * @return string              URL of enclosure
	 */
	public function get_enclosure( $episode_id = 0 ) {

		if( $episode_id ) {
			return apply_filters( 'ssp_episode_enclosure', get_post_meta( $episode_id, 'enclosure', true ), $episode_id );
		}

		return false;
	}

	/**
	 * Get episode from audio file
	 * @param  string $file File name & path
	 * @return object       Episode post object
	 */
	public function get_episode_from_file( $file = '' ) {

		$episode = false;

		if( $file != '' ) {

			$args = array(
				'post_type' => 'podcast',
				'post_status' => 'publish',
				'posts_per_page' => 1,
				'meta_key' => 'enclosure',
				'meta_value' => $file
			);

			$qry = new WP_Query( $args );

			if ( $qry->have_posts() ) {
				while ( $qry->have_posts() ) { $qry->the_post();
					$episode = get_queried_object();
					break;
				}
			}
		}

		return apply_filters( 'ssp_episode_from_file', $episode, $file );

	}

	/**
	 * Download file from $_GET['podcast_episode']
	 * @return void
	 */
	public function download_file() {

		$file = esc_attr( $_GET['podcast_episode'] );

		if( $file ) {

			// Get episode object
			$episode = $this->get_episode_from_file( $file );

			// Allow other actions
		    do_action( 'ss_podcasting_file_download', $file, $episode );

		    // Set necessary headers
			header( "Pragma: no-cache" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Robots: none" );
			header( "Content-Type: application/force-download" );
			header( "Content-Description: File Transfer" );
			header( "Content-Disposition: attachment; filename=\"" . basename( $file ) . "\";" );
			header( "Content-Transfer-Encoding: binary" );

			// Set size of file
	        if ( $size = @filesize( $file ) )
	        	header( "Content-Length: " . $size );

	        // Use readfile_chunked() if allowed on the server or simply access file directly
			@readfile_chunked( "$file" ) or header( 'Location: ' . $file );

		}

	}

	/**
	 * Register new image size
	 * @return void
	 */
	public function register_image_sizes() {
		if ( function_exists( 'add_image_size' ) ) {
			add_image_size( 'podcast-thumbnail', 200, 9999 ); // 200 pixels wide (and unlimited height)
		}
	}

	/**
	 * Ensure thumbnail support on site
	 * @return void
	 */
	public function ensure_post_thumbnails_support() {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) { add_theme_support( 'post-thumbnails' ); }
	}

	/**
	 * Load plugin text domain
	 * @return void
	 */
	public function load_localisation() {
		load_plugin_textdomain( 'ss-podcasting', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Load localisation
	 * @return void
	 */
	public function load_plugin_textdomain() {
	    $domain = 'ss-podcasting';
	    // The "plugin_locale" filter is also used in load_plugin_textdomain()
	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Regsiter new widget area for podcast template
	 * @return void
	 */
    public function register_widget_area() {

        register_sidebar( array(
            'name' => __( 'Podcast sidebar' , 'ss-podcasting' ),
            'id' => 'podcast_sidebar',
            'description' => __( 'Sidebar used on the podcast pages if you are using the plugin\'s built-in templates.' , 'ss-podcasting' ),
            'class' => 'podcast_sidebar',
            'before_widget' => '<div class="widget">',
            'after_widget' => '</div>',
            'before_title' => '<h3>',
            'after_title' => '</h3>',
        	)
        );

    }

    /**
     * Register podcast feed
     */
    public function add_feed() {
		add_feed( $this->token, array( $this, 'feed_template' ) );
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

    	$user_template_file = apply_filters( 'ssp_template_file', trailingslashit( get_template_directory() ) . $file_name );

		// Any functions hooked in here must NOT output any data
		do_action( 'ssp_before_feed' );

    	// Load feed template from theme if it exists, otherwise use plugin template
    	if( file_exists( $user_template_file ) ) {
    		require( $user_template_file );
    	} else {
    		require( $this->template_path . $file_name );
    	}

    	// Any functions hooked in here must NOT output any data
    	do_action( 'ssp_after_feed' );
	}

	/**
	 * Display feed meta tag in site HTML
	 * @return void
	 */
	public function rss_meta_tag() {

		$feed_url = $this->site_url . 'feed/' . $this->token;
		$custom_feed_url = get_option('ss_podcasting_feed_url');
		if( $custom_feed_url && strlen( $custom_feed_url ) > 0 && $custom_feed_url != '' ) {
			$feed_url = $custom_feed_url;
		}

		$html = '<link rel="alternate" type="application/rss+xml" title="Podcast RSS feed" href="' . esc_url( $feed_url ) . '" />';

		echo apply_filters( 'ssp_rss_meta_tag', $html );
	}

	/**
	 * Redirect v1.x feed
	 * @return void
	 */
	public function redirect_old_feed() {
		if( isset( $_GET['feed'] ) && in_array( $_GET['feed'], array( 'podcast', 'itunes' ) ) ) {
			$this->feed_template();
			exit;
		}
	}

}