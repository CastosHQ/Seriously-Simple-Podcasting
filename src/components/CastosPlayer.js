/**
 * WordPress dependencies
 */
import {Component} from '@wordpress/element';
import {Icon} from '@wordpress/components';
import {__} from '@wordpress/i18n';

class CastosPlayer extends Component {
	render() {
		return (
			<div className="podcast_player">
				<div className="ssp-player ssp-player-large" data-player-instance-number={1}
				     data-player-waveform-colour="#fff" data-player-waveform-progress-colour="#00d4f7"
				     data-source-file={this.props.episodeFileUrl}
				     id="ssp_player_id_1" style={{background: '#222222'}}>
					<div className="ssp-album-art-container">
						<div className="ssp-album-art" style={{
							background: 'url( this.props.episodeImage ) center center no-repeat',
							WebkitBackgroundSize: 'cover',
							backgroundSize: 'cover'
						}}/>
					</div>
					<div style={{overflow: 'hidden'}}>
						<div className="ssp-player-inner" style={{overflow: 'hidden'}}>
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
												<div data-waveform-id="waveform_1" id="waveform1" className="ssp-wave">
													<wave style={{
														display: 'block',
														position: 'relative',
														userSelect: 'none',
														height: '8px',
														overflow: 'hidden'
													}}>
														<wave style={{
															position: 'absolute',
															zIndex: 2,
															left: '0px',
															top: '0px',
															bottom: '0px',
															overflow: 'hidden',
															width: '0px',
															display: 'block',
															boxSizing: 'border-box',
															borderRight: '1px solid rgb(51, 51, 51)'
														}}>
															<canvas style={{
																position: 'absolute',
																left: '0px',
																top: '0px',
																bottom: '0px',
																height: '100%',
																width: '702px'
															}} width={702} height={8}/>
														</wave>
														<canvas style={{
															position: 'absolute',
															zIndex: 1,
															left: '0px',
															top: '0px',
															bottom: '0px',
															height: '100%',
															width: '702px'
														}} width={702} height={8}/>
													</wave>
												</div>
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
														<a className="ssp-episode-download" href="{this.props.episodeDownloadUrl}" target="_blank">
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

export default CastosPlayer;
