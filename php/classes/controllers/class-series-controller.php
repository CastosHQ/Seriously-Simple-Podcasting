<?php

namespace SeriouslySimplePodcasting\Controllers;


// Exit if accessed directly.
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Series_Handler;
use SeriouslySimplePodcasting\Handlers\Settings_Handler;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This is controller for Podcast and other SSP post types (which are enabled via settings) custom behavior.
 *
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       3.0.0
 */
class Series_Controller {

	use Useful_Variables;

	/**
	 * @var Series_Handler
	 * */
	private $series_handler;

	/**
	 * @var Castos_Handler
	 * */
	private $castos_handler;

	/**
	 * @var Settings_Handler
	 * */
	private $settings_handler;

	public function __construct( $series_handler, $castos_handler, $settings_handler ) {
		$this->series_handler = $series_handler;
		$this->castos_handler = $castos_handler;
		$this->settings_handler = $settings_handler;

		$this->init_useful_variables();

		$taxonomy = ssp_series_taxonomy();

		add_action( 'init', array( $this, 'register_taxonomy' ), 11 );
		add_filter( "{$taxonomy}_row_actions", array( $this, 'add_term_actions' ), 10, 2 );
		add_action( 'ssp_triggered_podcast_sync', array( $this, 'update_podcast_sync_status' ), 10, 3 );
		add_filter( 'term_name', array( $this, 'update_default_series_name' ), 10, 2 );

		add_action( 'created_series', array( $this, 'save_series_meta' ), 10, 2 );

		// Series list table.
		add_filter( 'manage_edit-series_columns', array( $this, 'edit_series_columns' ) );
		add_filter( 'manage_series_custom_column', array( $this, 'add_series_columns' ), 1, 3 );

		// Series term meta forms
		add_action( 'series_add_form_fields', array( $this, 'add_series_term_meta_fields' ), 10, 2 );
		add_action( 'series_edit_form_fields', array( $this, 'edit_series_term_meta_fields' ), 10, 2 );

		// Exclude series feed from the default feed
		add_action( 'create_series', array( $this, 'exclude_feed_from_default' ) );

		$this->prevent_deleting_default_series();

	}

	/**
	 * Adding it here, and not via default settings for the backward compatibility.
	 * So if users have their old series included in the default feed, it should not affect them.
	 * */
	public function exclude_feed_from_default( $series_id ) {
		ssp_update_option( 'exclude_feed', 'on', $series_id );
	}

	/**
	 * Adds series term metaboxes to the new series form.
	 */
	public function add_series_term_meta_fields( $taxonomy ) {
		// Add series image upload metabox.
		$this->series_image_uploader( $taxonomy );
	}

	/**
	 * Adds series term metaboxes to the edit series form.
	 */
	public function edit_series_term_meta_fields( $term, $taxonomy ) {
		// Add series image edit/upload metabox.
		$this->series_image_uploader( $taxonomy, 'UPDATE', $term );
		$this->show_feed_info( $term );
	}

	/**
	 * @param \WP_Term $term
	 *
	 * @return void
	 *
	 * Todo: get rid of HTML
	 */
	protected function show_feed_info( $term ) {
		$edit_feed_url = sprintf(
			'edit.php?post_type=%s&page=podcast_settings&tab=feed-details&feed-series=%s',
			SSP_CPT_PODCAST,
			$term->slug
		);
		$edit_feed_url = admin_url( $edit_feed_url );

		$feed_fields = $this->settings_handler->get_feed_fields();

		?>
		<tr class="form-field term-upload-wrap">
			<th scope="row">
				<label><?php echo __( 'Podcast Feed Details', 'seriously-simple-podcasting' ) ?></label>
				<p><a class="view-feed-link" href="<?php echo esc_url( $edit_feed_url ) ?>">
						<span class="dashicons dashicons-edit"></span>
						<?php echo __( 'Edit Feed Settings', 'seriously-simple-podcasting' ) ?></a></p>
				<p><a class="view-feed-link" href="<?php echo esc_url( ssp_get_feed_url( $term->slug ) ); ?>" target="_blank">
						<span class="dashicons dashicons-rss"></span>
						<?php echo __( 'View feed', 'seriously-simple-podcasting' ) ?>
					</a></p>
			</th>
			<td>
				<table style="border: 1px solid #ccc; width: 100%; padding: 0 10px;">
					<?php foreach ( $feed_fields as $field ) :
						$value = ssp_get_option( $field['id'], '', $term->term_id );
						if ( ! $value ) {
							$value = ssp_get_option( $field['id'] );
						}
						if ( ! $value || ! is_string( $value ) ) {
							continue;
						}
						if ( 'image' === $field['type'] ) {
							$value = sprintf('<img src="%s" style="width: 100px;">', $value );
						}
						?>
						<tr>
							<th><?php echo $field['label']; ?>:</th>
							<td><?php echo $value; ?></td>
						</tr>
					<?php endforeach; ?>
				</table>
			</td>
		</tr>
		<?php
	}

