/**
 * WordPress dependencies
 */
import {Component} from '@wordpress/element';

const {apiFetch} = wp;

class AudioPlayer extends Component {
	constructor({className}) {
		super(...arguments);
		const episodeId = this.props.episodeId || '';
		this.state = {
			episodeId: episodeId,
			audioPlayer: ''
		}
	}

	componentDidMount() {
		const fetchAudioPlayer = 'ssp/v1/audio_player?ssp_podcast_id='+this.state.episodeId;
		apiFetch({path: fetchAudioPlayer}).then(response => {
			console.log(response);
			this.setState({
				audioPlayer: response.audio_player,
			});
		});
	}

	render() {
		return (
			<div dangerouslySetInnerHTML={{__html: this.state.audioPlayer}} />
		);
	}
}

export default AudioPlayer;
