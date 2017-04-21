<?php

WP_CLI::add_command( 'ssp', 'SSP_CLI' );

/**
 * Wired Core CLI Functions
 */

class SSP_CLI extends WP_CLI_Command {


	/**
	 * Add posts to feed
	 *
	 * @subcommand add-feed
	 * @synopsis <feed> <slug> <author> <category-slug>
	 *
	 */
	public function add_feed( $args, $assoc_args ) {

		// Setup the args.
		list( $feed, $slug, $author, $category_slug ) = $args;

		$category = get_term_by( 'slug', $category_slug, 'category' );

		// Load the RSS file.
		$import = simplexml_load_file( $feed );

		// How many posts are we looking for?
		$count = count( $import->channel->item );

		// Some vars.
		$sucess     = 0;
		$failure    = 0;
		$post_added = 0;


		WP_CLI::line( 'We are looking for ' . $count . ' posts' );

		// Start the loop.
		// Because the items aren't held in an array directly, we need to use a for loop.
		for ( $i=0; $i < $count; $i++ ) {

			$itunes = $import->channel->item[ $i ]->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
			$title = trim( (string) $import->channel->item[ $i ]->title );

			// First condition, do we have a link? This makes it a lot easier.
			if ( isset( $import->channel->item[ $i ]->link ) ) {
				WP_CLI::line( '/////////////////////////' );
				WP_CLI::line( 'Link:    ' . $import->channel->item[ $i ]->link );

				// Parse the link into bits so that we can grab the slug for the query
				$url = parse_url( $import->channel->item[ $i ]->link );
				$explode = explode( '/', $url['path'] );
				WP_CLI::line( 'Slug:    ' . $explode[3] );

				// Setup a new WP_Query
				$posts = new WP_Query( array( 'name' => $explode[3] ) );
				WP_CLI::line( 'ID:      ' . $posts->post->ID );

				// Add the post to the series taxonomy
				$added = wp_add_object_terms( $posts->post->ID, $slug, 'series' );

				// Let's course correct on the audio_file
				$audio_file = get_post_meta( $posts->post->ID, 'audio_file', true );
				$bomb = explode( "\n", $audio_file );

				// Backwards compatible, save to a more common area.
				update_post_meta( $posts->post->ID, 'enclosure', $audio_file );

				// Save the url
				if ( ! empty( $bomb[0] ) ) {
					update_post_meta( $posts->post->ID, 'audio_file', $bomb[0] );
				}

				// Save the file size
				if ( ! empty( $bomb[1] ) ) {
					update_post_meta( $posts->post->ID, 'filesize_raw', $bomb[1] );
				}

				// Do a little error checking to ensure that it worked.
				if ( ! is_wp_error( $added ) ) {
					$sucess++;
					WP_CLI::success( $title );
				} else {
					$failure++;
					WP_CLI::warning( $title );
				}
				WP_CLI::line( '/////////////////////////' );
			}

			// Do we have an enclosure URL?
			// Really, they all should, and do, so we are going to do a little more checking.
			elseif ( isset( $import->channel->item[ $i ]->enclosure['url'] ) ) {
				// Need to cast things to a string as they come back as this SimpleXMLClass
				$url = (string) $import->channel->item[ $i ]->enclosure['url'];

				// Query args.
				// Let's check for audio_file or enclosure.
				$args = array(
					'meta_query' => array(
						'relation'    => 'OR',
						array(
							'key'     => 'audio_file',
							'value'   => $url,
							'compare' => 'LIKE',
						),
						array(
							'key' => 'enclosure',
							'value'   => $url,
							'compare' => 'LIKE',
						),
					)
				);

				// Run the query
				$meta_query = new WP_Query( $args );

				// Did we find anything? If we do, add the series.
				if ( $meta_query->found_posts > 0 ) {
					WP_CLI::line( '/////////////////////////' );
					WP_CLI::line( 'File:    ' . $url );
					WP_CLI::line( 'ID:      ' . $meta_query->post->ID );

					// Add the found post to the series tax.
					$added = wp_add_object_terms( $meta_query->post->ID, $slug, 'series' );

					// Let's course correct on the audio_file
					$audio_file = get_post_meta( $meta_query->post->ID, 'audio_file', true );
					$bomb = explode( "\n", $audio_file );

					// Backwards compatible, save to a more common area.
					update_post_meta( $meta_query->post->ID, 'enclosure', $audio_file );

					// Save the url
					if ( ! empty( $bomb[0] ) ) {
						update_post_meta( $meta_query->post->ID, 'audio_file', $bomb[0] );
					}

					// Save the file size
					if ( ! empty( $bomb[1] ) ) {
						update_post_meta( $meta_query->post->ID, 'filesize_raw', $bomb[1] );
					}

					// Error checking.
					if ( ! is_wp_error( $added ) ) {
						$sucess++;
						WP_CLI::success( $title );
					} else {
						$failure++;
						WP_CLI::warning( $title );
					}
					WP_CLI::line( '/////////////////////////' );

				}

				else {

					// Ok, we didn't find anything, let's add this post to the DB.
					// Setup the post array.
					$post_arr = array(
						'post_content'   => trim( (string) $itunes->summary ),
						'post_excerpt'   => trim( (string) $itunes->subtitle ),
						'post_title'     => $title,
						'post_status'    => 'publish',
						'post_author'    => $author,
						'post_date'      => date("Y-m-d H:i:s", strtotime( (string) $import->channel->item[ $i ]->pubDate ) ),
						'post_category'  => array( $category->term_id ),
					);

					WP_CLI::line( '/////////////////////////' );
					WP_CLI::line( 'Inserting: ' . $title );

					// Add the post
					$post_id = wp_insert_post( $post_arr );

					// New ID
					WP_CLI::line( 'New ID:    ' . $post_id );

					// Error checking.
					if ( ! is_wp_error( $post_id ) ) {
						// Update the added count
						$post_added++;
						// Set the post meta
						$audio_file = add_post_meta( $post_id, 'audio_file', $url );
						// Set the series term
						$added = wp_add_object_terms( $post_id, $slug, 'series' );
						// Log whether or not this failed.
						if ( ! is_wp_error( $added ) ) {
							$sucess++;
							WP_CLI::success( get_the_title( $post_id ) );
						} else {
							$failure++;
							WP_CLI::warning( $title );
						}
					}
					WP_CLI::line( '/////////////////////////' );
				}
			}

			// I don't think that we will ever hit this condition, but leaving it for posterities sake.
			else {
				WP_CLI::line( '/////////////////////////' );
				WP_CLI::warning( (string) $title );
				$failure++;
				WP_CLI::line( '/////////////////////////' );
			}
		}

		// Log the success/add/fail rate.
		WP_CLI::success( 'Success: ' . $sucess );
		WP_CLI::success( 'Added:   ' . $post_added );
		WP_CLI::warning( 'Failed:  ' . $failure );

	}
}