	/**
	 * Series Image Uploader metabox for add/edit.
	 *
	 * Todo: get rid of HTML
	 */
	public function series_image_uploader( $taxonomy, $mode = 'CREATE', $term = null ) {
		$series_settings = $this->token . '_series_image_settings';

		$default_image = esc_url( $this->assets_url . 'images/no-image.png' );
		$media_id      = $this->get_series_image_id( $term ) ?: '';
		$src           = $this->get_series_image_src( $term );
		$image_width   = "auto";
		$image_height  = "auto";

		$series_img_title = __( 'Podcast Image', 'seriously-simple-podcasting' );
		$upload_btn_text  = __( 'Choose podcast image', 'seriously-simple-podcasting' );
		$upload_btn_value = __( 'Add Image', 'seriously-simple-podcasting' );
		$upload_btn_title = __( 'Choose an image file', 'seriously-simple-podcasting' );
		$series_img_desc  = __( "Set an image as the artwork for the podcast page. No image will be set if not provided.", 'seriously-simple-podcasting' );
		$series_img_form_label = <<<HTML
<label>{$series_img_title}</label>
HTML;

		$series_img_form_fields = <<<HTML
<img id="{$taxonomy}_image_preview" data-src="{$default_image}" src="$src" width="{$image_width}" height="{$image_height}" />
<div>
	<input type="hidden" id="{$taxonomy}_image_id" name="{$series_settings}" value="{$media_id}" />
	<button id="{$taxonomy}_upload_image_button" class="button" data-uploader_title="{$upload_btn_title}" data-uploader_button_text="{$upload_btn_text}"><span class="dashicons dashicons-format-image"></span> {$upload_btn_value}</button>
	<button id="{$taxonomy}_remove_image_button" class="button">&times;</button>
</div>
<p class="description">{$series_img_desc}</p>
HTML;

		if ( $mode == 'CREATE' ) {
			echo <<<HTML
<div class="form-field term-upload-wrap">
	{$series_img_form_label}
	{$series_img_form_fields}
</div>
HTML;
		} else if ( $mode == 'UPDATE' ) {
			echo <<<HTML
<tr class="form-field term-upload-wrap">
	<th scope="row">{$series_img_form_label}</th>
	<td>
		{$series_img_form_fields}
	</td>
</tr>
HTML;
		}
	}

	/**
	 * @since 2.7.3
	 *
	 * @param \WP_Term $term
	 *
	 * @return int|null
	 */
	public function get_series_image_id( $term = null ) {
		if ( empty( $term ) ) {
			return null;
		}

		return get_term_meta( $term->term_id, $this->token . '_series_image_settings', true );
	}

	/**
	 * @since 2.7.3
	 *
	 * @param \WP_Term $term
	 *
	 * @return int|null
	 */
	public function get_series_image_src( $term ) {
		return ssp_get_podcast_image_src( $term );
	}

	/**
	 * Register columns for series list table
	 *
	 * @param  array $columns Default columns
	 *
	 * @return array          Modified columns
	 */
	public function edit_series_columns( $columns ) {

		unset( $columns['description'] );
		unset( $columns['posts'] );

		$columns['series_image']    = __( 'Podcast Image', 'seriously-simple-podcasting' );
		$columns['series_feed_url'] = __( 'Podcast feed URL', 'seriously-simple-podcasting' );
		$columns['posts']           = __( 'Episodes', 'seriously-simple-podcasting' );
		$columns = apply_filters( 'ssp_admin_columns_series', $columns );

		return $columns;
	}

	/**
	 * Display column data in series list table
	 *
	 * @param string $column_data Default column content
	 * @param string $column_name Name of current column
	 * @param integer $term_id ID of term
	 *
	 * @return string
	 * Todo: get rid of HTML
	 */
	public function add_series_columns( $column_data, $column_name, $term_id ) {

		switch ( $column_name ) {
			case 'series_feed_url':
				$series   = get_term( $term_id, 'series' );
				$feed_url = $this->get_series_feed_url( $series );

				$column_data = '<a href="' . esc_attr( $feed_url ) . '" target="_blank">' . esc_html( $feed_url ) . '</a>';
				break;
			case 'series_image':
				$series = get_term( $term_id, 'series' );
				$source = $this->get_series_image_src( $series );
				$column_data      = <<<HTML
<img id="{$series->name}_image_preview" src="{$source}" width="auto" height="auto" style="max-width:50px;" />
HTML;
				break;
		}

		return $column_data;
	}


	/**
	 * @since 2.7.3
	 *
	 * @param \WP_Term $term
	 *
	 * @return string
	 */
	public function get_series_feed_url( $term ){
		$series_slug = $term->slug;

		if ( get_option( 'permalink_structure' ) ) {
			$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
			$feed_url  = $this->home_url . 'feed/' . $feed_slug . '/' . $series_slug;
		} else {
			$feed_url = add_query_arg(
				array(
					'feed'           => $this->token,
					'podcast_series' => $series_slug,
				),
				$this->home_url
			);
		}

		return $feed_url;
	}

