import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {BlockControls} from '@wordpress/block-editor';
import {ToolbarButton, ToolbarGroup} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

import EpisodeSelector from "./EpisodeSelector";
import ServerSideRender from '@wordpress/server-side-render';

class EditCastosHTMLPlayer extends Component {
	constructor({attributes, setAttributes, className}) {
		super(...arguments);
		this.episodeRef = React.createRef();
		let editing = true;
		if (attributes.episodeId){
			editing = false;
		}
		this.state = {
			editing: editing,
			className,
			episodes: [],
			episodeId: attributes.episodeId || "",
			setAttributes: setAttributes
		};
	}

	componentDidMount() {
		this._isMounted = true;
		let fetchPost = 'ssp/v1/episodes?per_page=100';
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
		const {editing, episodes, episodeId, className, setAttributes} = this.state;

		const switchToEditing = () => {
			this.setState({editing: true});
		};

		const activateEpisode = () => {
			let episodeId = this.episodeRef.current.value;
			let fetchPost = 'ssp/v1/episodes?include=' + episodeId;
			apiFetch({path: fetchPost}).then(() => {
				this.setState({
					key: episodeId,
					episodeId: episodeId,
					editing: false
				});
				setAttributes({
					episodeId: episodeId,
				});
			});
		};

		const controls = (
			<BlockControls key="controls">
				<ToolbarGroup>
					<ToolbarButton
						className="components-icon-button components-toolbar__control"
						label={__('Select Podcast', 'seriously-simple-podcasting')}
						onClick={switchToEditing}
						icon="edit"
					/>
				</ToolbarGroup>
			</BlockControls>
		);


		if (editing) {
			return (
				<EpisodeSelector
					className={className}
					episodeRef={this.episodeRef}
					episodeId={episodeId}
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
									  attributes={{episodeId:episodeId}}
					/>
				]
			);
		}
	}
}

export default EditCastosHTMLPlayer;
