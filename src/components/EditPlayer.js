const {__} = wp.i18n;
const {Component} = wp.element;
const {BlockControls} = wp.blockEditor;
const {Button, Toolbar} = wp.components;

const {apiFetch} = wp;

import EpisodeSelector from "./EpisodeSelector";
import AudioPlayer from "./AudioPlayer";

class EditPlayer extends Component {
	constructor({className}) {
		super(...arguments);
		this.episodeRef = React.createRef();
		let editing = true;
		if (this.props.attributes.audio_player){
			editing = false;
		}
		const episode = {
			audioPlayer: this.props.attributes.audio_player || "",
		}
		this.state = {
			className,
			editing: editing,
			episode: episode,
			episodes: []
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

		const {editing, episodes, className, episode} = this.state;

		const {setAttributes} = this.props;

		const switchToEditing = () => {
			this.setState({editing: true});
		};

		const activateEpisode = () => {
			const episodeId = this.episodeRef.current.value;
			const fetchAudioPlayer = 'ssp/v1/audio_player?ssp_podcast_id='+episodeId;
			apiFetch({path: fetchAudioPlayer}).then(response => {
				console.log(response);
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
