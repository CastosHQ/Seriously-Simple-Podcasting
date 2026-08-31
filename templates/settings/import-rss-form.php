<?php
/**
 * @see Settings_Controller::render_external_import_form()
 *
 * @var array $post_types
 * @var array $series
 * */

use SeriouslySimplePodcasting\Handlers\RSS_Import_Handler;

?>

<div class="ssp-settings ssp-settings-import">
	<h2><?php esc_html_e( 'Import External RSS Feed', 'seriously-simple-podcasting' ); ?></h2>

	<p><?php esc_html_e( 'If you have a podcast hosted on an external service (like Libsyn, Soundcloud or Simplecast) enter the url to
	the RSS Feed in the form below and the plugin will import the episodes for you.', 'seriously-simple-podcasting' ); ?></p>
	<table class="form-table">
		<tbody>
		<tr>
			<th scope="row"><?php esc_html_e( 'RSS feed', 'seriously-simple-podcasting' ); ?></th>
			<td>
				<input id="external_rss" name="external_rss" type="text" placeholder="https://externalservice.com/rss"
					   value="" class="regular-text">
			</td>
		</tr>
		<?php if ( count( $post_types ) > 1 ) { ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Post Type', 'seriously-simple-podcasting' ); ?></th>
				<td>
					<select id="import_post_type" name="import_post_type">
						<?php foreach ( $post_types as $post_type ) { ?>
							<option value="<?php echo $post_type; ?>"><?php echo ucfirst( $post_type ); ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Podcast', 'seriously-simple-podcasting' ); ?></th>
			<td>
				<select id="import_series" name="import_series">
					<option value="<?php echo esc_attr( RSS_Import_Handler::CREATE_NEW_SERIES ); ?>">
						<?php esc_html_e( 'Create new podcast', 'seriously-simple-podcasting' ); ?>
					</option>
					<?php foreach ( $series as $series_item ) { ?>
						<option value="<?php echo esc_attr( $series_item->term_id ); ?>"><?php echo esc_html( $series_item->name ); ?></option>
					<?php } ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'A new podcast is named after the imported feed. Choose an existing podcast to import the episodes into it instead.', 'seriously-simple-podcasting' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Import Podcast Data', 'seriously-simple-podcasting' ); ?></th>
			<td>
				<input id="import_podcast_data" type="checkbox" name="import_podcast_data" value="true" checked="checked">
				<label for="import_podcast_data">
				<span
					class="description"><?php esc_html_e( 'Import podcast data (Title, Description, Cover Art etc.).', 'seriously-simple-podcasting' ); ?></span>
				</label>
			</td>
		</tr>
		</tbody>
	</table>
	<p class="submit">
		<input id="ssp-settings-submit" name="Submit" type="submit" class="button-primary"
			   value="<?php echo esc_attr( __( 'Begin Import Now', 'seriously-simple-podcasting' ) ) ?>"/>
	</p>
</div>
