import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {BlockControls} from '@wordpress/block-editor';
import {Button, Toolbar} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

import EpisodeSelector from "./EpisodeSelector";
import CastosPlayer from "./CastosPlayer";
import ServerSideRender from '@wordpress/server-side-render';

class EditCastosHTMLPlayer extends Component {
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
		this._isMounted = true;
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

	componentWillUnmount() {
		// fix Warning: Can't perform a React state update on an unmounted component
		this._isMounted = false;
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
				const episode = {
					episodeId: episodeId
				}
				this.setState({
					key: episodeId,
					episode: episode,
					editing: false
				});
				setAttributes({
					episodeId: episodeId,
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
			return (
				[
					controls,
					<ServerSideRender className={className}
									  key='castos-player'
									  block="seriously-simple-podcasting/castos-html-player"
									  attributes={{episodeId:episode.episodeId}}
					/>
				]
			);
		}
	}
}

export default EditCastosHTMLPlayer;
