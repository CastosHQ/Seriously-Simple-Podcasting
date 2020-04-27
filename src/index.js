import { registerBlockType } from '@wordpress/blocks';
import { RichText } from '@wordpress/block-editor';

//https://developer.wordpress.org/block-editor/tutorials/block-tutorial/introducing-attributes-and-editable-fields/

registerBlockType( 'seriously-simple-podcasting/example-03-editable-esnext', {
	title: 'Example: Editable (esnext)',
	icon: 'universal-access-alt',
	category: 'layout',
	attributes: {
		content: {
			type: 'array',
			source: 'children',
			selector: 'p',
		},
	},
	example: {
		attributes: {
			content: 'Hello World',
		},
	},
	edit: ( props ) => {
		console.log(props);
		const { attributes: { content }, setAttributes, className } = props;
		const onChangeContent = ( newContent ) => {
			setAttributes( { content: newContent } );
		};
		return (
			<RichText
				tagName="p"
				className={ className }
				onChange={ onChangeContent }
				value={ content }
			/>
		);
	},
	save: ( props ) => {
		console.log(props);
		return <RichText.Content tagName="p" value={ props.attributes.content } />;
	},
} );

/**
 * Initial attempt at Castos Player block
 * Just need to load the CSS ?
 * And then make it possible to edit the audio file, or choose an episode?
 */
