import {Component} from '@wordpress/element';
import classnames from "classnames";
import CastosPlayerPanels from "./CastosPlayerPanels";
/**
 * @todo clean up the inline styles better
 */
class CastosPlayer extends Component {
	render() {
		const {className, episodeId, episodeImage, episodeTitle, episodeFileUrl, episodeDuration} = this.props
		const playerClassNames = classnames(
			className,
			'castos-player',
			'dark-mode',
		);
		const playerBackgroundStyle = {
			background: 'url(' + episodeImage + ') center center no-repeat',
			WebkitBackgroundSize: 'cover',
			backgroundSize: 'cover'
		};
		const playerBackgroundClass = 'player__artwork player__artwork-'+episodeId;
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
			<div className={playerClassNames} data-episode={episodeId}>
				<div className="player">
					<div className="player__main">
						<div className={playerBackgroundClass} style={playerBackgroundStyle}></div>
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
										<span className={playProgressClass}></span>
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
				<CastosPlayerPanels
					className={className}
					episodeId={episodeId}
					episodeFileUrl={episodeFileUrl}
					episodeTitle={episodeTitle}
				/>
			</div>
		);
	}
}

export default CastosPlayer;
