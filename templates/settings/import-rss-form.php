<?php
/**
 * @see Settings_Controller::render_external_import_form()
 *
 * @var array $post_types
 * @var array $series
 * */
?>

<div class="ssp-settings ssp-settings-import">
	<h2><?php _e( 'Import External RSS Feed', 'seriously-simple-podcasting' ) ?></h2>

	<p><?php _e( 'If you have a podcast hosted on an external service (like Libsyn, Soundcloud or Simplecast) enter the url to
	the RSS Feed in the form below and the plugin will import the episodes for you.', 'seriously-simple-podcasting' ) ?></p>
	<table class="form-table">
		<tbody>
		<tr>
			<th scope="row"><?php _e( 'RSS feed', 'seriously-simple-podcasting' ) ?></th>
			<td>
				<input id="external_rss" name="external_rss" type="text" placeholder="https://externalservice.com/rss"
					   value="" class="regular-text">
			</td>
		</tr>
		<?php if ( count( $post_types ) > 1 ) { ?>
			<tr>
				<th scope="row"><?php _e( 'Post Type', 'seriously-simple-podcasting' ) ?></th>
				<td>
					<select id="import_post_type" name="import_post_type">
						<?php foreach ( $post_types as $post_type ) { ?>
							<option value="<?php echo $post_type; ?>"><?php echo ucfirst( $post_type ); ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
		<?php } ?>
		<?php if ( count( $series ) >= 1 ) { ?>
			<tr>
				<th scope="row"><?php _e( 'Podcast', 'seriously-simple-podcasting' ) ?></th>
				<td>
					<select id="import_series" name="import_series">
						<?php foreach ( $series as $series_item ) { ?>
							<option value="<?php echo $series_item->term_id; ?>"><?php echo $series_item->name; ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<th scope="row"><?php _e( 'Import Podcast Data', 'seriously-simple-podcasting' ) ?></th>
			<td>
				<input id="import_podcast_data" type="checkbox" name="import_podcast_data" value="true" checked="checked">
				<label for="import_podcast_data">
				<span
					class="description"><?php _e( 'Import podcast data (Title, Description, Cover Art etc.).', 'seriously-simple-podcasting' ) ?></span>
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
