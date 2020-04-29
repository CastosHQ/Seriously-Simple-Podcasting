import { registerBlockType } from '@wordpress/blocks';
import { RichText } from '@wordpress/block-editor';
import CastosPlayer from "./components/CastosPlayer";
import EpisodeSelector from "./components/EpisodeSelector";
import EditPlayer from './components/EditPlayer';
/**
 * Castos Player block
 * Need to load the CSS for the player
 */

registerBlockType('seriously-simple-podcasting/castos-player', {

	title: 'Castos Player',

	icon: 'controls-volumeon',

	category: 'layout',

	supports: {
		multiple: false,
	},

	attributes: {
		id: {
			type: 'number',
		},
		image: {
			type: 'string',
			source: 'attribute',
			attribute: 'image',
		},
		file: {
			type: 'string',
			source: 'attribute',
			attribute: 'file',
		},
		title: {
			type: 'string',
			source: 'attribute',
			attribute: 'title',
		},
		duration: {
			type: 'string',
			source: 'attribute',
			attribute: 'duration',
		},
		download: {
			type: 'string',
			source: 'attribute',
			attribute: 'download',
		},
	},

	edit: EditPlayer,

	save: props => {
		const { id, image, file, title, duration, download } = props.attributes;
		return (
			<CastosPlayer
				episodeImage={image}
				episodeFileUrl={file}
				episodeTitle={title}
				episodeDuration={duration}
				episodeDownloadUrl={download}
			/>
		);
	},

});

//https://developer.wordpress.org/block-editor/tutorials/block-tutorial/introducing-attributes-and-editable-fields/

/*registerBlockType( 'seriously-simple-podcasting/example-03-editable-esnext', {
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
} );*/

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
