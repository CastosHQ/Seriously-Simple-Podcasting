<?php
/**
 * @see \SeriouslySimplePodcasting\Controllers\Players_Controller::render_html_player();
 *
 * @var array $album_art
 * @var string $player_mode
 * @var WP_Post $episode
 * @var string $audio_file
 * @var string $duration
 * @var array $subscribe_links
 * @var string $episode_id
 * @var string $feed_url
 * @var string $episode_url
 * @var string $embed_code
 * @var string $podcast_title
 * @var bool $show_subscribe_button
 * @var bool $show_share_button
 **/

$episode_id = $episode_id . '-' . substr(md5(microtime()), 0, 7)

?>

<div class="castos-player <?php echo $player_mode ?>-mode" data-episode="<?php echo $episode_id?>">
	<div class="player">
		<div class="player__main">
			<div class="player__artwork player__artwork-<?php echo $episode_id?>">
				<img src="<?php echo apply_filters( 'ssp_album_art_cover', $album_art['src'], get_the_ID() ); ?>" alt="<?php echo $podcast_title ?>" title="<?php echo $podcast_title ?>">
			</div>
			<div class="player__body">
				<div class="currently-playing">
					<div class="show">
						<strong><?php echo $podcast_title ?></strong>
					</div>
					<div class="episode-title"><?php echo $episode->post_title ?></div>
				</div>
				<div class="play-progress">
					<div class="play-pause-controls">
						<button title="Play" class="play-btn play-btn-<?php echo $episode_id?>"><span class="screen-reader-text">Play Episode</span></button>
						<button alt="Pause" class="pause-btn pause-btn-<?php echo $episode_id?> hide"><span class="screen-reader-text">Pause Episode</span></button>
						<img src="<?php echo SSP_PLUGIN_URL ?>assets/css/images/player/images/icon-loader.svg" class="loader loader-<?php echo $episode_id ?> hide"/>
					</div>
					<div>
						<audio preload="none" class="clip clip-<?php echo $episode_id?>">
							<source loop preload="none" src="<?php echo $audio_file ?>">
						</audio>
						<div class="ssp-progress progress-<?php echo $episode_id ?>" title="Seek">
							<span class="progress__filled progress__filled-<?php echo $episode_id ?>"></span>
						</div>
						<div class="playback playback-<?php echo $episode_id ?>">
							<div class="playback__controls">
								<button class="player-btn__volume player-btn__volume-<?php echo $episode_id ?>" title="Mute/Unmute"><span class="screen-reader-text">Mute/Unmute Episode</span></button>
								<button data-skip="-10" class="player-btn__rwd" title="Rewind 10 seconds"><span class="screen-reader-text">Rewind 10 Seconds</span></button>
								<button data-speed="1" class="player-btn__speed player-btn__speed-<?php echo $episode_id ?>" title="Playback Speed">1x</button>
								<button data-skip="30" class="player-btn__fwd" title="Fast Forward 30 seconds"><span class="screen-reader-text">Fast Forward 30 seconds</span></button>
							</div>
							<div class="playback__timers">
								<time id="timer-<?php echo $episode_id ?>">00:00</time>
								<span>/</span>
								<!-- We need actual duration here from the server -->
								<time id="duration-<?php echo $episode_id ?>"><?php echo $duration ?></time>
							</div>
						</div>
					</div>
				</div>
				<nav class="player-panels-nav">
					<?php if ( $show_subscribe_button ) : ?>
						<button class="subscribe-btn" id="subscribe-btn-<?php echo $episode_id ?>" title="Subscribe"><?php _e( 'Subscribe', 'seriously-simple-podcasting' ) ?></button>
					<?php endif; ?>
					<?php if ( $show_share_button ) : ?>
						<button class="share-btn" id="share-btn-<?php echo $episode_id ?>" title="Share"><?php _e( 'Share', 'seriously-simple-podcasting' ) ?></button>
					<?php endif; ?>
				</nav>
			</div>
		</div>
	</div>
	<div class="player-panels player-panels-<?php echo $episode_id ?>">
		<div class="subscribe player-panel subscribe-<?php echo $episode_id ?>">
			<div class="close-btn close-btn-<?php echo $episode_id ?>">
				<span></span>
				<span></span>
			</div>
			<div class="panel__inner">
				<div class="subscribe-icons">
					<?php foreach ( $subscribe_links as $key => $subscribe_link ) : ?>
						<?php if ( ! empty( $subscribe_link['url'] ) ) : ?>
							<a href="<?php echo $subscribe_link['url'] ?>" target="_blank" class="<?php echo esc_attr( $subscribe_link['class']) ?>" title="Subscribe on  <?php echo $subscribe_link['label'] ?>">
								<span></span>
								<?php echo $subscribe_link['label'] ?>
							</a>
						<?php endif ?>
					<?php endforeach ?>
				</div>
				<div class="player-panel-row">
					<div class="title">
						<?php _e( 'RSS Feed', 'seriously-simple-podcasting' ) ?>
					</div>
					<div>
						<input value="<?php echo $feed_url ?>" class="input-rss input-rss-<?php echo $episode_id ?>" />
					</div>
					<button class="copy-rss copy-rss-<?php echo $episode_id ?>"></button>
				</div>
			</div>
		</div>
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
					<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $audio_file; ?>&t=<?php echo $episode->post_title; ?>"
					   target="_blank" class="share-icon facebook" title="<?php _e( 'Share on Facebook', 'seriously-simple-podcasting' ) ?>">
						<span></span>
					</a>
					<a href="https://twitter.com/intent/tweet?text=<?php echo $audio_file; ?>&url=<?php echo $episode->post_title; ?>"
					   target="_blank" class="share-icon twitter" title="<?php _e( 'Share on Twitter', 'seriously-simple-podcasting' ) ?>">
						<span></span>
					</a>
					<a href="<?php echo $audio_file ?>"
					   target="_blank" class="share-icon download" title="<?php _e( 'Download', 'seriously-simple-podcasting' ) ?>" download>
						<span></span>
					</a>
				</div>
			</div>
			<div class="player-panel-row">
				<div class="title">
					<?php _e( 'Link', 'seriously-simple-podcasting' ) ?>
				</div>
				<div>
					<input value="<?php echo $episode_url ?>" class="input-link input-link-<?php echo $episode_id ?>"/>
				</div>
				<button class="copy-link copy-link-<?php echo $episode_id ?>"></button>
			</div>
			<div class="player-panel-row">
				<div class="title">
					<?php _e( 'Embed', 'seriously-simple-podcasting' ) ?>
				</div>
				<div style="height: 10px;">
					<input type="text" value='<?php echo esc_attr( $embed_code) ?>'
					       class="input-embed input-embed-<?php echo $episode_id ?>"/>
				</div>
				<button class="copy-embed copy-embed-<?php echo $episode_id ?>"></button>
			</div>
		</div>
	</div>
</div>
