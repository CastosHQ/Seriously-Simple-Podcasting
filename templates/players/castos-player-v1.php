<div id="embed-app" class="dark-mode castos-player" data-episode="<?php echo $episode_id?>">
	<div class="player">
		<div class="player__main">
			<div class="player__artwork player__artwork-<?php echo $episode_id?>" style="background: url( <?php echo apply_filters( 'ssp_album_art_cover', $albumArt['src'], get_the_ID() ); ?> ) center center no-repeat; -webkit-background-size: cover;background-size: cover;">
			</div>
			<div class="player__body">
				<div class="currently-playing">
					<div class="show">
						<strong><?php echo $podcastTitle ?></strong>
					</div>
					<div class="episode-title"><?php echo $episode->post_title ?></div>
				</div>
				<div class="play-progress">
					<div class="play-pause-controls">
						<button title="Play" class="play-btn play-btn-<?php echo $episode_id?>"></button>
						<button alt="Pause" class="pause-btn pause-btn-<?php echo $episode_id?> hide"></button>
						<img src="<?php echo SSP_PLUGIN_URL ?>assets/css/images/player/images/icon-loader.svg" class="loader loader-<?php echo $episode_id ?> hide"/>
					</div>
					<div>
						<audio class="clip clip-<?php echo $episode_id?>">
							<source loop preload="none" src="<?php echo $audioFile ?>">
						</audio>
						<div class="progress progress-<?php echo $episode_id ?>" title="Seek">
							<span class="progress__filled progress__filled-<?php echo $episode_id ?>"></span>
						</div>
						<div class="playback playback-<?php echo $episode_id ?>">
							<div class="playback__controls">
								<button class="player-btn__volume player-btn__volume-<?php echo $episode_id ?>" title="Mute/Unmute"></button>
								<button data-skip="-10" class="player-btn__rwd" title="Rewind 10 seconds"></button>
								<button data-speed="1" class="player-btn__speed player-btn__speed-<?php echo $episode_id ?>" title="Playback Speed">1x</button>
								<button data-skip="30" class="player-btn__fwd" title="Fast Forward 30 seconds"></button>
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
					<button class="subscribe-btn" id="subscribe-btn-<?php echo $episode_id ?>" title="Subscribe">Subscribe</button>
					<button class="share-btn" id="share-btn-<?php echo $episode_id ?>" title="Share">Share</button>
				</nav>
			</div>
			<span class="powered-by">
              <a target="_blank" title="Broadcast by Castos" href="https://castos.com">
              </a>
            </span>
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
					<?php if($itunes['link']): ?>
						<a href="<?php echo $itunes['link'] ?>"
						   target="_blank" class="apple-podcasts" title="Subscribe on Apple Podcasts">
							<span></span>
							Apple Podcasts
						</a>
					<?php endif; ?>
					<?php if($stitcher['link']): ?>
						<a href="<?php echo $stitcher['link'] ?>" target="_blank" class="sticher"
						   title="Subscribe on Stitcher">
							<span></span>
							Stitcher
						</a>
					<?php endif; ?>
					<?php if($spotify['link']): ?>
						<a href="<?php echo $spotify['link'] ?>" target="_blank"
						   class="spotify"
						   title="Subscribe on Spotify">
							<span></span>
							Spotify
						</a>
					<?php endif; ?>
					<?php if($googlePlay['link']): ?>
						<a href="<?php echo $googlePlay['link'] ?>" target="_blank" class="google-play"
						   title="Subscribe on Google Play">
							<span></span>
							Google Play
						</a>
					<?php endif; ?>
				</div>
				<div class="player-panel-row">
					<div class="title">
						RSS Feed
					</div>
					<div>
						<input value="<?php echo $feedUrl ?>" class="input-rss input-rss-<?php echo $episode_id ?>" />
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
					Share
				</div>
				<div class="icons-holder">
					<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $audioFile; ?>&t=<?php echo $episode->post_title; ?>"
					   target="_blank" class="share-icon facebook" title="Share on Facebook">
						<span></span>
					</a>
					<a href="https://twitter.com/intent/tweet?text=<?php echo $audioFile; ?>&url=<?php echo $episode->post_title; ?>"
					   target="_blank" class="share-icon twitter" title="Share on Twitter">
						<span></span>
					</a>
					<a href="<?php echo $audioFile ?>"
					   target="_blank" class="share-icon download" title="Download" download>
						<span></span>
					</a>
				</div>
			</div>
			<div class="player-panel-row">
				<div class="title">
					Link
				</div>
				<div>
					<input value="<?php echo $audioFile ?>" class="input-link input-link-<?php echo $episode_id ?>"/>
				</div>
				<button class="copy-link copy-link-<?php echo $episode_id ?>"></button>
			</div>
			<div class="player-panel-row">
				<div class="title">
					Embed
				</div>
				<div>
					<input value="<iframe src='{{ $episode->getIframeUrl() }}' frameborder='0' scrolling='no' width='100%' height='150'></iframe>"
					       class="input-embed input-embed-<?php echo $episode_id ?>" />
				</div>
				<button class="copy-embed copy-embed-<?php echo $episode_id ?>"></button>
			</div>
		</div>
	</div>
</div>
