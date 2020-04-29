/**
 * WordPress dependencies
 */
const {__} = wp.i18n;
const {Component} = wp.element;
const {
	BlockControls,
	InspectorControls,
	MediaPlaceholder,
	RichText,
} = wp.blockEditor;
const {
	FormToggle,
	IconButton,
	PanelBody,
	PanelRow,
	SelectControl,
	TextControl,
	Toolbar,
} = wp.components;

const {apiFetch} = wp;

import CastosPlayer from "./CastosPlayer";

class EditPlayer extends Component {
	constructor({className}) {
		super(...arguments);
		this.episodeRef = React.createRef();
		this.state = {
			editing: !this.props.attributes.id,
			className,
			episodes: []
		};
	}

	render() {

		const {editing, episodes} = this.state;

		const { setAttributes, isSelected, attributes } = this.props;

		const populateEpisodes = () => {
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

		const switchToEditing = () => {
			this.setState({editing: true});
		};

		const activateEpisode = () => {
			const episodeId = this.episodeRef.current.value;
			let fetchPost = 'ssp/v1/episodes?include='+episodeId;
			apiFetch({path: fetchPost}).then(post => {
				const episode = {
					episodeImage: post[0].episode_featured_image,
					episodeFileUrl: post[0].meta.audio_file,
					episodeTitle: post[0].title.rendered,
					episodeDuration: post[0].meta.duration,
					episodeDownloadUrl: post[0].download_link,
				}
				this.setState({
					episode: episode,
					editing: false
				});
				setAttributes({
					id: episodeId,
					image: episode.episodeImage,
					file: episode.episodeFileUrl,
					title: episode.episodeTitle,
					duration: episode.episodeDuration,
					download: episode.episodeDownloadUrl
				});
			});
		};

		const controls = (
			<BlockControls key="controls">
				<Toolbar>
					<IconButton
						className="components-icon-button components-toolbar__control"
						label={__('Select Podcast', 'seriously-simple-podcasting')}
						onClick={switchToEditing}
						icon="edit"
					/>
				</Toolbar>
			</BlockControls>
		);

		if (editing) {
			if (episodes.length === 0) {
				populateEpisodes()
			}
			return (
				<div>
					Select podcast Episode
					<select ref={this.episodeRef}>
						{this.state.episodes.map((item, key) =>
							<option value={item.id}>{item.title}</option>
						)}
					</select>
					<button onClick={activateEpisode}>Go</button>
				</div>
			);
		} else {
			return [
				controls, (
					<CastosPlayer
						episodeImage={this.state.episode.episodeImage}
						episodeFileUrl={this.state.episode.episodeFileUrl}
						episodeTitle={this.state.episode.episodeTitle}
						episodeDuration={this.state.episode.episodeDuration}
						episodeDownloadUrl={this.state.episode.episodeDownloadUrl}
					/>
				)];
		}
	}
}

export default EditPlayer;
