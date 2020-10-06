import {Component} from '@wordpress/element';

class CastosPlayer extends Component {
	render() {

		const {episodeId, episodeFileUrl} = this.props;

		return (
			<div className="dark-mode castos-player" data-episode={episodeId}>
				<div className="player">
					<div className="player__main">
						<div className="player__artwork player__artwork-<?php echo $episode_id?>"
						     style="background: url( <?php echo apply_filters( 'ssp_album_art_cover', $albumArt['src'], get_the_ID() ); ?> ) center center no-repeat; -webkit-background-size: cover;background-size: cover;">
						</div>
						<div className="player__body">
							<div className="currently-playing">
								<div className="show">
									<strong><?php echo $podcastTitle ?></strong>
								</div>
								<div className="episode-title"><?php echo $episode->post_title ?></div>
							</div>
							<div className="play-progress">
								<div className="play-pause-controls">
									<button title="Play"
									        className="play-btn play-btn-<?php echo $episode_id?>"></button>
									<button alt="Pause"
									        className="pause-btn pause-btn-<?php echo $episode_id?> hide"></button>
									<img
										src="<?php echo SSP_PLUGIN_URL ?>assets/css/images/player/images/icon-loader.svg"
										className="loader loader-<?php echo $episode_id ?> hide"/>
								</div>
								<div>
									<audio className="clip clip-<?php echo $episode_id?>">
										<source loop preload="none" src="<?php echo $audioFile ?>">
									</audio>
									<div className="progress progress-<?php echo $episode_id ?>" title="Seek">
										<span
											className="progress__filled progress__filled-<?php echo $episode_id ?>"></span>
									</div>
									<div className="playback playback-<?php echo $episode_id ?>">
										<div className="playback__controls">
											<button
												className="player-btn__volume player-btn__volume-<?php echo $episode_id ?>"
												title="Mute/Unmute"></button>
											<button data-skip="-10" className="player-btn__rwd"
											        title="Rewind 10 seconds"></button>
											<button data-speed="1"
											        className="player-btn__speed player-btn__speed-<?php echo $episode_id ?>"
											        title="Playback Speed">1x
											</button>
											<button data-skip="30" className="player-btn__fwd"
											        title="Fast Forward 30 seconds"></button>
										</div>
										<div className="playback__timers">
											<time id="timer-<?php echo $episode_id ?>">00:00</time>
											<span>/</span>
											<!-- We need actual duration here from the server -->
											<time id="duration-<?php echo $episode_id ?>"><?php echo $duration ?></time>
										</div>
									</div>
								</div>
							</div>
							<nav className="player-panels-nav">
								<button className="subscribe-btn" id="subscribe-btn-<?php echo $episode_id ?>"
								        title="Subscribe">Subscribe
								</button>
								<button className="share-btn" id="share-btn-<?php echo $episode_id ?>"
								        title="Share">Share
								</button>
							</nav>
						</div>
						<span className="powered-by">
              <a target="_blank" title="Broadcast by Castos" href="https://castos.com">
              </a>
            </span>
					</div>
				</div>
				<div className="player-panels player-panels-<?php echo $episode_id ?>">
					<div className="subscribe player-panel subscribe-<?php echo $episode_id ?>">
						<div className="close-btn close-btn-<?php echo $episode_id ?>">
							<span></span>
							<span></span>
						</div>
						<div className="panel__inner">
							<div className="subscribe-icons">
								<?php if($itunes['link']): ?>
								<a href="<?php echo $itunes['link'] ?>"
								   target="_blank" className="apple-podcasts" title="Subscribe on Apple Podcasts">
									<span></span>
									Apple Podcasts
								</a>
								<?php endif; ?>
								<?php if($stitcher['link']): ?>
								<a href="<?php echo $stitcher['link'] ?>" target="_blank" className="sticher"
								   title="Subscribe on Stitcher">
									<span></span>
									Stitcher
								</a>
								<?php endif; ?>
								<?php if($spotify['link']): ?>
								<a href="<?php echo $spotify['link'] ?>" target="_blank"
								   className="spotify"
								   title="Subscribe on Spotify">
									<span></span>
									Spotify
								</a>
								<?php endif; ?>
								<?php if($googlePlay['link']): ?>
								<a href="<?php echo $googlePlay['link'] ?>" target="_blank" className="google-play"
								   title="Subscribe on Google Play">
									<span></span>
									Google Play
								</a>
								<?php endif; ?>
							</div>
							<div className="player-panel-row">
								<div className="title">
									RSS Feed
								</div>
								<div>
									<input value="<?php echo $feedUrl ?>"
									       className="input-rss input-rss-<?php echo $episode_id ?>"/>
								</div>
								<button className="copy-rss copy-rss-<?php echo $episode_id ?>"></button>
							</div>
						</div>
					</div>
					<div className="share share-<?php echo $episode_id ?> player-panel">
						<div className="close-btn close-btn-<?php echo $episode_id ?>">
							<span></span>
							<span></span>
						</div>
						<div className="player-panel-row">
							<div className="title">
								Share
							</div>
							<div className="icons-holder">
								<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $audioFile; ?>&t=<?php echo $episode->post_title; ?>"
								   target="_blank" className="share-icon facebook" title="Share on Facebook">
									<span></span>
								</a>
								<a href="https://twitter.com/intent/tweet?text=<?php echo $audioFile; ?>&url=<?php echo $episode->post_title; ?>"
								   target="_blank" className="share-icon twitter" title="Share on Twitter">
									<span></span>
								</a>
								<a href="<?php echo $audioFile ?>"
								   target="_blank" className="share-icon download" title="Download" download>
									<span></span>
								</a>
							</div>
						</div>
						<div className="player-panel-row">
							<div className="title">
								Link
							</div>
							<div>
								<input value="<?php echo $audioFile ?>"
								       className="input-link input-link-<?php echo $episode_id ?>"/>
							</div>
							<button className="copy-link copy-link-<?php echo $episode_id ?>"></button>
						</div>
						<div className="player-panel-row">
							<div className="title">
								Embed
							</div>
							<div>
								<input
									value="<iframe src='<?php echo $audioFile ?>' frameborder='0' scrolling='no' width='100%' height='150'></iframe>"
									className="input-embed input-embed-<?php echo $episode_id ?>"/>
							</div>
							<button className="copy-embed copy-embed-<?php echo $episode_id ?>"></button>
						</div>
					</div>
				</div>
			</div>

	);
	}
}

export default CastosPlayer;
