<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class SeriouslySimplePodcasting {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $template_path;
	private $token;
	private $home_url;

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

		// Regsiter 'podcast' post type
		add_action('init', array( $this , 'register_post_type' ) );

		// Use built-in templates if selected
		$template_option = get_option( 'ss_podcasting_use_templates' );
		if( ( $template_option && $template_option == 'on' ) ) {
			add_action( 'template_redirect' , array( $this , 'page_templates' ) , 10 );
			add_action( 'widgets_init', array( $this , 'register_widget_area' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		// Add meta data to start of podcast content
		add_filter( 'the_content', array( $this , 'content_meta_data' ) );

		// Add RSS meta tag to site header
		add_action( 'wp_head' , array( $this , 'rss_meta_tag' ) );

		// Add podcast episode to main query loop if setting is activated
		$include_in_main_query = get_option('ss_podcasting_include_in_main_query');
		if( $include_in_main_query && $include_in_main_query == 'on' ) {
			add_filter( 'pre_get_posts' , array( $this , 'add_to_home_query' ) );
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
			add_filter( 'manage_edit-series_columns' , array( $this , 'edit_series_columns' ) );
            add_filter( 'manage_series_custom_column' , array( $this , 'add_series_columns' ) , 1 , 3 );

		}

		// Add podcast image size
		add_action( 'after_setup_theme', array( $this , 'ensure_post_thumbnails_support' ) );
		add_action( 'after_setup_theme', array( $this , 'register_image_sizes' ) );

		// Handle RSS template
		if( is_podcast_feed() ) {

			// Prevent feed from returning a 404 error when no posts are present on site
			add_action( 'template_redirect' , array( $this , 'prevent_feed_404' ) , 10 );

			switch( $_GET['feed'] ) {
				case 'podcast': add_action( 'template_redirect' , array( $this , 'feed_template' ) , 1 ); break;
				case 'itunes': add_action( 'template_redirect' , array( $this , 'feed_template' ) , 1 ); break; // Backwards compatiblity
			}
		}

		if( is_file_download() ) {
			add_action( 'wp', array( $this , 'download_file' ), 1 );
		}

		// Fluch rewrite rules on plugin activation
		register_activation_hook( $file, array( $this, 'rewrite_flush' ) );

	}

	public function rewrite_flush() {
		$this->register_post_type();
		flush_rewrite_rules();
	}

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


		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => $slug , 'feeds' => true ),
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'comments', 'author', 'custom-fields' ),
			'menu_position' => 5,
			'menu_icon' => ''
		);

		register_post_type( $this->token, $args );

        register_taxonomy( 'series', array( $this->token ), array( 'hierarchical' => true , 'label' => 'Series' , 'singular_label' => 'Series' , 'rewrite' => true) );
        register_taxonomy( 'keywords', array( $this->token ), array( 'hierarchical' => false , 'label' => 'Keywords' , 'singular_label' => 'Keyword' , 'rewrite' => true) );
	}

	public function register_custom_columns ( $column_name, $id ) {
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

	public function register_custom_column_headings ( $defaults ) {
		$new_columns = array( 'series' => __( 'Series' , 'ss-podcasting' ) , 'image' => __( 'Image' , 'ss-podcasting' ) );

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

	public function edit_series_columns( $columns ) {

        unset( $columns['description'] );
        unset( $columns['posts'] );

        $columns['series_feed_url'] = __( 'Series feed URL' , 'ss-podcasting' );
        $columns['posts'] = __( 'Episodes' , 'ss-podcasting' );

        return $columns;
    }

    public function add_series_columns( $column_data , $column_name , $term_id ) {

        switch ( $column_name ) {
            case 'series_feed_url':
            	$series = get_term( $term_id, 'series' );
            	$series_slug = $series->slug;
            	$feed_url = $this->home_url . '?feed=podcast&podcast_series=' . $series_slug;
                $column_data = '<a href="' . $feed_url . '" target="_blank">' . $feed_url . '</a>';
            break;
        }

        return $column_data;
    }

	public function updated_messages ( $messages ) {
	  global $post, $post_ID;

	  $messages[$this->token] = array(
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

	public function meta_box_setup () {
		add_meta_box( 'episode-data', __( 'Episode Details' , 'ss-podcasting' ), array( $this, 'meta_box_content' ), $this->token, 'normal', 'high' );

		do_action( 'ss_podcasting_meta_boxes' );
	}

	public function meta_box_content() {
		global $post_id;
		$fields = get_post_custom( $post_id );
		$field_data = $this->get_custom_fields_settings();

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

		$field_data = $this->get_custom_fields_settings();
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

	public function enter_title_here( $title ) {
		if ( get_post_type() == $this->token ) {
			$title = __( 'Enter the episode title here' , 'ss-podcasting' );
		}
		return $title;
	}

	public function enqueue_admin_styles() {

		// Admin CSS
		wp_register_style( 'ss_podcasting-admin', esc_url( $this->assets_url . 'css/admin.css' ), array(), '1.0.0' );
		wp_enqueue_style( 'ss_podcasting-admin' );

	}

	public function enqueue_admin_scripts() {

		// Admin JS
		wp_register_script( 'ss_podcasting-admin', esc_url( $this->assets_url . 'js/admin.js' ), array( 'jquery' ), '1.7.0' );
		wp_enqueue_script( 'ss_podcasting-admin' );

	}

	public function get_custom_fields_settings() {
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

		return apply_filters( 'ss_podcasting_episode_fields', $fields );
	}

	public function enqueue_scripts() {

		wp_register_style( 'ss_podcasting', esc_url( $this->assets_url . 'css/style.css' ), array(), '1.7.5' );
		wp_enqueue_style( 'ss_podcasting' );

	}

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

	public function rss_meta_tag() {

		$custom_feed_url = get_option('ss_podcasting_feed_url');
		$feed_url = $this->home_url . '?feed=podcast';
		if( $custom_feed_url && strlen( $custom_feed_url ) > 0 && $custom_feed_url != '' ) {
			$feed_url = $custom_feed_url;
		}

		$html = '<link rel="alternate" type="application/rss+xml" title="Podcast RSS feed" href="' . esc_url( $feed_url ) . '" />';

		echo $html;
	}

	public function get_episode_download_link( $episode ) {

		$file = $this->get_enclosure( $episode );

		$link = add_query_arg( array( 'podcast_episode' => $file ), $this->home_url );

		return $link;
	}

	public function content_meta_data( $content ) {

		$hide_content_meta = get_option( 'ss_podcasting_hide_content_meta' );
		if( ! $hide_content_meta ) {

			if( ( ( get_post_type() == 'podcast' && is_single() ) || is_post_type_archive( 'podcast' ) ) && ! is_podcast_feed() ) {

				$id = get_the_ID();
				$file = $this->get_enclosure( $id );

				if( $file ) {
					$link = $this->get_episode_download_link( $id );
					$duration = get_post_meta( $id , 'duration' , true );
					$size = get_post_meta( $id , 'filesize' , true );
					if( ! $size || strlen( $size ) == 0 || $size == '' ) {
						$size = $this->get_file_size( $file );
						$size = $size['formatted'];
					}

					$meta = '';

					if( is_single() ) {
						$meta .= '<div class="podcast_player">' . $this->audio_player( $file ) . '</div>';
					}

					$meta .= '<div class="podcast_meta"><aside>';
					if( $link && strlen( $link ) > 0 ) { $meta .= '<a href="' . esc_url( $link ) . '" title="' . get_the_title() . ' ">' . __( 'Download file' , 'ss-podcasting' ) . '</a>'; }
					if( $duration && strlen( $duration ) > 0 ) { if( $link && strlen( $link ) > 0 ) { $meta .= ' | '; } $meta .= __( 'Duration' , 'ss-podcasting' ) . ': ' . $duration; }
					if( $size && strlen( $size ) > 0 ) { if( ( $duration && strlen( $duration ) > 0 ) || ( $file && strlen( $file ) > 0 ) ) { $meta .= ' | '; } $meta .= __( 'Size' , 'ss-podcasting' ) . ': ' . $size; }
					$meta .= '</aside></div>';

					$content = $meta . $content;
				}

			}
		}

		return $content;

	}

	public function add_to_home_query( $query ) {

		if ( ! is_admin() ) {

			if ( $query->is_home() && $query->is_main_query() ) {
				$query->set( 'post_type', array( 'post' , 'podcast' ) );
			}


		}
	}

	public function get_file_size( $file = false ) {

		if( $file ) {

			$data = wp_remote_head( $file );

			if( isset( $data['headers']['content-length'] ) ) {

				$raw = $data['headers']['content-length'];
				$formatted = $this->format_bytes( $raw );

				$size = array(
					'raw' => $raw,
					'formatted' => $formatted
				);

				return $size;

			}

		}

		return false;
	}

	public function get_file_duration( $file ) {

		/*
		 * Uses getid3 class for calculation audio duration
		 *
		 * http://www.getid3.org/
		*/

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

			return $duration;

		}

		return false;
	}

	protected function format_bytes( $size , $precision = 2 ) {

		if( $size ) {

		    $base = log ( $size ) / log( 1024 );
		    $suffixes = array( '' , 'k' , 'M' , 'G' , 'T' );
		    $bytes = round( pow( 1024 , $base - floor( $base ) ) , $precision ) . $suffixes[ floor( $base ) ];

		    return $bytes;
		}

		return false;
	}

	public function get_attachment_mimetype( $attachment = false ) {

		if( $attachment ) {
		    global $wpdb;

		    $prefix = $wpdb->prefix;

		    $attachment = $wpdb->get_col($wpdb->prepare( 'SELECT ID FROM ' . $prefix . 'posts' . ' WHERE guid="' . $attachment . '";' ) );

		    if( $attachment[0] ) {
			    $id = $attachment[0];

			    $mime_type = get_post_mime_type( $id );

			    return $mime_type;
			}

		}

		return false;

	}

	public function format_duration( $duration = false ) {

		$length = false;

		if( $duration ) {
			sscanf( $duration , "%d:%d:%d" , $hours , $minutes , $seconds );
			$length = isset( $seconds ) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;

			if( ! $length ) {
				$length = (int) $duration;
			}

			return $length;
		}

		return 0;
	}

	public function audio_player( $src = false ) {
		global $wp_version;

		if( $src ) {

			if( $wp_version && version_compare( $wp_version, '3.6', '>=' ) ) {
				return wp_audio_shortcode( array( 'src' => $src ) );
			} else {

				// DEPRECATED: All the following code will be removed from future version of the plugin
				// It is included here for backwards compatibility with versions of WordPress prior to 3.6

				$dir = $this->assets_url . 'mediaelement/';

				// Only enqueue script when needed
				wp_register_script( 'mediaelementjs-scripts' , esc_url( $dir . 'mediaelement-and-player.min.js' ) , array('jquery') , '2.7.0' , false );
				wp_enqueue_script( 'mediaelementjs-scripts' );

				wp_register_style( 'mediaelementjs-styles' , esc_url( $dir . 'mediaelementplayer.css' ) );
				wp_enqueue_style( 'mediaelementjs-styles' );

				$ext = pathinfo(basename($src), PATHINFO_EXTENSION);

				if( $ext && strlen( $ext ) > 0 ) {

					$sources = array();
					$options = array();
					$flash_src = '';

					$width = '400';
					$height = '30';

					$attributes = array(
						'src' => htmlspecialchars( $src ),
						'mp3' => '',
						'ogg' => '',
						'poster' => '',
						'width' => $width,
						'height' => $height,
						'type' => 'audio',
						'preload' => 'none',
						'skin' => get_option( 'mep_video_skin' ),
						'autoplay' => 'false',
						'loop' => 'false',

						// old ones
						'duration' => 'true',
						'progress' => 'true',
						'fullscreen' => 'false',
						'volume' => 'true',

						// captions
						'captions' => '',
						'captionslang' => 'en'
					);

					$sources[] = '<source src="' . htmlspecialchars( $src ) . '" type="audio/' . $ext . '" />';
					$flash_src = htmlspecialchars( $src );

					// MEJS options
					if ($attributes['loop']) {
						$options[]  = 'loop: ' . $attributes['loop'];
					}

					// CONTROLS array
					$controls_option[] = '"playpause"';
					if ($attributes['progress'] == 'true') {
						$controls_option[] = '"current"';
						$controls_option[] = '"progress"';
					}
					if ($attributes['duration'] == 'true') {
						$controls_option[] = '"duration"';
					}
					if ($attributes['volume'] == 'true') {
						$controls_option[] = '"volume"';
					}
					$controls_option[] = '"tracks"';
					$options[] = '"features":[' . implode(',', $controls_option) . ']';
					$options[] = '"audioWidth":'.$width;
					$options[] = '"audioHeight":'.$height;

					$attributes_string = !empty($attributes) ? implode(' ', $attributes) : '';
					$sources_string = !empty($sources) ? implode("\n\t\t", $sources) : '';
					$options_string = !empty($options) ? '{' . implode(',', $options) . '}' : '';

					$mediahtml = '<audio id="wp_mep_0" controls="controls" ' . $attributes_string . ' class="mejs-player" data-mejsoptions="' . $options_string . '">
						' . $sources_string . '
						<object width="' . $width . '" height="' . $height . '" type="application/x-shockwave-flash" data="' . $dir . 'flashmediaelement.swf">
							<param name="movie" value="' . $dir . 'flashmediaelement.swf" />
							<param name="flashvars" value="controls=true&amp;file=' . $flash_src . '" />
						</object>
					</audio>';

	  				return $mediahtml;

				}

			}
		}

		return false;

	}

	protected function get_image( $id, $size = 'podcast-thumbnail' ) {
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

		return $response;
	}

	/**
	 * Get podcast
	 * @param  string/array $args Arguments to be passed to the query.
	 * @since  1.0.0
	 * @return array/boolean      Array if true, boolean if false.
	 */
	public function get_podcast( $args = '' ) {
		$defaults = array(
			'title' => '',
			'content' => 'series',
			'series' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		// Allow themes/plugins to filter here.
		$args = apply_filters( 'podcast_get_args', $args );

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

		$query[ 'content' ] = $args[ 'content' ];

		return $query;
	}

	public function get_enclosure( $episode ) {

		if( $episode ) {
			return get_post_meta( $episode, 'enclosure', true );
		}

		return false;

	}

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

		return $episode;

	}

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

	public function register_image_sizes() {
		if ( function_exists( 'add_image_size' ) ) {
			add_image_size( 'podcast-thumbnail', 200, 9999 ); // 200 pixels wide (and unlimited height)
		}
	}

	public function ensure_post_thumbnails_support() {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) { add_theme_support( 'post-thumbnails' ); }
	}

	public function load_localisation() {
		load_plugin_textdomain( 'ss-podcasting', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	public function load_plugin_textdomain() {
	    $domain = 'ss-podcasting';
	    // The "plugin_locale" filter is also used in load_plugin_textdomain()
	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

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

    public function prevent_feed_404() {
    	global $wp_query;

    	if( is_podcast_feed() ) {
    		status_header( 200 );
			$wp_query->is_404 = false;
    	}

    }

    public function feed_template() {

    	$file_name = 'feed-podcast.php';

    	$theme_template_file = trailingslashit( get_template_directory() ) . $file_name;

    	// Load feed template from theme if it exists, otherwise use plugin template
    	if( file_exists( $theme_template_file ) ) {
    		require( $theme_template_file );
    	} else {
    		require( $this->template_path . $file_name );
    	}

	    exit;
	}

}