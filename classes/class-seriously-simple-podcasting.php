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

		add_action('init', array( &$this , 'register_post_type' ) );

		// Use custom templates
		add_action( 'template_redirect' , array( &$this , 'page_templates' ) , 1 );
		add_action( 'widgets_init', array( &$this , 'register_widget_area' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

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
			'name' => _x( 'Podcasts', 'post type general name' ),
			'singular_name' => _x( 'Podcast', 'post type singular name' ),
			'add_new' => _x( 'Add New', $this->token ),
			'add_new_item' => sprintf( __( 'Add New %s' ), __( 'Episode' ) ),
			'edit_item' => sprintf( __( 'Edit %s' ), __( 'Episode' ) ),
			'new_item' => sprintf( __( 'New %s' ), __( 'Episode' ) ),
			'all_items' => sprintf( __( 'All %s' ), __( 'Episodes' ) ),
			'view_item' => sprintf( __( 'View %s' ), __( 'Episode' ) ),
			'search_items' => sprintf( __( 'Search %a' ), __( 'Episodes' ) ),
			'not_found' =>  sprintf( __( 'No %s Found' ), __( 'Episodes' ) ),
			'not_found_in_trash' => sprintf( __( 'No %s Found In Trash' ), __( 'Episodes' ) ),
			'parent_item_colon' => '',
			'menu_name' => __( 'Podcasts' )

		);
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'podcast' ),
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
		$new_columns = array( 'series' => __( 'Series' ) , 'image' => __( 'Image' ) );
		
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
	    1 => sprintf( __( 'Podcast updated. %sView podcast%s.' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    2 => __( 'Custom field updated.' ),
	    3 => __( 'Custom field deleted.' ),
	    4 => __( 'Podcast updated.' ),
	    /* translators: %s: date and time of the revision */
	    5 => isset($_GET['revision']) ? sprintf( __( 'Podcast restored to revision from %s.' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
	    6 => sprintf( __( 'Podcast published. %sView podcast%s.' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    7 => __('Podcast saved.'),
	    8 => sprintf( __( 'Podcast submitted. %sPreview podcast%s.' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
	    9 => sprintf( __( 'Podcast scheduled for: %1$s. %2$sPreview podcast%3$s.' ), '<strong>' . date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink($post_ID) ) . '">', '</a>' ),
	    10 => sprintf( __( 'Podcast draft updated. %sPreview podcast%s.' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
	  );

	  return $messages;
	}

	public function meta_box_setup () {		
		add_meta_box( 'episode-data', __( 'Episode Details' ), array( &$this, 'meta_box_content' ), $this->token, 'normal', 'high' );
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
					$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input id="upload_file_button" type="button" class="button" value="'. __( 'Upload File' ) . '" /></td></tr>' . "\n";
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
			$title = __( 'Enter the episode title here' );
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
		    'name' => __( 'Audio file' ),
		    'description' => __( 'Upload the podcast audio file (usually in MP3 format). If the file is hosted on another server simply paste the URL in this box.' ),
		    'type' => 'url',
		    'default' => '',
		    'section' => 'info'
		);

		$fields['duration'] = array(
		    'name' => __( 'Duration' ),
		    'description' => __( 'Duration of audio file as it will be displayed on the site (usually in \'MM:SS\' format).' ),
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

	protected function get_file_size( $url ){
	     $ch = curl_init( $url );

	     curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	     curl_setopt($ch, CURLOPT_HEADER, TRUE);
	     curl_setopt($ch, CURLOPT_NOBODY, TRUE);

	     $data = curl_exec($ch);
	     $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
	     $size = $this->format_bytes($size);

	     curl_close($ch);

	     return $size;
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
			// If not a string or an array, and not an integer, default to 150x9999.
			if ( is_int( $size ) || ( 0 < intval( $size ) ) ) {
				$size = array( intval( $size ), intval( $size ) );
			} elseif ( ! is_string( $size ) && ! is_array( $size ) ) {
				$size = array( 200, 9999 );
			}
			$response = get_the_post_thumbnail( intval( $id ), $size );
		}

		return $response;
	}

	public function register_image_sizes () {
		if ( function_exists( 'add_image_size' ) ) { 
			add_image_size( 'podcast-thumbnail', 150, 9999 ); // 200 pixels wide (and unlimited height)
		}
	}

	public function ensure_post_thumbnails_support () {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) { add_theme_support( 'post-thumbnails' ); }
	}

	
    public function register_widget_area() {

        register_sidebar( array(
            'name' => 'Podcast sidebar',
            'id' => 'podcast_sidebar',
            'before_widget' => '<div class="widget">',
            'after_widget' => '</div>',
            'before_title' => '<h3>',
            'after_title' => '</h3>',
        	)
        );

    }
    

}