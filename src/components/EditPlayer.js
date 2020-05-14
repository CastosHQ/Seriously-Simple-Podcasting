/**
 * WordPress dependencies
 */

const {__} = wp.i18n;
const {Component} = wp.element;
const {BlockControls} = wp.blockEditor;
const {Button, Toolbar} = wp.components;

const {apiFetch} = wp;

import AudioPlayer from "./AudioPlayer";

class EditPlayer extends Component {
	constructor({className}) {
		super(...arguments);
		this.episodeRef = React.createRef();
		let editing = true;
		if (this.props.attributes.id){
			editing = false;
		}
		this.state = {
			className,
			editing: editing,
			episode: [],
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

	activateEpisode() {

	}

	render() {

		const {editing, episodes, className, episode} = this.state;

		const {setAttributes} = this.props;

		const switchToEditing = () => {
			this.setState({editing: true});
		};

		const activateEpisode = () => {
			const episodeId = this.episodeRef.current.value;
			let fetchPost = 'ssp/v1/episodes?include='+episodeId;
			apiFetch({path: fetchPost}).then(post => {
				const episode = {
					episodeId: episodeId,
					episodeTitle: post[0].title.rendered,
				}
				this.setState({
					episode: episode,
					editing: false
				});
				setAttributes({
					id: episodeId,
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
				/* @todo this could be moved to it's own component */
				<div className={className}>
					Select podcast Episode
					<select ref={this.episodeRef}>
						{episodes.map((item, key) =>
							<option key={item.id} value={item.id}>{item.title}</option>
						)}
					</select>
					<button onClick={activateEpisode}>Go</button>
				</div>
			);
		} else {
			return [
				controls, (
					<AudioPlayer className={className} episode={episode}/>
				)];
		}
	}
}

export default EditPlayer;
