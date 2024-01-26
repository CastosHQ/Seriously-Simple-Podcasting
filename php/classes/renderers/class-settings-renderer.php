<?php
/**
 * Settings Renderer class.
 */

namespace SeriouslySimplePodcasting\Renderers;

// Exit if accessed directly.
use SeriouslySimplePodcasting\Entities\Sync_Status;
use SeriouslySimplePodcasting\Interfaces\Service;
use SeriouslySimplePodcasting\Traits\Singleton;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @author Serhiy Zakharchenko
 * @package SeriouslySimplePodcasting
 * */
class Settings_Renderer implements Service {

	use Singleton;

	use Useful_Variables;

	protected $colorpicker_dependencies_enabled;

	/**
	 * Protected constructor.
	 */
	protected function __construct() {
		$this->init_useful_variables();
	}

	/**
	 * Prints the template.
	 *
	 * @param array $field
	 * @param array|string $data
	 * @param string $option_name
	 * @param string $default_option_name It's needed only for feed images
	 *
	 * @return string
	 * @since 2.9.3 Moved this function from the settings_controller.
	 * @since 2.15.0 Split into multiple render functions
	 */
	public function render_field( $field, $data, $option_name, $default_option_name = '' ) {

		$html = '';

		switch ( $field['type'] ) {
			case 'text':
			case 'password':
				$html .= $this->render_text_field( $field, $data, $option_name );
				break;
			case 'number':
				$html .= $this->render_number_field( $field, $data, $option_name );
			case 'hidden':
				$html .= $this->render_hidden_field( $field, $data );
				break;
			case 'button':
				$html .= $this->render_button( $field );
				break;
			case 'text_multi':
				$html .= $this->render_text_multi( $field, $data, $option_name );
				break;
			case 'color':
				$html .= $this->render_colorpicker( $field, $data, $option_name );
				break;
			case 'text_secret':
				$html .= $this->render_text_secret( $field, $data, $option_name );
				break;
			case 'textarea':
				$html .= $this->render_textarea( $field, $data, $option_name );
				break;
			case 'checkbox':
				$html .= $this->render_checkbox( $field, $data, $option_name );
				break;
			case 'checkbox_multi':
				$html .= $this->render_checkbox_multi( $field, $data, $option_name );
				break;
			case 'select2_multi':
				$html .= $this->render_select2_multi( $field, $data, $option_name );
				break;
			case 'radio':
				$html .= $this->render_radio( $field, $data, $option_name );
				break;
			case 'select':
				$html .= $this->render_select( $field, $data, $option_name );
				break;
			case 'image':
				$html .= $this->render_image( $field, $data, $option_name, $default_option_name );
				break;
			case 'feed_link':
				$html .= $this->render_feed_link();
				break;
			case 'feed_link_series':
				$html .= $this->render_feed_link_series();
				break;
			case 'podcast_url':
				$html .= $this->render_podcast_url();
				break;
			case 'importing_podcasts':
				$html .= $this->render_importing_podcasts( $field );
				break;
			case 'podcasts_sync':
				$html .= $this->render_sync_podcasts( $field, $data, $option_name );;
		}

		if ( ! in_array( $field['type'], array(
			'feed_link',
			'feed_link_series',
			'podcast_url',
			'hidden',
			'text_multi'
		), true ) ) {
			switch ( $field['type'] ) {
				case 'checkbox_multi':
				case 'radio':
				case 'select_multi':
				case 'color':
					if ( ! empty( $field['description'] ) ) {
						$html .= '<br/><span class="description">' . esc_attr( $field['description'] ) . '</span>';
					}
					break;
				default:
					if ( ! empty( $field['description'] ) ) {
						$html .= '<label for="' . esc_attr( $field['id'] ) . '"><span class="description">' . wp_kses_post( $field['description'] ) . '</span></label>' . "\n";
					}
					break;
			}
		}

		if ( ! empty( $field['parent_class'] ) ) {
			$html = '<div class="' . $field['parent_class'] . '">' . $html . '</div>';
		}

		return $html;
	}

