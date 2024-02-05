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
 **/

?>

<div id="<?php echo $player_id; ?>" class="castos-player <?php echo $player_mode ?>-mode" data-episode="<?php echo $episode_id ?>" data-player_id="<?php echo $player_id; ?>">
	<div class="player">
		<div class="player__main">
			<div class="player__artwork player__artwork-<?php echo $episode_id?>">
				<img src="<?php echo apply_filters( 'ssp_album_art_cover', $album_art['src'], get_the_ID() ); ?>" alt="<?php echo $podcast_title ?>" title="<?php echo $podcast_title ?>">
			</div>
			<div class="player__body">
				<div class="currently-playing">
					<div class="show player__podcast-title">
						<?php echo $podcast_title ?>
					</div>
					<div class="episode-title player__episode-title"><?php echo $episode->post_title ?></div>
				</div>
				<div class="play-progress">
					<div class="play-pause-controls">
						<button title="<?php _e( 'Play', 'seriously-simple-podcasting' ) ?>" class="play-btn">
							<span class="screen-reader-text"><?php _e( 'Play Episode', 'seriously-simple-podcasting' ) ?></span>
						</button>
						<button title="<?php _e( 'Pause', 'seriously-simple-podcasting' ) ?>" class="pause-btn hide">
							<span class="screen-reader-text"><?php _e( 'Pause Episode', 'seriously-simple-podcasting' ) ?></span>
						</button>
						<img src="<?php echo SSP_PLUGIN_URL ?>assets/css/images/player/images/icon-loader.svg" alt="<?php _e( 'Loading', 'seriously-simple-podcasting' ) ?>" class="ssp-loader hide"/>
					</div>
					<div>
						<audio preload="none" class="clip clip-<?php echo $episode_id ?>">
							<source src="<?php echo $audio_file ?>">
						</audio>
						<div class="ssp-progress" role="progressbar" title="<?php _e( 'Seek', 'seriously-simple-podcasting' ) ?>">
							<span class="progress__filled"></span>
						</div>
						<div class="ssp-playback playback">
							<div class="playback__controls">
								<button class="player-btn__volume" title="<?php _e( 'Mute/Unmute', 'seriously-simple-podcasting' ) ?>">
									<span class="screen-reader-text"><?php _e( 'Mute/Unmute Episode', 'seriously-simple-podcasting' ) ?></span>
								</button>
								<button data-skip="-10" class="player-btn__rwd" title="<?php _e( 'Rewind 10 seconds', 'seriously-simple-podcasting' ) ?>">
								<span class="screen-reader-text"><?php _e( 'Rewind 10 Seconds', 'seriously-simple-podcasting' ) ?></span>
								</button>
								<button data-speed="1" class="player-btn__speed" title="<?php _e( 'Playback Speed', 'seriously-simple-podcasting' ) ?>">1x</button>
								<button data-skip="30" class="player-btn__fwd" title="<?php _e( 'Fast Forward 30 seconds', 'seriously-simple-podcasting' ) ?>">
									<span class="screen-reader-text"><?php _e( 'Fast Forward 30 seconds', 'seriously-simple-podcasting' ) ?></span>
								</button>
							</div>
							<div class="playback__timers">
								<time class="ssp-timer">00:00</time>
								<span>/</span>
								<!-- We need actual duration here from the server -->
								<time class="ssp-duration"><?php echo $duration ?></time>
							</div>
						</div>
					</div>
				</div>
				<?php if ( $show_subscribe_button || $show_share_button ) : ?>
				<nav class="player-panels-nav">
					<?php if ( $show_subscribe_button ) : ?>
						<button class="subscribe-btn" id="subscribe-btn-<?php echo $episode_id ?>" title="<?php _e( 'Subscribe', 'seriously-simple-podcasting' ) ?>"><?php _e( 'Subscribe', 'seriously-simple-podcasting' ) ?></button>
					<?php endif; ?>
					<?php if ( $show_share_button ) : ?>
						<button class="share-btn" id="share-btn-<?php echo $episode_id ?>" title="<?php _e( 'Share', 'seriously-simple-podcasting' ) ?>"><?php _e( 'Share', 'seriously-simple-podcasting' ) ?></button>
					<?php endif; ?>
				</nav>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php if ( $show_subscribe_button || $show_share_button ) : ?>
	<div class="player-panels player-panels-<?php echo $episode_id ?>">
		<?php if ( $show_subscribe_button ) : ?>
		<div class="subscribe player-panel subscribe-<?php echo $episode_id ?>">
			<div class="close-btn close-btn-<?php echo $episode_id ?>">
				<span></span>
				<span></span>
			</div>
			<div class="panel__inner">
				<div class="subscribe-icons">
					<?php foreach ( $subscribe_links as $key => $subscribe_link ) : ?>
						<?php if ( ! empty( $subscribe_link['url'] ) ) : ?>
							<a href="<?php echo $subscribe_link['url'] ?>" target="_blank" rel="noopener noreferrer" class="<?php echo esc_attr( $subscribe_link['class']) ?>" title="Subscribe on  <?php echo $subscribe_link['label'] ?>">
								<span></span>
								<?php echo $subscribe_link['label'] ?>
							</a>
						<?php endif ?>
					<?php endforeach ?>
				</div>
				<div class="player-panel-row" area-label="RSS Feed URL">
					<div class="title">
						<?php _e( 'RSS Feed', 'seriously-simple-podcasting' ) ?>
					</div>
					<div>
						<input value="<?php echo $feed_url ?>" class="input-rss input-rss-<?php echo $episode_id ?>" title="<?php _e( 'RSS Feed URL', 'seriously-simple-podcasting' ) ?>" readonly />
					</div>
					<button class="copy-rss copy-rss-<?php echo $episode_id ?>" title="<?php _e('Copy RSS Feed URL', 'seriously-simple-podcasting') ?>"></button>
				</div>
			</div>
		</div>
		<?php endif ?>
		<?php if ( $show_share_button ) : ?>
		<div class="share share-<?php echo $episode_id ?> player-panel">
			<div class="close-btn close-btn-<?php echo $episode_id ?>">
				<span></span>
				<span></span>
			</div>
			<div class="player-panel-row">
				<div class="title">
					<?php _e( 'Share', 'seriously-simple-podcasting' ) ?>
				</div>
				<div class="icons-holder">
					<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $current_url; ?>&t=<?php echo $episode->post_title; ?>"
					   target="_blank" rel="noopener noreferrer" class="share-icon facebook" title="<?php _e( 'Share on Facebook', 'seriously-simple-podcasting' ) ?>">
						<span></span>
					</a>
					<a href="https://twitter.com/intent/tweet?text=<?php echo $current_url; ?>&url=<?php echo $episode->post_title; ?>"
					   target="_blank" rel="noopener noreferrer" class="share-icon twitter" title="<?php _e( 'Share on Twitter', 'seriously-simple-podcasting' ) ?>">
						<span></span>
					</a>
					<a href="<?php echo $audio_file ?>"
					   target="_blank" rel="noopener noreferrer" class="share-icon download" title="<?php _e( 'Download', 'seriously-simple-podcasting' ) ?>" download>
						<span></span>
					</a>
				</div>
			</div>
			<div class="player-panel-row">
				<div class="title">
					<?php _e( 'Link', 'seriously-simple-podcasting' ) ?>
				</div>
				<div>
					<input value="<?php echo $current_url ?>" class="input-link input-link-<?php echo $episode_id ?>" title="<?php _e('Episode URL', 'seriously-simple-podcasting') ?>" readonly />
				</div>
				<button class="copy-link copy-link-<?php echo $episode_id ?>" title="<?php _e('Copy Episode URL', 'seriously-simple-podcasting') ?>" readonly=""></button>
			</div>
			<div class="player-panel-row">
				<div class="title">
					<?php _e( 'Embed', 'seriously-simple-podcasting' ) ?>
				</div>
				<div style="height: 10px;">
					<input type="text" value='<?php echo esc_attr( $embed_code) ?>' title="<?php _e('Embed Code', 'seriously-simple-podcasting') ?>"
					       class="input-embed input-embed-<?php echo $episode_id ?>" readonly/>
				</div>
				<button class="copy-embed copy-embed-<?php echo $episode_id ?>" title="<?php _e('Copy Embed Code', 'seriously-simple-podcasting') ?>"></button>
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
						data-episode="<?php echo $item['episode_id']; ?>">
						<div class="playlist__item__cover">
							<img src="<?php echo $item['album_art']['src'] ?>" title="<?php echo $item['title']; ?>" alt="<?php echo $item['title'] ?>" />
						</div>
						<div class="playlist__item__details">
							<h2 class="playlist__episode-title" data-podcast="<?php echo $item['podcast_title']; ?>"><?php echo $item['title'] ?></h2>
							<p><?php echo $item['date'] . ' • ' . $item['duration']; ?></p>
							<p class="playlist__episode-description"><?php echo $item['excerpt']; ?></p>
						</div>
						<audio preload="none" class="clip clip-<?php echo $item['episode_id'] ?>">
							<source src="<?php echo $item['audio_file'] ?>">
						</audio>
					</li>
				<?php endforeach ?>
			</ul>
		</div>
	<?php endif; ?>
</div>
