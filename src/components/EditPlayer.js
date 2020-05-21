import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {BlockControls} from '@wordpress/block-editor';
import {Button, Toolbar} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

import EpisodeSelector from "./EpisodeSelector";
import AudioPlayer from "./AudioPlayer";

class EditPlayer extends Component {
	constructor({attributes, setAttributes, className}) {
		super(...arguments);
		this.episodeRef = React.createRef();
		let editing = true;
		if (attributes.audio_player){
			editing = false;
		}
		const episode = {
			audioPlayer: attributes.audio_player || "",
		}
		this.state = {
			className,
			editing: editing,
			episode: episode,
			episodes: [],
			setAttributes: setAttributes
		}
	}

	componentDidMount() {
		let fetchPost = 'ssp/v1/episodes';
		apiFetch({path: fetchPost}).then(posts => {
			let episodes = []
			Object.keys(posts).map(function (key) {
				let episode = {
					id: posts[key].id,
					title: posts[key].title.rendered
				}
				episodes.push(episode);
			});
			this.setState({
				episodes: episodes,
			});
		});
	}

	render() {

		const {editing, episodes, episode, className, setAttributes} = this.state;

		const switchToEditing = () => {
			this.setState({editing: true});
		};

		const activateEpisode = () => {
			const episodeId = this.episodeRef.current.value;
			const fetchAudioPlayer = 'ssp/v1/audio_player?ssp_podcast_id='+episodeId;
			apiFetch({path: fetchAudioPlayer}).then(response => {
				const episode = {
					episodeId: episodeId,
					audioPlayer: response.audio_player
				}
				this.setState({
					episode: episode,
					editing: false
				});
				setAttributes({
					id: episodeId,
					audio_player: episode.audioPlayer
				});
			});
		};

		const controls = (
			<BlockControls key="controls">
				<Toolbar>
					<Button
						className="components-icon-button components-toolbar__control"
						label={__('Select Podcast', 'seriously-simple-podcasting')}
						onClick={switchToEditing}
						icon="edit"
					/>
				</Toolbar>
			</BlockControls>
		);

		if (editing) {
			return (
				<EpisodeSelector
					className={className}
					episodeRef={this.episodeRef}
					episodes={episodes}
					activateEpisode={activateEpisode}
				/>
			);
		} else {
			return [
				controls, (
					<AudioPlayer className={className} audioPlayer={episode.audioPlayer}/>
				)];
		}
	}
}

export default EditPlayer;