	/**
	 * @param array $field
	 * @param string $data
	 *
	 * @return string
	 */
	protected function render_hidden_field( $field, $data ) {
		return '<input name="' . esc_attr( $field['id'] ) .
			   '" type="hidden" id="' . esc_attr( $field['id'] ) .
			   '" value="' . esc_attr( $data ) . '" />' . "\n";
	}

	/**
	 * @param array $field
	 *
	 * @return string
	 */
	protected function render_button( $field ) {
		return '<button type="button" id="' . esc_attr( $field['id'] ) .
			   '" class="' . esc_attr( $field['class'] ) . '" />' . esc_html( $field['label'] ) .  "</button>\n";
	}

	/**
	 * @param array $field
	 * @param array $data
	 * @param string $option_name
	 *
	 * @return string
	 */
	protected function render_text_multi( $field, $data, $option_name ) {
		$html = '';
		if ( empty( $field['fields'] ) ) {
			return $html;
		}

		foreach ( $field['fields'] as $f ) {
			$val      = isset( $data[ $f['id'] ] ) ? $data[ $f['id'] ] : '';
			$field_id = esc_attr( sprintf( '%s_%s', $field['id'], $f['id'] ) );
			$html     .= '<input id="' . $field_id . '" type="' . $f['type'] . '" name="' .
						 esc_attr( sprintf( '%s[%s]', $option_name, $f['id'] ) ) . '" placeholder="' .
						 esc_attr( $f['placeholder'] ) . '" value="' .
						 esc_attr( $val ) . '" class="' . $this->get_field_class( $field ) . '"' .
						 $this->get_data_attrs( $field ) . '/>';
			if ( isset( $f['description'] ) ) {
				$html .= '<label for=' . $field_id . '><span class="description">' . $f['description'] . '</span></label>';
			}
			$html .= '<br><br>' . "\n";
		}

		return $html;
	}

	/**
	 * @param array $field
	 * @param string $data
	 * @param string $option_name
	 *
	 * @return string
	 */
	protected function render_text_field( $field, $data, $option_name ) {
		return '<input id="' . esc_attr( $field['id'] ) .
			   '" type="' . $field['type'] .
			   '" name="' . esc_attr( $option_name ) .
			   '" placeholder="' . esc_attr( $this->get_field_placeholder( $field ) ) .
			   '" value="' . esc_attr( $data ) .
			   '" class="' . $this->get_field_class( $field ) .
			   '"' . $this->get_data_attrs( $field ) . '/>' . "\n";
	}

	/**
	 * @param array $field
	 * @param string $data
	 * @param string $option_name
	 *
	 * @return string
	 */
	protected function render_number_field( $field, $data, $option_name ) {
		$input = '<input id="' . esc_attr( $field['id'] ) .
			   '" type="number';

		if ( isset( $field['min'] ) ) {
			$input .= '" min="' . $field['min'];
		}

		if ( isset( $field['max'] ) ) {
			$input .= '" max="' . $field['max'];
		}

		if ( isset( $field['step'] ) ) {
			$input .= '" step="' . $field['step'];
		}

		$input .= '" name="' . esc_attr( $option_name ) .
			   '" placeholder="' . esc_attr( $this->get_field_placeholder( $field ) ) .
			   '" value="' . esc_attr( $data ) .
			   '" class="' . $this->get_field_class( $field ) .
			   '"' . $this->get_data_attrs( $field ) . '/>' . "\n";

		return $input;
	}

	/**
	 * @param array $field
	 * @param string $data
	 * @param string $option_name
	 *
	 * @return string
	 */
	protected function render_colorpicker( $field, $data, $option_name ) {
		$this->enable_colorpicker_dependencies();

		return '<input id="' . esc_attr( $field['id'] ) .
			   '" type="text" name="' . esc_attr( $option_name ) .
			   '" value="' . esc_attr( $data ) .
			   '" class="ssp-color-picker ' . $this->get_field_class( $field ) . '"' .
			   $this->get_data_attrs( $field ) . '/>' . "\n";
	}

