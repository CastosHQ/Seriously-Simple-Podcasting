import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {BlockControls} from '@wordpress/block-editor';
import {Button, Toolbar} from '@wordpress/components';
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
			setAttributes: setAttributes,
			episodeId: attributes.episodeId
		};
	}

	componentDidMount() {
		this._isMounted = true;
		let fetchPost = 'ssp/v1/episodes?per_page=100&get_additional_options=true';
		apiFetch({path: fetchPost}).then(posts => {
			let episodes = [];

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
		const {editing, episodes, episodeId, className, setAttributes} = this.state;
		const switchToEditing = () => {
			this.setState({editing: true});
		};

		const activateEpisode = () => {

			let newEpisodeId = this.episodeRef.current.value;

			this.setState({
				episodeId: newEpisodeId,
				editing: false
			});

			setAttributes({
				episodeId: newEpisodeId,
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
									  attributes={{episodeId:episodeId}}
					/>
				]
			);
		}
	}
}

export default EditCastosHTMLPlayer;
