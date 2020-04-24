import { registerBlockType } from '@wordpress/blocks';
import { RichText } from '@wordpress/block-editor';

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