registerBlockType('seriously-simple-podcasting/castos-player', {
	title: 'Castos Player',
	icon: 'audio',
	category: 'layout',
	edit: (props) => {
		return (
			<div className="ssp-player ssp-player-large" data-player-instance-number={1} data-player-waveform-colour="#fff" data-player-waveform-progress-colour="#00d4f7" data-source-file="https://wphackercast.com/podcast-player/1143/wp-hackercast-episode-24-tammie-lister-the-future-of-digital-experiences-and-all-things-esoteric.mp3" id="ssp_player_id_1" style={{background: '#222222'}}>
				<div className="ssp-album-art-container">
					<div className="ssp-album-art" style={{background: 'url( https://wphackercast.com/wp-content/uploads/2017/11/WP-Hacker-Cast-300x300.png ) center center no-repeat', WebkitBackgroundSize: 'cover', backgroundSize: 'cover'}} />
				</div>
				<div style={{overflow: 'hidden'}}>
					<div className="ssp-player-inner" style={{overflow: 'hidden'}}>
						<div className="ssp-player-info">
							<div style={{width: '80%', float: 'left'}}>
								<h3 className="ssp-player-title episode-title">WP HackerCast – Episode 24 – Tammie Lister – The Future of Digital Experiences and All Things Esoteric</h3>
							</div>
							<div className="ssp-download-episode" style={{overflow: 'hidden', textAlign: 'right'}} />
							<div>&nbsp;</div>
							<div className="ssp-media-player">
								<div className="ssp-custom-player-controls">
									<div className="ssp-play-pause" id="ssp-play-pause">
										<span className="ssp-icon ssp-icon-play_icon">&nbsp;</span>
									</div>
									<div className="ssp-wave-form">
										<div className="ssp-inner">
											<div data-waveform-id="waveform_1" id="waveform1" className="ssp-wave"><wave style={{display: 'block', position: 'relative', userSelect: 'none', height: '8px', overflow: 'hidden'}}><wave style={{position: 'absolute', zIndex: 2, left: '0px', top: '0px', bottom: '0px', overflow: 'hidden', width: '0px', display: 'block', boxSizing: 'border-box', borderRight: '1px solid rgb(51, 51, 51)'}}><canvas style={{position: 'absolute', left: '0px', top: '0px', bottom: '0px', height: '100%', width: '702px'}} width={702} height={8} /></wave><canvas style={{position: 'absolute', zIndex: 1, left: '0px', top: '0px', bottom: '0px', height: '100%', width: '702px'}} width={702} height={8} /></wave></div>
										</div>
									</div>
									<div className="ssp-time-volume">
										<div className="ssp-duration">
											<span id="sspPlayedDuration">00:00</span> / <span id="sspTotalDuration">00:59:37</span>
										</div>
										<div className="ssp-volume">
											<div className="ssp-back-thirty-container">
												<div className="ssp-back-thirty-control" id="ssp-back-thirty">
													<i className="ssp-icon icon-replay">&nbsp;</i>
												</div>
											</div>
											<div className="ssp-playback-speed-label-container">
												<div className="ssp-playback-speed-label-wrapper">
													<span data-playback-speed-id="ssp_playback_speed_1" id="ssp_playback_speed1" data-ssp-playback-rate={1}>1X</span>
												</div>
											</div>
											<div className="ssp-download-container">
												<div className="ssp-download-control">
													<a className="ssp-episode-download" href="https://wphackercast.com/podcast-download/1143/wp-hackercast-episode-24-tammie-lister-the-future-of-digital-experiences-and-all-things-esoteric.mp3?ref=download" target="_blank"><i className="ssp-icon icon-cloud-download">&nbsp;</i></a>
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
	},
	save: (props) => {
		return (
			<div className="ssp-player ssp-player-large" data-player-instance-number={1} data-player-waveform-colour="#fff" data-player-waveform-progress-colour="#00d4f7" data-source-file="https://wphackercast.com/podcast-player/1143/wp-hackercast-episode-24-tammie-lister-the-future-of-digital-experiences-and-all-things-esoteric.mp3" id="ssp_player_id_1" style={{background: '#222222'}}>
				<div className="ssp-album-art-container">
					<div className="ssp-album-art" style={{background: 'url( https://wphackercast.com/wp-content/uploads/2017/11/WP-Hacker-Cast-300x300.png ) center center no-repeat', WebkitBackgroundSize: 'cover', backgroundSize: 'cover'}} />
				</div>
				<div style={{overflow: 'hidden'}}>
					<div className="ssp-player-inner" style={{overflow: 'hidden'}}>
						<div className="ssp-player-info">
							<div style={{width: '80%', float: 'left'}}>
								<h3 className="ssp-player-title episode-title">WP HackerCast – Episode 24 – Tammie Lister – The Future of Digital Experiences and All Things Esoteric</h3>
							</div>
							<div className="ssp-download-episode" style={{overflow: 'hidden', textAlign: 'right'}} />
							<div>&nbsp;</div>
							<div className="ssp-media-player">
								<div className="ssp-custom-player-controls">
									<div className="ssp-play-pause" id="ssp-play-pause">
										<span className="ssp-icon ssp-icon-play_icon">&nbsp;</span>
									</div>
									<div className="ssp-wave-form">
										<div className="ssp-inner">
											<div data-waveform-id="waveform_1" id="waveform1" className="ssp-wave"><wave style={{display: 'block', position: 'relative', userSelect: 'none', height: '8px', overflow: 'hidden'}}><wave style={{position: 'absolute', zIndex: 2, left: '0px', top: '0px', bottom: '0px', overflow: 'hidden', width: '0px', display: 'block', boxSizing: 'border-box', borderRight: '1px solid rgb(51, 51, 51)'}}><canvas style={{position: 'absolute', left: '0px', top: '0px', bottom: '0px', height: '100%', width: '702px'}} width={702} height={8} /></wave><canvas style={{position: 'absolute', zIndex: 1, left: '0px', top: '0px', bottom: '0px', height: '100%', width: '702px'}} width={702} height={8} /></wave></div>
										</div>
									</div>
									<div className="ssp-time-volume">
										<div className="ssp-duration">
											<span id="sspPlayedDuration">00:00</span> / <span id="sspTotalDuration">00:59:37</span>
										</div>
										<div className="ssp-volume">
											<div className="ssp-back-thirty-container">
												<div className="ssp-back-thirty-control" id="ssp-back-thirty">
													<i className="ssp-icon icon-replay">&nbsp;</i>
												</div>
											</div>
											<div className="ssp-playback-speed-label-container">
												<div className="ssp-playback-speed-label-wrapper">
													<span data-playback-speed-id="ssp_playback_speed_1" id="ssp_playback_speed1" data-ssp-playback-rate={1}>1X</span>
												</div>
											</div>
											<div className="ssp-download-container">
												<div className="ssp-download-control">
													<a className="ssp-episode-download" href="https://wphackercast.com/podcast-download/1143/wp-hackercast-episode-24-tammie-lister-the-future-of-digital-experiences-and-all-things-esoteric.mp3?ref=download" target="_blank"><i className="ssp-icon icon-cloud-download">&nbsp;</i></a>
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
	},
});

/**
registerBlockType(
	'seriously-simple-podcasting/player-block', {
		title: 'Castos Player',
		icon: 'controls-volumeoff',
		category: 'layout',
		edit: () => <iframe className="castos-iframe-player" src="https://wp-hacker-cast.castos.com/player/150332" frameBorder="0" scrolling="no" width="100%" height="150"></iframe>,
		save: () => <iframe className="castos-iframe-player" src="https://wp-hacker-cast.castos.com/player/150332" frameBorder="0" scrolling="no" width="100%" height="150"></iframe>,
	}
);

registerBlockType(
	'seriously-simple-podcasting/podcast-list', {
		title: 'Podcast List',
		icon: 'smiley',
		category: 'layout',
		edit: () =>
			<div>Latest Podcasts</div>
		,
		save: () =>
			<div>Latest Podcasts</div>
		,
	}
);
*/
