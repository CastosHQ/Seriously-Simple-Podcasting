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
		const episodeId = this.props.attributes.id || '';
		let editing = ! this.props.attributes.id;
		this.state = {
			className,
			editing: editing,
			episodeId: episodeId,
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

		const {editing, episodes, className, episodeId} = this.state;

		const {setAttributes} = this.props;

		const switchToEditing = () => {
			this.setState({editing: true});
		};

		const activateEpisode = () => {
			const episodeId = this.episodeRef.current.value;
			console.log(episodeId);
			this.setState({
				episodeId: episodeId,
				editing: false
			});
			setAttributes({
				id: episodeId,
			});
			console.log(this.state);
			console.log(this.props);
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
					<AudioPlayer className={className} episodeId={episodeId}/>
				)];
		}
	}
}

export default EditPlayer;
