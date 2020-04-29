import CastosPlayer from "./CastosPlayer";

/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;
const { Component } = wp.element;
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

const { apiFetch } = wp;

class EditPlayer extends Component {
	constructor( { className } ) {
		super( ...arguments );
		// edit component has its own src in the state so it can be edited
		// without setting the actual value outside of the edit UI
		this.state = {
			editing: ! this.props.attributes.id,
			className,
		};
		this.episodeRef = React.createRef();
	}
	render() {
		const episodes = [
			{id: 5, title: 'Lipsum'},
			{id: 28, title: 'Lipsum 2'},
		]

		const switchToEditing = () => {
			this.setState( { editing: true } );
		};

		const activateEpisode = () => {
			// get the episode id from the ref
			const episodeId = this.episodeRef.current.value;
			console.log(episodeId);
			this.setState( { editing: false } );
		};

		const controls = (
			<BlockControls key="controls">
				<Toolbar>
					<IconButton
						className="components-icon-button components-toolbar__control"
						label={ __( 'Select Podcast', 'seriously-simple-podcasting' ) }
						onClick={ switchToEditing }
						icon="edit"
					/>
				</Toolbar>
			</BlockControls>
		);
		if (this.state.editing){
			return (
				<div>
					Select podcast Episode
					<select>
						{episodes.map((item, key) =>
							<option ref={this.episodeRef} value={item.id}>{item.title}</option>
						)}
					</select>
					<button onClick={activateEpisode}>Go</button>
				</div>
			);
		} else {
			return [
				controls, (
					<CastosPlayer
						episodeImage="https://wphackercast.com/wp-content/uploads/2017/11/WP-Hacker-Cast-300x300.png"
						episodeFileUrl="https://wphackercast.com/podcast-player/1143/wp-hackercast-episode-24-tammie-lister-the-future-of-digital-experiences-and-all-things-esoteric.mp3"
						episodeTitle="WP HackerCast – Episode 24 – Tammie Lister – The Future of Digital Experiences and All Things Esoteric"
						episodeDuration="00:59:37"
						episodeDownloadUrl="https://wphackercast.com/podcast-download/1143/wp-hackercast-episode-24-tammie-lister-the-future-of-digital-experiences-and-all-things-esoteric.mp3?ref=download"
					/>
				)];
		}
	}
}

export default EditPlayer;