	/**
	 * Hook to allow saving series metadata.
	 */
	public function save_series_meta( $term_id, $tt_id ) {
		$this->insert_update_series_meta( $term_id, $tt_id );
		$this->save_series_data_to_feed( $term_id );
	}

	/**
	 * Store the Series Feed title as the Series name
	 *
	 * @param $term_id
	 */
	public function save_series_data_to_feed( $term_id ) {
		$term                    = get_term( $term_id );
		$title_option_name       = 'ss_podcasting_data_title_' . $term_id;
		$subtitle_option_name    = 'ss_podcasting_data_subtitle_' . $term_id;
		$description_option_name = 'ss_podcasting_data_description_' . $term_id;
		if ( ! empty( $term->name ) ) {
			update_option( $title_option_name, $term->name );
		}
		if ( ! empty( $term->description ) ) {
			update_option( $subtitle_option_name, $term->description );
			update_option( $description_option_name, $term->description );
		}
		if ( ! ssp_is_connected_to_castos() ) {
			return;
		}

		$default_series_id = ssp_get_default_series_id();
		if ( ! $default_series_id ) {
			/**
			 * It means we're creating the default series now,
			 * and we should update the default Podcast series ID instead of creating the new one.
			 * @see Castos_Handler::update_default_series_id()
			 * */
			return;
		}

		// push the series to Castos as a Podcast
		$series_data              = $this->castos_handler->generate_series_data_for_castos( $term_id );
		$series_data['series_id'] = $term_id;
		$this->castos_handler->update_podcast_data( $series_data );
	}

	/**
	 * Main method for saving or updating Series data.
	 */
	public function insert_update_series_meta( $term_id, $tt_id ) {
		$series_settings = SSP_CPT_PODCAST . '_series_image_settings';
		$prev_media_id   = get_term_meta( $term_id, $series_settings, true );
		$media_id        = sanitize_title( $_POST[ $series_settings ] );
		update_term_meta( $term_id, $series_settings, $media_id, $prev_media_id );
	}

	public function prevent_deleting_default_series() {
		add_filter( ssp_series_taxonomy() . '_row_actions', array( $this, 'disable_deleting_default' ), 10, 2 );
		add_action( 'pre_delete_term', array( $this, 'prevent_term_deletion' ), 10, 2 );
	}

	/**
	 * @param int $term_id
	 * @param string $taxonomy
	 *
	 * @return void
	 */
	public function prevent_term_deletion( $term_id, $taxonomy ) {
		if ( $taxonomy != ssp_series_taxonomy() ) {
			return;
		}
		if ( $term_id == ssp_get_default_series_id() ) {
			if ( isset( $_POST['action'] ) && 'delete-tag' === $_POST['action'] ) {
				$error = - 1; // it's an ajax action, just return -1
			} else {
				$error = new \WP_Error ();
				$error->add( 1, __( '<h2>You cannot delete the default podcast!', 'seriously-simple-podcasting' ) );
			}
			wp_die( $error );
		}
	}


	/**
	 * @return void
	 */
	public function register_taxonomy() {
		$this->series_handler->register_taxonomy();
	}

	/**
	 * @return void
	 */
	public function enable_default_series() {
		$this->series_handler->enable_default_series();
	}

	/**
	 * @param string $name
	 * @param \WP_Term $tag
	 *
	 * @return string
	 */
	public function update_default_series_name( $name, $tag ) {
		if ( ! is_object( $tag ) || $tag->taxonomy != ssp_series_taxonomy() ) {
			return $name;
		}

		if ( $tag->term_id == ssp_get_default_series_id() ) {
			return sprintf( '%s (%s)', $name, 'default' );
		}

		return $name;
	}

	/**
	 * @param $actions
	 * @param $tag
	 *
	 * @return mixed
	 */
	public function disable_deleting_default( $actions, $tag ) {
		if ( ! is_object( $tag ) || $tag->term_id != ssp_get_default_series_id() ) {
			return $actions;
		}

		$title = __( "You can't delete the default podcast", 'seriously-simple-podcasting' );

		$actions['delete'] = '<span title="' . $title .'">' . __( 'Delete', 'seriously-simple-podcasting' ) . '</span>';

		return $actions;
	}

	/**
	 * @param array $actions
	 * @param \WP_Term $term
	 *
	 * @return array
	 */
	public function add_term_actions( $actions, $term ) {

		$link = '<a href="%s">' . __( 'Edit&nbsp;Feed&nbsp;Details', 'seriously-simple-podcasting' ) . '</a>';
		$link = sprintf( $link, sprintf(
			'edit.php?post_type=%s&page=podcast_settings&tab=feed-details&feed-series=%s',
			SSP_CPT_PODCAST,
			$term->slug
		) );

		$actions['edit_feed_details'] = $link;

		return $actions;
	}

	/**
	 * @param int $podcast_id
	 * @param array $response
	 * @param string $status
	 *
	 * @return void
	 */
	public function update_podcast_sync_status( $podcast_id, $response, $status ) {

		$this->series_handler->update_sync_status( $podcast_id, $status );
	}
}
