import { registerBlockType } from '@wordpress/blocks';

/**
 * Example 1
 * @type {{padding: string, backgroundColor: string, color: string}}
 */
const blockStyle = {
	backgroundColor: '#900',
	color: '#fff',
	padding: '20px',
};

registerBlockType( 'seriously-simple-podcasting/example-01-basic-esnext', {
	title: 'Example: Basic (esnext)',
	icon: 'universal-access-alt',
	category: 'layout',
	example: {},
	edit() {
		return <div style={ blockStyle }>Hello World, step 1 (from the editor).</div>;
	},
	save() {
		return <div style={ blockStyle }>Hello World, step 1 (from the frontend).</div>;
	},
} );

/**
 * Example 2

registerBlockType( 'seriously-simple-podcasting/example-02-stylesheets', {
	title: 'Example: Stylesheets',
	icon: 'universal-access-alt',
	category: 'layout',
	example: {},
	edit( { className } ) {
		return <p className={ className }>Hello World, step 2 (from the editor, in green).</p>;
	},
	save() {
		return <p>Hello World, step 2 (from the frontend, in red).</p>;
	},
} );

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