	/**
	 * Enable colorpicker dependencies
	 *
	 * @return void
	 */
	protected function enable_colorpicker_dependencies() {
		if ( ! $this->colorpicker_dependencies_enabled ) {
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );
			add_action( 'admin_footer', function () {
				?>
				<script>
					jQuery(document).ready(function ($) {
						if ("function" === typeof $.fn.wpColorPicker) {
							$('.ssp-color-picker').wpColorPicker();
						}
					});
				</script>
				<?php
			}, 99 );

			$this->colorpicker_dependencies_enabled = true;
		}
	}

	/**
	 * @param array $field
	 * @param string $data
	 * @param string $option_name
	 *
	 * @return string
	 */
	protected function render_text_secret( $field, $data, $option_name ) {
		$placeholder = $this->get_field_placeholder( $field );
		if ( $data ) {
			$placeholder = __( 'Password stored securely', 'seriously-simple-podcasting' );
		}

		return '<input id="' . esc_attr( $field['id'] ) .
			   '" type="text" name="' . esc_attr( $option_name ) .
			   '" placeholder="' . esc_attr( $placeholder ) .
			   '" value="" class="' . $this->get_field_class( $field ) . '"' .
			   $this->get_data_attrs( $field ) . '/>' . "\n";
	}

	/**
	 * @param array $field
	 * @param string $data
	 * @param string $option_name
	 *
	 * @return string
	 */
	protected function render_textarea( $field, $data, $option_name ) {
		return '<textarea id="' . esc_attr( $field['id'] ) .
			   '" rows="5" cols="50" name="' . esc_attr( $option_name ) .
			   '" placeholder="' . esc_attr( $field['placeholder'] ) .
			   '" class="' . $this->get_field_class( $field ) . '"' .
			   $this->get_data_attrs( $field ) . '>' . $data . '</textarea><br/>' . "\n";
	}

	/**
	 * @param array $field
	 * @param string $data
	 * @param string $option_name
	 *
	 * @return string
	 */
	protected function render_checkbox( $field, $data, $option_name ) {
		$checked = '';
		if ( 'on' === $data ) {
			$checked = 'checked="checked"';
		}

		return '<input id="' . esc_attr( $field['id'] ) .
			   '" type="' . $field['type'] .
			   '" name="' . esc_attr( $option_name ) . '" ' . $checked .
			   ' class="' . $this->get_field_class( $field ) . '"' .
			   $this->get_data_attrs( $field ) . '/>' . "\n";
	}


	/**
	 * @param array $field
	 * @param array $data
	 * @param string $option_name
	 *
	 * @return string
	 */
	protected function render_checkbox_multi( $field, $data, $option_name ) {
		$html = '';
		if ( empty( $field['options'] ) ) {
			return $html;
		}
		foreach ( $field['options'] as $k => $v ) {
			$checked = false;
			if ( in_array( $k, (array) $data, true ) ) {
				$checked = true;
			}
			$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) .
					 '"><input type="checkbox" ' . checked( $checked, true, false ) .
					 ' name="' . esc_attr( $option_name ) .
					 '[]" value="' . esc_attr( $k ) .
					 '" id="' . esc_attr( $field['id'] . '_' . $k ) .
					 '" class="' . $this->get_field_class( $field ) . '" /> ' .
					 $v . '</label><br/>';
		}

		return $html;
	}

	/**
	 * @param array $field
	 * @param array $data
	 * @param string $option_name
	 *
	 * @return string
	 */
	protected function render_sync_podcasts( $field, $data, $option_name ) {
		$html = '';
		if ( empty( $field['options'] ) ) {
			return $html;
		}

		if ( empty( $data['statuses'] ) ) {
			return '<div class="ssp-sync-podcast api-error">' . __( 'Castos API error', 'seriously-simple-podcasting' ) . '</div>';
		}

		foreach ( $field['options'] as $podcast_id => $v ) {
			$status = $data['statuses'][ $podcast_id ];
			/**
			 * @var Sync_Status $status
			 * */
			$link = '';
			if ( $podcast_id ) {
				$podcast = get_term_by( 'term_id', $podcast_id, 'series' );
				$link    = admin_url( sprintf( 'edit.php?series=%s&post_type=%s', $podcast->slug, $this->token ) );
			}

			$checkbox = '<div class="ssp-sync-podcast__checkbox"><label for="' . esc_attr( $field['id'] . '_' . $podcast_id ) .
						'"><input type="checkbox" name="' . esc_attr( $option_name ) .
						'[]" value="' . esc_attr( $podcast_id ) . '" id="' . esc_attr( $field['id'] . '_' . $podcast_id ) .
						'" class="' . esc_attr ( $this->get_field_class( $field ) ) . '" /> ' . $v . '</label></div>';


			$classes = 'js-sync-status ' . $status->status;
			$is_full_label = true;
			$label = ssp_renderer()->fetch( 'settings/sync-label', compact('status', 'classes', 'link', 'is_full_label') );

			$html .= '<div class="ssp-sync-podcast js-sync-podcast">' . $checkbox . $label . '</div>';
		}

		return $html;
	}

	/**
	 * @param array $field
	 * @param array $data
	 * @param string $option_name
	 *
	 * @return string
	 */
	protected function render_select2_multi( $field, $data, $option_name ) {
		$html = '<select class="js-ssp-select2" name="' . esc_attr( $option_name ) . '[]" multiple="multiple">';
		foreach ( $field['options'] as $k => $v ) {
			$selected = in_array( $k, (array) $data, true );
			$html     .= '<option ' . selected( $selected, true, false ) .
						 ' value="' . esc_attr( $k ) .
						 '" id="' . esc_attr( $field['id'] . '_' . $k ) .
						 '" class="' . $this->get_field_class( $field ) . '"> ' . $v . '</option>';
		}
		$html .= '</select>';

		return $html;
	}

	/**
	 * @param array $field
	 * @param string $data
	 * @param string $option_name
	 *
	 * @return string
	 */
	protected function render_radio( $field, $data, $option_name ) {
		// Fix bug when data is not equals to any option
		if ( ! array_key_exists( $data, $field['options'] ) ) {
			$data = isset( $field['default'] ) ? $field['default'] : $field['options'][0];
		}

		$html = '';

		if ( empty( $field['options'] ) ) {
			return $html;
		}

		foreach ( $field['options'] as $k => $v ) {
			$checked = false;
			if ( $k === $data ) {
				$checked = true;
			}
			$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) .
					 '"><input type="radio" ' . checked( $checked, true, false ) .
					 ' name="' . esc_attr( $option_name ) .
					 '" value="' . esc_attr( $k ) .
					 '" id="' . esc_attr( $field['id'] . '_' . $k ) .
					 '" class="' . $this->get_field_class( $field ) . '"' .
					 $this->get_data_attrs( $field ) . ' /> ' . $v . '</label><br/>';
		}

		return $html;
	}


	/**
	 * @param array $field
	 * @param string $data
	 * @param string $option_name
	 *
	 * @return string
	 */
	protected function render_select( $field, $data, $option_name ) {
		$html       = '<select name="' . esc_attr( $option_name ) .
					  '" id="' . esc_attr( $field['id'] ) .
					  '" class="' . $this->get_field_class( $field ) . '"' .
					  $this->get_data_attrs( $field ) . '>';
		$prev_group = '';
		foreach ( $field['options'] as $k => $v ) {

			$group = '';
			if ( is_array( $v ) ) {
				if ( isset( $v['group'] ) ) {
					$group = $v['group'];
				}
				$v = $v['label'];
			}

			if ( $prev_group && $group !== $prev_group ) {
				$html .= '</optgroup>';
			}

			$selected = false;
			if ( $k === $data ) {
				$selected = true;
			}

			if ( $group && $group !== $prev_group ) {
				$html .= '<optgroup label="' . esc_attr( $group ) . '">';
			}

			$html .= '<option ' . selected( $selected, true, false ) .
					 ' value="' . esc_attr( $k ) . '">' . esc_html( $v ) . '</option>';

			$prev_group = $group;
		}
		$html .= '</select> ';

		return $html;
	}

	/**
	 * @param array $field
	 * @param string $data
	 * @param string $option_name
	 * @param string $default_option_name
	 *
	 * @return string
	 */
	protected function render_image( $field, $data, $option_name, $default_option_name = '' ) {
		$default_option_name = $default_option_name ?: $option_name;

		$html = '<img id="' . esc_attr( $default_option_name ) . '_preview" src="' .
				esc_attr( $data ) . '" style="max-width:400px;height:auto;"' .
				$this->get_data_attrs( $field ) . ' /><br/>' . "\n";

		$html .= '<input id="' . esc_attr( $default_option_name ) .
				 '_button" type="button" class="button" value="' .
				 __( 'Upload new image', 'seriously-simple-podcasting' ) . '" />' . "\n";

		$html .= '<input id="' . esc_attr( $default_option_name ) .
				 '_delete" type="button" class="button" value="' .
				 __( 'Remove image', 'seriously-simple-podcasting' ) . '" />' . "\n";

		$html .= '<input id="' . esc_attr( $default_option_name ) .
				 '" type="hidden" name="' . esc_attr( $option_name ) .
				 '" value="' . esc_attr( $data ) . '"/><br/>' . "\n";

		return $html;
	}

	/**
	 * @return string
	 */
	protected function render_feed_link() {
		// Set feed URL based on site's permalink structure
		$links = '';
		$default_podcast_id = ssp_get_default_series_id();
		foreach ( ssp_get_podcasts() as $podcast ) {
			$url   = ssp_get_feed_url( $podcast->slug );
			$link = '<a href="' . esc_url( $url ) . '" target="_blank">' . $url . '</a>' . '<br />';
			$name = ( $podcast->term_id === $default_podcast_id ) ? ssp_get_default_series_name( $podcast->name ) : $podcast->name;
			$links .= sprintf(__('%1$s: %2$s', 'seriously-simple-podcasting'), $name, $link );
		}

		return $links;
	}

	protected function render_feed_link_series() {
		// Set feed URL based on site's permalink structure
		$url = ssp_get_feed_url( 'podcast-slug' );

		return esc_url( $url ) . "\n";
	}

	protected function render_podcast_url() {
		$slug = apply_filters(
			'ssp_archive_slug',
			_x( SSP_CPT_PODCAST, 'Podcast URL slug', 'seriously-simple-podcasting' )
		);

		$podcast_url = $this->home_url . $slug . '/';

		return '<a href="' . esc_url( $podcast_url ) . '" target="_blank">' . $podcast_url . '</a>';
	}

	/**
	 * @param array $field
	 *
	 * @return string
	 */
	protected function render_importing_podcasts( $field ) {
		$data = ssp_get_importing_podcasts_count();

		return '<input type="input" value="' . esc_attr( $data ) .
			   '" class="' . $this->get_field_class( $field ) . '" disabled' .
			   $this->get_data_attrs( $field ) . '/>' . "\n";
	}

	/**
	 * @param $field
	 *
	 * @return string
	 */
	protected function get_field_placeholder( $field ) {
		return isset( $field['placeholder'] ) ? $field['placeholder'] : '';
	}

	/**
	 * @param $field
	 *
	 * @return string
	 */
	protected function get_field_class( $field ) {
		return isset( $field['class'] ) ? $field['class'] : '';
	}

	/**
	 * @param $field
	 *
	 * @return string
	 */
	protected function get_data_attrs( $field ) {
		$data_attrs = '';
		if ( ! empty( $field['data'] ) && is_array( $field['data'] ) ) {
			foreach ( $field['data'] as $k => $v ) {
				$data_attrs .= sprintf( ' data-%s="%s" ', $k, $v );
			}
		}

		return $data_attrs;
	}


	protected function home_url() {
		return trailingslashit( home_url() );
	}


}
