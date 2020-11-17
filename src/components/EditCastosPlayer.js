import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {BlockControls} from '@wordpress/block-editor';
import {Button, Toolbar} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

import EpisodeSelector from "./EpisodeSelector";
import CastosPlayer from "./CastosPlayer";

class EditCastosPlayer extends Component {
	constructor({attributes, setAttributes, className}) {
		super(...arguments);
		this.episodeRef = React.createRef();
		const episode = {
			episodeImage: attributes.image || "",
			episodeFileUrl: attributes.file || "",
			episodeTitle: attributes.title || "",
			episodeDuration: attributes.duration || "",
			episodeDownloadUrl: attributes.download || "",
			episodeData: attributes.episode_data || "",
		}
		let editing = true;
		if (attributes.title){
			editing = false;
		}
		this.state = {
			editing: editing,
			className,
			episodes: [],
			episode: episode,
			setAttributes: setAttributes
		};
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
			let fetchPost = 'ssp/v1/episodes?include=' + episodeId;
			apiFetch({path: fetchPost}).then(post => {
				let fetchEpisodeData = 'ssp/v1/player_data?ssp_episode_id=' + episodeId;
				apiFetch({path: fetchEpisodeData}).then(episode_data => {
					const episode = {
						episodeId: episodeId,
						episodeImage: post[0].episode_player_image,
						episodeFileUrl: post[0].meta.audio_file,
						episodeTitle: post[0].title.rendered,
						episodeDuration: post[0].meta.duration,
						episodeDownloadUrl: post[0].download_link,
						episodeData: episode_data,
					}
					this.setState({
						key: episodeId,
						episode: episode,
						editing: false
					});
					setAttributes({
						id: episodeId,
						image: episode.episodeImage,
						file: episode.episodeFileUrl,
						title: episode.episodeTitle,
						duration: episode.episodeDuration,
						download: episode.episodeDownloadUrl,
						episode_data: episode.episodeData
					});
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
					<CastosPlayer
						key='castos-player'
						className={this.state.className}
						episodeId={episode.episodeId}
						episodeImage={episode.episodeImage}
						episodeTitle={episode.episodeTitle}
						episodeFileUrl={episode.episodeFileUrl}
						episodeDuration={episode.episodeDuration}
						episodeData={episode.episodeData}
					/>
				)];
		}
	}
}

export default EditCastosPlayer;
