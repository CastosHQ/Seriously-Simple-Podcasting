<?php

namespace SeriouslySimplePodcasting\Controllers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 *
 * @author      Jonathan Bossenger
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       1.0
 */
class Extensions_Controller extends Controller {

	public function render_seriously_simple_sidebar() {
		$image_dir = $this->assets_url . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
		ob_start();
		include( $this->template_path . DIRECTORY_SEPARATOR . 'settings-sidebar.php' );

		return ob_get_clean();
	}

	public function render_seriously_simple_extensions() {
		add_thickbox();
		$image_dir  = $this->assets_url . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
		$extensions = array(
			'connect'     => array(
				'title'       => 'NEW - Castos Podcast Hosting',
				'image'       => $image_dir . 'castos-icon-extension.jpg',
				'url'         => SSP_CASTOS_APP_URL,
				'description' => 'Host your podcast media files safely and securely in a CDN-powered cloud platform designed specifically to connect beautifully with Seriously Simple Podcasting.  Faster downloads, better live streaming, and take back security for your web server with Castos.',
				'new_window'  => true,
			),
			'stats'       => array(
				'title'       => 'Seriously Simple Podcasting Stats',
				'image'       => $image_dir . 'ssp-stats.jpg',
				'url'         => add_query_arg( array( 'tab' => 'plugin-information', 'plugin' => 'seriously-simple-stats', 'TB_iframe' => 'true', 'width' => '772', 'height' => '859' ), admin_url( 'plugin-install.php' ) ),
				'description' => 'Seriously Simple Stats offers integrated analytics for your podcast, giving you access to incredibly useful information about who is listening to your podcast and how they are accessing it.',
			),
			'transcripts' => array(
				'title'       => 'Seriously Simple Podcasting Transcripts',
				'image'       => $image_dir . 'ssp-transcripts.jpg',
				'url'         => add_query_arg( array( 'tab' => 'plugin-information', 'plugin' => 'seriously-simple-transcripts', 'TB_iframe' => 'true', 'width' => '772', 'height' => '859' ), admin_url( 'plugin-install.php' ) ),
				'description' => 'Seriously Simple Transcripts gives you a simple and automated way for you to add downloadable transcripts to your podcast episodes. It’s an easy way for you to provide episode transcripts to your listeners without taking up valuable space in your episode content.',
			),
			'speakers'    => array(
				'title'       => 'Seriously Simple Podcasting Speakers',
				'image'       => $image_dir . 'ssp-speakers.jpg',
				'url'         => add_query_arg( array( 'tab' => 'plugin-information', 'plugin' => 'seriously-simple-speakers', 'TB_iframe' => 'true', 'width' => '772', 'height' => '859' ), admin_url( 'plugin-install.php' ) ),
				'description' => 'Does your podcast have a number of different speakers? Or maybe a different guest each week? Perhaps you have unique hosts for each episode? If any of those options describe your podcast then Seriously Simple Speakers is the add-on for you!',
			),
			'genesis'     => array(
				'title'       => 'Seriously Simple Podcasting Genesis Support ',
				'image'       => $image_dir . 'ssp-genesis.jpg',
				'url'         => add_query_arg( array( 'tab' => 'plugin-information', 'plugin' => 'seriously-simple-podcasting-genesis-support', 'TB_iframe' => 'true', 'width' => '772', 'height' => '859' ), admin_url( 'plugin-install.php' ) ),
				'description' => 'The Genesis compatibility add-on for Seriously Simple Podcasting gives you full support for the Genesis theme framework. It adds support to the podcast post type for the features that Genesis requires. If you are using Genesis and Seriously Simple Podcasting together then this plugin will make your website look and work much more smoothly.',
			),
		);

		$html = '<div id="ssp-extensions">';
		foreach ( $extensions as $extension ) {
			$html .= '<div class="ssp-extension"><h3 class="ssp-extension-title">' . $extension['title'] . '</h3>';
			if (isset($extension['new_window']) && $extension['new_window']){
				$html .= '<a href="' . $extension['url'] . '" title="' . $extension['title'] . '" target="_blank"><img width="880" height="440" src="' . $extension['image'] . '" class="attachment-showcase size-showcase wp-post-image" alt="" title="' . $extension['title'] . '"></a>';
			}else {
				$html .= '<a href="' . $extension['url'] . '" title="' . $extension['title'] . '" class="thickbox"><img width="880" height="440" src="' . $extension['image'] . '" class="attachment-showcase size-showcase wp-post-image" alt="" title="' . $extension['title'] . '"></a>';
			}
			$html .= '<p></p>';
			$html .= '<p>' . $extension['description'] . '</p>';
			$html .= '<p></p>';
			if (isset($extension['new_window']) && $extension['new_window']){
				$html .= '<a href="' . $extension['url'] . '" title="' . $extension['title'] . '" target="_blank" class="button-secondary">Get this Extension</a>';
			}else {
				$html .= '<a href="' . $extension['url'] . '" title="' . $extension['title'] . '" class="thickbox button-secondary">Get this Extension</a>';
			}
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}
}
