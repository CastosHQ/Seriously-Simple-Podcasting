import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'myguten/test-block', {
	title: 'Basic Example',
	icon: 'smiley',
	category: 'layout',
	edit: () => <div>Hola, mundo!</div>,
save: () => <div>Hola, mundo!</div>,
} );
