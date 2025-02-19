<?php
/**
 * Is used for rendering both HTML player and Playlist player
 *
 * @see \SeriouslySimplePodcasting\Controllers\Players_Controller::render_html_player();
 * @see \SeriouslySimplePodcasting\Controllers\Players_Controller::render_playlist_player();
 *
 * @var array $album_art
 * @var string $player_mode
 * @var WP_Post $episode
 * @var string $audio_file
 * @var string $duration
 * @var array $subscribe_links
 * @var string $episode_id
 * @var string $feed_url
 * @var string $current_url
 * @var string $embed_code
 * @var string $podcast_title
 * @var bool $show_subscribe_button
 * @var bool $show_share_button
 * @var int $player_id
 * @var bool $add_empty_warning
 * @var bool $class
 **/

?>
<div id="<?php echo esc_attr( $player_id ); ?>" class="castos-player <?php echo esc_attr( $player_mode ) ?>-mode <?php echo esc_attr( $class ) ?>"
	 data-episode="<?php echo esc_attr( $episode_id ) ?>" data-player_id="<?php echo esc_attr( $player_id ); ?>">
	<div class="player">
		<div class="player__main">
			<div class="player__artwork player__artwork-<?php echo esc_attr( $episode_id ) ?>">
				<img src="<?php echo esc_attr( apply_filters( 'ssp_album_art_cover', $album_art['src'], get_the_ID() ) ); ?>"
					 alt="<?php echo ! empty( $album_art['alt'] ) ? esc_attr( $album_art['alt'] ) : esc_attr( strip_tags( $podcast_title ) ); ?>"
					 title="<?php echo esc_attr( strip_tags( $podcast_title ) ) ?>">
			</div>
			<div class="player__body">
				<div class="currently-playing">
					<div class="show player__podcast-title">
						<?php echo wp_kses_post( $podcast_title ) ?>
					</div>
					<div class="episode-title player__episode-title"><?php echo wp_kses_post( $episode->post_title ); ?></div>
				</div>
				<div class="play-progress">
					<div class="play-pause-controls">
						<button title="<?php esc_attr_e( 'Play', 'seriously-simple-podcasting' )
						?>" aria-label="<?php esc_attr_e( 'Play Episode', 'seriously-simple-podcasting' )
						?>" aria-pressed="false" class="play-btn">
							<span class="screen-reader-text"><?php
								esc_attr_e( 'Play Episode', 'seriously-simple-podcasting' )
								?></span>
						</button>
						<button title="<?php esc_attr_e( 'Pause', 'seriously-simple-podcasting' )
						?>" aria-label="<?php esc_attr_e( 'Pause Episode', 'seriously-simple-podcasting' )
						?>" class="pause-btn hide">
							<span class="screen-reader-text"><?php
								esc_attr_e( 'Pause Episode', 'seriously-simple-podcasting' )
								?></span>
						</button>
						<img src="<?php echo SSP_PLUGIN_URL ?>assets/css/images/player/images/icon-loader.svg"
							 alt="<?php esc_attr_e( 'Loading', 'seriously-simple-podcasting' ) ?>" class="ssp-loader hide"/>
					</div>
					<div>
						<audio preload="none" class="clip clip-<?php esc_attr_e( $episode_id ) ?>">
							<source src="<?php echo $audio_file ?>">
						</audio>
						<div class="ssp-progress" role="progressbar" title="<?php
						esc_attr_e( 'Seek', 'seriously-simple-podcasting' )
						?>" aria-valuenow="<?php echo 0
						?>" aria-valuemin="<?php echo 0
						?>" aria-valuemax="<?php echo ssp_duration_seconds( $duration )
						?>">
							<span class="progress__filled"></span>
						</div>
						<div class="ssp-playback playback">
							<div class="playback__controls">
								<button class="player-btn__volume" title="<?php esc_attr_e( 'Mute/Unmute', 'seriously-simple-podcasting' ) ?>">
									<span class="screen-reader-text"><?php esc_attr_e( 'Mute/Unmute Episode', 'seriously-simple-podcasting' ) ?></span>
								</button>
								<button data-skip="-10" class="player-btn__rwd" title="<?php esc_attr_e( 'Rewind 10 seconds', 'seriously-simple-podcasting' ) ?>">
									<span class="screen-reader-text"><?php esc_attr_e( 'Rewind 10 Seconds', 'seriously-simple-podcasting' ) ?></span>
								</button>
								<button data-speed="1" class="player-btn__speed" title="<?php esc_attr_e( 'Playback Speed', 'seriously-simple-podcasting' ) ?>">1x</button>
								<button data-skip="30" class="player-btn__fwd" title="<?php esc_attr_e( 'Fast Forward 30 seconds', 'seriously-simple-podcasting' ) ?>">
									<span class="screen-reader-text"><?php esc_attr_e( 'Fast Forward 30 seconds', 'seriously-simple-podcasting' ) ?></span>
								</button>
							</div>
							<div class="playback__timers">
								<time class="ssp-timer">00:00</time>
								<span>/</span>
								<!-- We need actual duration here from the server -->
								<time class="ssp-duration" datetime="<?php echo ssp_iso_duration( $duration ) ?>"><?php echo esc_html( $duration ) ?></time>
							</div>
						</div>
					</div>
				</div>
				<?php if ( $show_subscribe_button || $show_share_button ) : ?>
					<nav class="player-panels-nav">
						<?php if ( $show_subscribe_button ) : ?>
							<button class="subscribe-btn" id="subscribe-btn-<?php echo esc_attr( $episode_id ) ?>" title="<?php esc_attr_e( 'Subscribe', 'seriously-simple-podcasting' ) ?>"><?php esc_attr_e( 'Subscribe', 'seriously-simple-podcasting' ) ?></button>
						<?php endif; ?>
						<?php if ( $show_share_button ) : ?>
							<button class="share-btn" id="share-btn-<?php echo esc_attr( $episode_id ) ?>" title="<?php esc_attr_e( 'Share', 'seriously-simple-podcasting' ) ?>"><?php esc_attr_e( 'Share', 'seriously-simple-podcasting' ) ?></button>
						<?php endif; ?>
					</nav>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php if ( $show_subscribe_button || $show_share_button ) : ?>
		<div class="player-panels player-panels-<?php echo esc_attr( $episode_id ) ?>">
			<?php if ( $show_subscribe_button ) : ?>
				<div class="subscribe player-panel subscribe-<?php echo esc_attr( $episode_id ) ?>">
					<div class="close-btn close-btn-<?php echo esc_attr( $episode_id ) ?>" aria-hidden="true">
						<span></span>
						<span></span>
					</div>
					<div class="panel__inner">
						<div class="subscribe-icons">
							<?php foreach ( $subscribe_links as $key => $subscribe_link ) : ?>
								<?php if ( ! empty( $subscribe_link['url'] ) ) : ?>
									<a href="<?php echo esc_attr( $subscribe_link['url'] ) ?>" target="_blank" rel="noopener noreferrer"
									   class="<?php echo esc_attr( $subscribe_link['class'] ) ?>"
									   title="Subscribe on  <?php echo esc_attr( $subscribe_link['label'] ) ?>">
										<span></span>
										<?php echo esc_html( $subscribe_link['label'] ) ?>
									</a>
								<?php endif ?>
							<?php endforeach ?>
						</div>
						<div class="player-panel-row" aria-label="RSS Feed URL">
							<div class="title"><?php esc_attr_e( 'RSS Feed', 'seriously-simple-podcasting' ) ?></div>
							<div>
								<input value="<?php echo esc_attr( $feed_url ) ?>" class="input-rss input-rss-<?php echo esc_attr( $episode_id ) ?>" title="<?php esc_attr_e( 'RSS Feed URL', 'seriously-simple-podcasting' ) ?>" readonly />
							</div>
							<button class="copy-rss copy-rss-<?php echo esc_attr( $episode_id ) ?>" title="<?php esc_attr_e( 'Copy RSS Feed URL', 'seriously-simple-podcasting' ) ?>" aria-label="<?php esc_attr_e( 'Copy RSS Feed URL', 'seriously-simple-podcasting' ) ?>"></button>
						</div>
					</div>
				</div>
			<?php endif ?>
			<?php if ( $show_share_button ) : ?>
				<div class="share share-<?php echo esc_attr( $episode_id ) ?> player-panel">
					<div class="close-btn close-btn-<?php echo esc_attr( $episode_id ) ?>" aria-hidden="true">
						<span></span>
						<span></span>
					</div>
					<div class="player-panel-row">
						<div class="title">
							<?php esc_attr_e( 'Share', 'seriously-simple-podcasting' ) ?>
						</div>
						<div class="icons-holder">
							<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $current_url ); ?>&t=<?php echo esc_attr( $episode->post_title ); ?>"
							   target="_blank" rel="noopener noreferrer" class="share-icon facebook" title="<?php esc_attr_e( 'Share on Facebook', 'seriously-simple-podcasting' ) ?>">
								<span></span>
							</a>
							<a href="https://twitter.com/intent/tweet?text=<?php echo esc_attr( $current_url ); ?>&url=<?php echo esc_attr( $episode->post_title ); ?>"
							   target="_blank" rel="noopener noreferrer" class="share-icon twitter" title="<?php esc_attr_e( 'Share on Twitter', 'seriously-simple-podcasting' ) ?>">
								<span></span>
							</a>
							<a href="<?php echo esc_attr( $audio_file ) ?>"
							   target="_blank" rel="noopener noreferrer" class="share-icon download" title="<?php esc_attr_e( 'Download', 'seriously-simple-podcasting' ) ?>" download>
								<span></span>
							</a>
						</div>
					</div>
					<div class="player-panel-row">
						<div class="title">
							<?php esc_attr_e( 'Link', 'seriously-simple-podcasting' ) ?>
						</div>
						<div>
							<input value="<?php echo esc_attr( $current_url ) ?>" class="input-link input-link-<?php echo esc_attr( $episode_id ) ?>" title="<?php esc_attr_e( 'Episode URL', 'seriously-simple-podcasting' ) ?>" readonly />
						</div>
						<button class="copy-link copy-link-<?php echo esc_attr( $episode_id ) ?>" title="<?php esc_attr_e( 'Copy Episode URL', 'seriously-simple-podcasting' ) ?>" aria-label="<?php esc_attr_e( 'Copy Episode URL', 'seriously-simple-podcasting' ) ?>" readonly=""></button>
					</div>
					<div class="player-panel-row">
						<div class="title">
							<?php esc_attr_e( 'Embed', 'seriously-simple-podcasting' ) ?>
						</div>
						<div style="height: 10px;">
							<input type="text" value='<?php echo esc_attr( $embed_code ) ?>'
								   title="<?php esc_attr_e( 'Embed Code', 'seriously-simple-podcasting' ) ?>"
								   class="input-embed input-embed-<?php echo $episode_id ?>" readonly/>
						</div>
						<button class="copy-embed copy-embed-<?php echo esc_attr( $episode_id ) ?>" title="<?php esc_attr_e( 'Copy Embed Code', 'seriously-simple-podcasting' ) ?>" aria-label="<?php esc_attr_e( 'Copy Embed Code', 'seriously-simple-podcasting' ) ?>"></button>
					</div>
				</div>
			<?php endif ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $playlist ) ) : ?>
		<div class="playlist__wrapper" data-page="1">
			<div class="loader"></div>
			<ul class="playlist__items">
				<?php foreach ( $playlist as $k => $item ) : ?>
					<li class="playlist__item<?php if ( 0 === $k ): ?> active<?php endif ?>"
						data-episode="<?php echo esc_attr( $item['episode_id'] ); ?>">
						<div class="playlist__item__cover">
							<img src="<?php echo esc_attr( $item['album_art']['src'] ) ?>" title="<?php echo esc_attr( strip_tags( $item['title'] ) ); ?>" alt="<?php echo esc_attr( strip_tags( $item['title'] ) ) ?>"/>
						</div>
						<div class="playlist__item__details">
							<h2 class="playlist__episode-title" data-podcast="<?php echo esc_attr( $item['podcast_title'] ); ?>"><?php echo wp_kses_post( $item['title'] ) ?></h2>
							<p><?php echo $item['date'] . ' • ' . $item['duration']; ?></p>
							<p class="playlist__episode-description"><?php echo wp_kses_post( $item['excerpt'] ); ?></p>
						</div>
						<audio preload="none" class="clip clip-<?php echo esc_attr( $item['episode_id'] ) ?>">
							<source src="<?php echo esc_attr( $item['audio_file'] ) ?>">
						</audio>
					</li>
				<?php endforeach ?>
			</ul>
		</div>
	<?php endif; ?>


	<?php if ( $add_empty_warning ) : ?>
		<p style="color:#BE123C"><em><?php
			_e( 'Warning: the player will not be shown to users because the episode file is missing, please upload a file or provide a URL to an existing audio file.', 'seriously-simple-podcasting' ); ?>
		</em></p>
	<?php endif; ?>
</div>
