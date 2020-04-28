import { registerBlockType } from '@wordpress/blocks';
import { RichText } from '@wordpress/block-editor';
import CastosPlayer from "./components/CastosPlayer";
import EpisodeSelector from "./components/EpisodeSelector";
import EditPlayer from './components/EditPlayer';
/**
 * Initial attempt at Castos Player block
 * Just need to load the CSS ?
 * And then make it possible to edit the audio file, or choose an episode?
 */
registerBlockType('seriously-simple-podcasting/castos-player', {

	title: 'Castos Player',

	icon: 'controls-volumeon',

	category: 'layout',

	attributes: {
		id: {
			type: 'number',
		},
	},

	edit: EditPlayer,

	save: () => {
		return (
			<CastosPlayer
				episodeImage="https://wphackercast.com/wp-content/uploads/2017/11/WP-Hacker-Cast-300x300.png"
				episodeFileUrl="https://wphackercast.com/podcast-player/1143/wp-hackercast-episode-24-tammie-lister-the-future-of-digital-experiences-and-all-things-esoteric.mp3"
				episodeTitle="WP HackerCast – Episode 24 – Tammie Lister – The Future of Digital Experiences and All Things Esoteric"
				episodeDuration="00:59:37"
				episodeDownloadUrl="https://wphackercast.com/podcast-download/1143/wp-hackercast-episode-24-tammie-lister-the-future-of-digital-experiences-and-all-things-esoteric.mp3?ref=download"
			/>
		);
	},

});

//https://developer.wordpress.org/block-editor/tutorials/block-tutorial/introducing-attributes-and-editable-fields/

registerBlockType( 'seriously-simple-podcasting/example-03-editable-esnext', {
	title: 'Example: Editable (esnext)',
	icon: 'universal-access-alt',
	category: 'layout',
	attributes: {
		content: {
			type: 'array',
			source: 'children',
			selector: 'p',
		},
	},
	example: {
		attributes: {
			content: 'Hello World',
		},
	},
	edit: ( props ) => {
		console.log(props);
		const { attributes: { content }, setAttributes, className } = props;
		const onChangeContent = ( newContent ) => {
			setAttributes( { content: newContent } );
		};
		return (
			<RichText
				tagName="p"
				className={ className }
				onChange={ onChangeContent }
				value={ content }
			/>
		);
	},
	save: ( props ) => {
		console.log(props);
		return <RichText.Content tagName="p" value={ props.attributes.content } />;
	},
} );

/**
registerBlockType(
	'seriously-simple-podcasting/player-block', {
		title: 'Castos Player',
		icon: 'controls-volumeoff',
		category: 'layout',
		edit: () => <iframe className="castos-iframe-player" src="https://wp-hacker-cast.castos.com/player/150332" frameBorder="0" scrolling="no" width="100%" height="150"></iframe>,
		save: () => <iframe className="castos-iframe-player" src="https://wp-hacker-cast.castos.com/player/150332" frameBorder="0" scrolling="no" width="100%" height="150"></iframe>,
	}
);

registerBlockType(
	'seriously-simple-podcasting/podcast-list', {
		title: 'Podcast List',
		icon: 'smiley',
		category: 'layout',
		edit: () =>
			<div>Latest Podcasts</div>
		,
		save: () =>
			<div>Latest Podcasts</div>
		,
	}
);
*/
