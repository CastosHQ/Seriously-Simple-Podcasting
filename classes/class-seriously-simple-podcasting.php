<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class SeriouslySimplePodcasting {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $template_path;
	private $token;

	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->template_path = trailingslashit( $this->dir ) . 'templates/';
		$this->token = 'podcast';

		$this->load_plugin_textdomain();
		add_action( 'init', array( &$this, 'load_localisation' ), 0 );

		add_action('init', array( &$this , 'register_post_type' ) );

		// Use built-in templates if selected
		$template_option = get_option( 'ss_podcasting_use_templates' );
		if( ( $template_option && $template_option == 'on' ) ) {
			add_action( 'template_redirect' , array( &$this , 'page_templates' ) , 10 );
			add_action( 'widgets_init', array( &$this , 'register_widget_area' ) );
			add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		}

		add_filter( 'the_content', array( &$this , 'meta_data' ) );

		if ( is_admin() ) {
			global $pagenow;

			add_action( 'admin_menu', array( &$this, 'meta_box_setup' ), 20 );
			add_action( 'save_post', array( &$this, 'meta_box_save' ) );
			add_filter( 'enter_title_here', array( &$this, 'enter_title_here' ) );
			add_filter( 'post_updated_messages', array( &$this, 'updated_messages' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_admin_scripts' ), 10 );

			if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && esc_attr( $_GET['post_type'] ) == $this->token ) {
				add_filter( 'manage_edit-' . $this->token . '_columns', array( &$this, 'register_custom_column_headings' ), 10, 1 );
				add_action( 'manage_posts_custom_column', array( &$this, 'register_custom_columns' ), 10, 2 );
			}

		}

		add_action( 'after_setup_theme', array( &$this , 'ensure_post_thumbnails_support' ) );
		add_action( 'after_setup_theme', array( &$this , 'register_image_sizes' ) );

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
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'menu_position' => 5,
			'menu_icon' => ''
		);

		register_post_type( $this->token, $args );
	        
        register_taxonomy( 'series', array( $this->token ), array( 'hierarchical' => true, 'label' => 'Series', 'singular_label' => 'Series', 'rewrite' => true));
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

	public function updated_messages ( $messages ) {
	  global $post, $post_ID;

	  $messages[$this->token] = array(
	    0 => '', // Unused. Messages start at index 1.
	    1 => sprintf( __( 'Podcast updated. %sView podcast%s.' , 'ss-podcasting' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    2 => __( 'Custom field updated.' , 'ss-podcasting' ),
	    3 => __( 'Custom field deleted.' , 'ss-podcasting' ),
	    4 => __( 'Podcast updated.' , 'ss-podcasting' ),
	    /* translators: %s: date and time of the revision */
	    5 => isset($_GET['revision']) ? sprintf( __( 'Podcast restored to revision from %s.' , 'ss-podcasting' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
	    6 => sprintf( __( 'Podcast published. %sView podcast%s.' , 'ss-podcasting' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    7 => __( 'Podcast saved.' , 'ss-podcasting' ),
	    8 => sprintf( __( 'Podcast submitted. %sPreview podcast%s.' , 'ss-podcasting' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
	    9 => sprintf( __( 'Podcast scheduled for: %1$s. %2$sPreview podcast%3$s.' , 'ss-podcasting' ), '<strong>' . date_i18n( __( 'M j, Y @ G:i' , 'ss-podcasting' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink($post_ID) ) . '">', '</a>' ),
	    10 => sprintf( __( 'Podcast draft updated. %sPreview podcast%s.' , 'ss-podcasting' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
	  );

	  return $messages;
	}

	public function meta_box_setup () {		
		add_meta_box( 'episode-data', __( 'Episode Details' , 'ss-podcasting' ), array( &$this, 'meta_box_content' ), $this->token, 'normal', 'high' );
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
					$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input id="upload_file_button" type="button" class="button" value="'. __( 'Upload File' , 'ss-podcasting' ) . '" /></td></tr>' . "\n";
					$html .= '<tr valign="top"><th scope="row">&nbsp;</th><td><input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />' . "\n";
					$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
					$html .= '</td><tr/>' . "\n";
				} else {
					$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />' . "\n";
					$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
					$html .= '</td><tr/>' . "\n";
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
		
			${$f} = strip_tags(trim($_POST[$f]));

			// Escape the URLs.
			if ( 'url' == $field_data[$f]['type'] ) {
				${$f} = esc_url( ${$f} );
			}
			
			if ( get_post_meta( $post_id, $f ) == '' ) { 
				add_post_meta( $post_id, $f, ${$f}, true ); 
			} elseif( ${$f} != get_post_meta( $post_id, $f, true ) ) { 
				update_post_meta( $post_id, $f, ${$f} );
			} elseif ( ${$f} == '' ) { 
				delete_post_meta( $post_id, $f, get_post_meta( $post_id, $f, true ) );
			}	
		}
	}

	public function enter_title_here( $title ) {
		if ( get_post_type() == $this->token ) {
			$title = __( 'Enter the episode title here' , 'ss-podcasting' );
		}
		return $title;
	}

	public function enqueue_admin_scripts () {

		// Admin CSS
		wp_register_style( 'ss_podcasting-admin', esc_url( $this->assets_url . 'css/admin.css' ), array(), '1.0.0' );
		wp_enqueue_style( 'ss_podcasting-admin' );

		// Admin JS
		wp_register_script( 'ss_podcasting-admin', esc_url( $this->assets_url . 'js/admin.js' ), array( 'jquery' , 'media-upload' , 'thickbox' ), '1.0.0' );

		// JS & CSS for media uploader
		wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'thickbox' );
        wp_enqueue_style( 'thickbox' );
        wp_enqueue_script( 'media-upload' );
        wp_enqueue_script( 'ss_podcasting-admin' );

	}

	public function get_custom_fields_settings () {
		$fields = array();

		$fields['enclosure'] = array(
		    'name' => __( 'Audio file' , 'ss-podcasting' ),
		    'description' => __( 'Upload the podcast audio file (usually in MP3 format). If the file is hosted on another server simply paste the URL in this box.' , 'ss-podcasting' ),
		    'type' => 'url',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['duration'] = array(
		    'name' => __( 'Duration' , 'ss-podcasting' ),
		    'description' => __( 'Duration of audio file as it will be displayed on the site (usually in \'MM:SS\' format).' , 'ss-podcasting' ),
		    'type' => 'text',
		    'default' => '',
		    'section' => 'info'
		);

		return $fields;
	}

	public function enqueue_scripts() {

		wp_register_style( 'ss_podcasting', esc_url( $this->assets_url . 'css/style.css' ), array(), '1.0.0' );
		wp_enqueue_style( 'ss_podcasting' );

	}

	public function page_templates() {

		// Single podcast template
		if( is_single() && get_post_type() == 'podcast' ) {
			include( $this->template_path . 'single-podcast.php' );
			exit;
		}

		// Podcast archive template
		if( is_post_type_archive( 'podcast' ) ) {
			include( $this->template_path . 'archive-podcast.php' );
			exit;
		}

	}

	public function meta_data( $content ) {

		if( get_post_type() == 'podcast' && ( is_feed() || is_single() ) ) {
			
			$id = get_the_ID();

			$file = get_post_meta( $id , 'enclosure' , true );
			$duration = get_post_meta( $id , 'duration' , true );
			$size = $this->get_file_size( $file );

			$meta = '<div class="podcast_meta"><aside>';
			if( $file && strlen( $file ) > 0 ) { $meta .= '<a href="' . esc_url( $file ) . '" title="' . get_the_title() . ' ">Download file</a>'; }
			if( $duration && strlen( $duration ) > 0 ) { if( $file && strlen( $file ) > 0 ) { $meta .= ' | '; } $meta .= 'Duration: ' . $duration; }
			if( $size && strlen( $size ) > 0 ) { if( ( $duration && strlen( $duration ) > 0 ) || ( $file && strlen( $file ) > 0 ) ) { $meta .= ' | '; } $meta .= 'Size: ' . $size; }
			$meta .= '</aside></div>';

			$content = $meta . $content;

		}

		return $content;

	}

	public function get_file_size( $url = false ) {

		if( $url ) {

			$data = wp_remote_head( $url );

			if( isset( $data['headers']['content-length'] ) ) {

				$size = $this->format_bytes( $data['headers']['content-length'] );

				return $size;

			}

		}

		return false;
	}

	protected function format_bytes($size, $precision = 2) {
	    $base = log($size) / log(1024);
	    $suffixes = array('', 'k', 'M', 'G', 'T');   
	    return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
	}

	public function audio_player( $src = false ) {

		/*
		 * Uses MediaElement.js for audio player
		 * This code is pulled from the MediaElement.js WordPress plugin
		 * 
		 * http://mediaelementjs.com/
		*/

		if( $src ) {

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
					'skin' => get_option('mep_video_skin'),
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

		return false;

	}

	protected function get_image ( $id, $size = 'podcast-thumbnail' ) {
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

	public function register_image_sizes () {
		if ( function_exists( 'add_image_size' ) ) { 
			add_image_size( 'podcast-thumbnail', 200, 9999 ); // 200 pixels wide (and unlimited height)
		}
	}

	public function ensure_post_thumbnails_support () {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) { add_theme_support( 'post-thumbnails' ); }
	}

	public function load_localisation () {
		load_plugin_textdomain( 'ss-podcasting', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	public function load_plugin_textdomain () {
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

}