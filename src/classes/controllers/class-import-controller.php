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
class Import_Controller {

	/**
	 * Render the progress bar to show the importing RSS feed progress
	 *
	 * @return false|string
	 */
	public function render_external_import_process() {
		ob_start();
		?>
		<h3 class="ssp-ssp-external-feed-message">Your external RSS feed is being imported. Please leave this window open until it completes</h3>
		<div id="ssp-external-feed-progress"></div>
		<div id="ssp-external-feed-status"><p>Commencing feed import</p></div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Render the form to enable importing an external RSS feed
	 *
	 * @return false|string
	 */
	public function render_external_import_form() {
		$post_types = ssp_post_types( true );
		$series = get_terms( 'series', array( 'hide_empty' => false ) );
		ob_start();
		?>
		<p>If you have a podcast hosted on an external service (like Libsyn, Soundcloud or Simplecast) enter the url to
			the RSS Feed in the form below and the plugin will import the episodes for you.</p>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">RSS feed</th>
				<td>
					<input id="external_rss" name="external_rss" type="text" placeholder="https://externalservice.com/rss" value="" class="regular-text">
				</td>
			</tr>
			<?php if ( count( $post_types ) > 1 ) { ?>
				<tr>
					<th scope="row">Post Type</th>
					<td>
						<select id="import_post_type" name="import_post_type">
							<?php foreach ( $post_types as $post_type ) { ?>
								<option value="<?php echo $post_type; ?>"><?php echo ucfirst( $post_type ); ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
			<?php } ?>
			<?php if ( count( $series ) > 1 ) { ?>
				<tr>
					<th scope="row">Series</th>
					<td>
						<select id="import_series" name="import_series">
							<?php foreach ( $series as $series_item ) { ?>
								<option value="<?php echo $series_item->term_id; ?>"><?php echo $series_item->name; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<p class="submit">
			<input id="ssp-settings-submit" name="Submit" type="submit" class="button-primary" value="<?php echo esc_attr( __( 'Begin Import Now', 'seriously-simple-podcasting' ) ) ?>"/>
		</p>
		<?php
		$html = ob_get_clean();

		return $html;
	}

}
