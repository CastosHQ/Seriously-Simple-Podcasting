<?php
/**
 * Settings Renderer class.
 */

namespace SeriouslySimplePodcasting\Renderers;

// Exit if accessed directly.
use SeriouslySimplePodcasting\Traits\Singleton;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @author Sergey Zakharchenko
 * @package SeriouslySimplePodcasting
 * */
class Settings_Renderer {

	use Singleton;

	use Useful_Variables;

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
	 * @param array $data
	 * @param string $option_name
	 * @param string $default_option_name It's needed only for feed images
	 *
	 * @return string
	 * @since 2.9.3 Moved this function from the settings_controller.
	 * Todo: further refactoring - split each field to separate function.
	 *
	 */
	public function render_field( $field, $data, $option_name, $default_option_name = '' ) {

		$html = '';
		$default_option_name = $default_option_name ?: $option_name;

		// Get field class if supplied
		$class = '';
		if ( isset( $field['class'] ) ) {
			$class = $field['class'];
		}

		// Get parent class if supplied
		$parent_class = '';
		if ( isset( $field['parent_class'] ) ) {
			$parent_class = $field['parent_class'];
		}

		// Get data attributes if supplied
		$data_attrs = '';
		if ( ! empty( $field['data'] ) && is_array( $field['data'] ) ) {
			foreach ( $field['data'] as $k => $v ) {
				$data_attrs .= sprintf( ' data-%s="%s" ', $k, $v );
			}
		}

		switch ( $field['type'] ) {
			case 'text':
			case 'password':
			case 'number':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '" class="' . $class . '"' . $data_attrs . '/>' . "\n";
				break;
			case 'hidden':
				$html .= '<input name="' . esc_attr( $field['id'] ) . '" type="hidden" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $data ) . '" />' . "\n";
				break;
			case 'text_multi':
				foreach ( $field['fields'] as $f ) {
					$val      = isset( $data[ $f['id'] ] ) ? $data[ $f['id'] ] : '';
					$field_id = esc_attr( sprintf( '%s_%s', $field['id'], $f['id'] ) );
					$html     .= '<input id="' . $field_id . '" type="' . $f['type'] . '" name="' . esc_attr( sprintf( '%s[%s]', $option_name, $f['id'] ) ) . '" placeholder="' . esc_attr( $f['placeholder'] ) . '" value="' . esc_attr( $val ) . '" class="' . $class . '"' . $data_attrs . '/>';
					if ( isset( $f['description'] ) ) {
						$html .= '<label for=' . $field_id . '><span class="description">' . $f['description'] . '</span></label>';
					}
					$html .= '<br><br>' . "\n";
				}
				break;
			case 'colour-picker':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $data ) . '" class="' . $class . '"' . $data_attrs . '/>' . "\n";
				break;
			case 'text_secret':
				$placeholder = $field['placeholder'];
				if ( $data ) {
					$placeholder = __( 'Password stored securely', 'seriously-simple-podcasting' );
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="" class="' . $class . '"' . $data_attrs . '/>' . "\n";
				break;
			case 'textarea':
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" class="' . $class . '"' . $data_attrs . '>' . $data . '</textarea><br/>' . "\n";
				break;
			case 'checkbox':
				$checked = '';
				if ( $data && 'on' === $data ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" ' . $checked . ' class="' . $class . '"' . $data_attrs . '/>' . "\n";
				break;
			case 'checkbox_multi':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( in_array( $k, (array) $data, true ) ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" class="' . $class . '" /> ' . $v . '</label><br/>';
				}
				break;
			case 'select2_multi':
				$html .= '<select class="js-ssp-select2" name="' . esc_attr( $option_name ) . '[]" multiple="multiple">';
                foreach ( $field['options'] as $k => $v ) {
	                $selected = in_array( $k, (array) $data, true );
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" class="' . $class . '" /> ' . $v . '</option>';
                }
				$html .= '</select>';
				break;
			case 'radio':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( $k === $data ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" class="' . $class . '"' . $data_attrs . ' /> ' . $v . '</label><br/>';
				}
				break;
			case 'select':
				$html       .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '" class="' . $class . '"' . $data_attrs . '>';
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

					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . esc_html( $v ) . '</option>';

					$prev_group = $group;
				}
				$html .= '</select> ';
				break;
			case 'image':
				$html .= '<img id="' . esc_attr( $default_option_name ) . '_preview" src="' . esc_attr( $data ) . '" style="max-width:400px;height:auto;"' . $data_attrs . ' /><br/>' . "\n";
				$html .= '<input id="' . esc_attr( $default_option_name ) . '_button" type="button" class="button" value="' . __( 'Upload new image', 'seriously-simple-podcasting' ) . '" />' . "\n";
				$html .= '<input id="' . esc_attr( $default_option_name ) . '_delete" type="button" class="button" value="' . __( 'Remove image', 'seriously-simple-podcasting' ) . '" />' . "\n";
				$html .= '<input id="' . esc_attr( $default_option_name ) . '" type="hidden" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $data ) . '"/><br/>' . "\n";
				break;
			case 'feed_link':
				// Set feed URL based on site's permalink structure
				if ( get_option( 'permalink_structure' ) ) {
					$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
					$url       = $this->home_url . 'feed/' . $feed_slug;
				} else {
					$url = $this->home_url . '?feed=' . $this->token;
				}

				$html .= '<a href="' . esc_url( $url ) . '" target="_blank">' . $url . '</a>';
				break;
			case 'feed_link_series':
				// Set feed URL based on site's permalink structure
				if ( get_option( 'permalink_structure' ) ) {
					$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
					$url       = $this->home_url . 'feed/' . $feed_slug . '/series-slug';
				} else {
					$url = $this->home_url . '?feed=' . $this->token . '&podcast_series=series-slug';
				}

				$html .= esc_url( $url ) . "\n";
				break;
			case 'podcast_url':
				$slug        = apply_filters( 'ssp_archive_slug', _x( SSP_CPT_PODCAST, 'Podcast URL slug', 'seriously-simple-podcasting' ) );
				$podcast_url = $this->home_url . $slug;

				$html .= '<a href="' . esc_url( $podcast_url ) . '" target="_blank">' . $podcast_url . '</a>';
				break;
			case 'importing_podcasts':
				$data = ssp_get_importing_podcasts_count();
				$html .= '<input type="input" value="' . esc_attr( $data ) . '" class="' . $class . '" disabled' . $data_attrs . '/>' . "\n";
				break;
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
				case 'select2_multi':
					if ( ! empty( $field['description'] ) ) {
						$html .= '<br/><span class="description">' . esc_attr( $field['description'] ) . '</span>';
					}
					break;
				default:
					$html .= '<label for="' . esc_attr( $field['id'] ) . '"><span class="description">' . wp_kses_post( $field['description'] ) . '</span></label>' . "\n";
					break;
			}
		}

		if ( $parent_class ) {
			$html = '<div class="' . $parent_class . '">' . $html . '</div>';
		}

		return $html;
	}

	protected function home_url(){
		return trailingslashit( home_url() );
	}


}
