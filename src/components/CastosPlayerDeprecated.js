import {Component} from '@wordpress/element';
/**
 * @todo clean up the inline styles better
 */
class CastosPlayerDeprecated extends Component {
	render() {
		const playerBackgroundStyle = {background: '#222222'};
		const overflowStyle = {
			overflow: 'hidden'
		};
		const imageStyle = {
			background: "url(" + this.props.episodeImage + ")",
			WebkitBackgroundSize: 'cover',
			backgroundSize: 'cover'
		};
		return (
			<div className={this.props.className}>
				<div className="ssp-player ssp-player-large"
					 data-player-instance-number={"1"}
					 data-player-waveform-colour="#fff" data-player-waveform-progress-colour="#00d4f7"
					 data-source-file={this.props.episodeFileUrl}
					 id="ssp_player_id_1" style={playerBackgroundStyle}>
					<div className="ssp-album-art-container">
						<div className="ssp-album-art" style={imageStyle}/>
					</div>
					<div style={overflowStyle}>
						<div className="ssp-player-inner" style={overflowStyle}>
							<div className="ssp-player-info">
								<div style={{width: '80%', float: 'left'}}>
									<h3 className="ssp-player-title episode-title">{this.props.episodeTitle}</h3>
								</div>
								<div className="ssp-download-episode" style={{overflow: 'hidden', textAlign: 'right'}}/>
								<div>&nbsp;</div>
								<div className="ssp-media-player">
									<div className="ssp-custom-player-controls">
										<div className="ssp-play-pause" id="ssp-play-pause">
											<span className="ssp-icon ssp-icon-play_icon">&nbsp;</span>
										</div>
										<div className="ssp-wave-form">
											<div className="ssp-inner">
												<div data-waveform-id="waveform_1" id="waveform1" className="ssp-wave"></div>
											</div>
										</div>
										<div className="ssp-time-volume">
											<div className="ssp-duration">
												<span id="sspPlayedDuration">00:00</span> / <span
												id="sspTotalDuration">{this.props.episodeDuration}</span>
											</div>
											<div className="ssp-volume">
												<div className="ssp-back-thirty-container">
													<div className="ssp-back-thirty-control" id="ssp-back-thirty">
														<i className="ssp-icon icon-replay">&nbsp;</i>
													</div>
												</div>
												<div className="ssp-playback-speed-label-container">
													<div className="ssp-playback-speed-label-wrapper">
														<span data-playback-speed-id="ssp_playback_speed_1"
															  id="ssp_playback_speed1"
															  data-ssp-playback-rate={1}>1X</span>
													</div>
												</div>
												<div className="ssp-download-container">
													<div className="ssp-download-control">
														<a className="ssp-episode-download"
														   href={this.props.episodeDownloadUrl} target="_blank"
														   rel="noopener noreferrer">
															<i className="ssp-icon icon-cloud-download">&nbsp;</i>
														</a>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		);
	}
}

export default CastosPlayerDeprecated;
