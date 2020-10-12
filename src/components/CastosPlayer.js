import {Component} from '@wordpress/element';
/**
 * @todo clean up the inline styles better
 */
class CastosPlayer extends Component {
	render() {
		const {className, episodeId, episodeTitle, episodeFileUrl, episodeDuration} = this.props

		const playerBackgroundClass = 'player__artwork player__artwork-'+episodeId;
		const playerBackgroundStyle = 'background: url( '+episodeFileUrl+' ) center center no-repeat; -webkit-background-size: cover;background-size: cover;';

		const playButtonClass = 'play-btn play-btn-'+episodeId;
		const pauseButtonClass = 'pause-btn pause-btn-'+episodeId+' hide';

		const loaderSVG = "../assets/css/images/player/images/icon-loader.svg"; // @todo might need to sort out path
		const loaderClass = 'loader loader-'+episodeId+' hide';

		const audioElementClass = 'clip clip-'+episodeId;
		const progressClass = 'progress progress-'+episodeId;
		const playProgressClass = 'progress__filled progress__filled-'+episodeId;
		const playbackClass = 'playback playback-'+episodeId;
		const muteClass = 'player-btn__volume player-btn__volume-'+episodeId;
		const speedClass = 'player-btn__speed player-btn__speed-'+episodeId;

		const timerId = 'timer-'+episodeId;
		const durationId = 'duration-'+episodeId;
		const subscribeButtonId = 'subscribe-btn-'+episodeId;
		const shareButtonId = 'share-btn-'+episodeId;

		return (

			<div className="dark-mode castos-player" data-episode={episodeId}>
				<div className="player">
					<div className="player__main">
						<div className={playerBackgroundClass}
						     style={playerBackgroundStyle}>
						</div>
						<div className="player__body">
							<div className="currently-playing">
								<div className="show">
									<strong>{episodeTitle}</strong>
								</div>
								<div className="episode-title">{episodeTitle}</div>
							</div>
							<div className="play-progress">
								<div className="play-pause-controls">
									<button title="Play" className={playButtonClass}></button>
									<button alt="Pause" className={pauseButtonClass}></button>
									<img src={loaderSVG} className={loaderClass}/>
								</div>
								<div>
									<audio className={audioElementClass}>
										<source loop preload="none" src={episodeFileUrl} />
									</audio>
									<div className={progressClass} title="Seek">
										<span
											className={playProgressClass}></span>
									</div>
									<div className={playbackClass}>
										<div className="playback__controls">
											<button className={muteClass} title="Mute/Unmute"></button>
											<button data-skip="-10" className="player-btn__rwd" title="Rewind 10 seconds"></button>
											<button data-speed="1" className={speedClass} title="Playback Speed">1x</button>
											<button data-skip="30" className="player-btn__fwd" title="Fast Forward 30 seconds"></button>
										</div>
										<div className="playback__timers">
											<time id={timerId}>00:00</time>
											<span>/</span>
											<!-- We need actual duration here from the server -->
											<time id={durationId}>{episodeDuration}</time>
										</div>
									</div>
								</div>
							</div>
							<nav className="player-panels-nav">
								<button className="subscribe-btn" id={subscribeButtonId} title="Subscribe">Subscribe</button>
								<button className="share-btn" id={shareButtonId} title="Share">Share</button>
							</nav>
						</div>
						{/*<span className="powered-by">
                            <a target="_blank" title="Broadcast by Castos" href="https://castos.com"></a>
                        </span>*/}
					</div>
				</div>

				{/*<div className="player-panels player-panels-<?php echo $episode_id ?>">
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
							<div style="height: 10px;">
								<input value='<?php echo $embed_code ?>'
								       className="input-embed input-embed-<?php echo $episode_id ?>"/>
							</div>
							<button className="copy-embed copy-embed-<?php echo $episode_id ?>"></button>
						</div>
					</div>
				</div>*/}

			</div>

		);
	}
}

export default CastosPlayer;
