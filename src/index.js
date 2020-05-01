import { registerBlockType } from '@wordpress/blocks';
import CastosPlayer from "./components/CastosPlayer";
import EditPlayer from './components/EditPlayer';
/**
 * Castos Player block
 * Fix the bug in the front end controller that needs to load the player styles when the block is rendered
 * Is the block able to trigger the $large_player_instance_number variable
 * Load the JS for the player so that things like playing is possible
 * Might be worth while to clean up the CastosPlayer component a bit
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
		},
		file: {
			type: 'string',
		},
		title: {
			type: 'string',
		},
		duration: {
			type: 'string',
		},
		download: {
			type: 'string',
		},
	},

	edit: EditPlayer,

	save: (props, className) => {
		const { id, image, file, title, duration, download } = props.attributes;
		return (
			<CastosPlayer
				className={className}
				episodeImage={image}
				episodeFileUrl={file}
				episodeTitle={title}
				episodeDuration={duration}
				episodeDownloadUrl={download}
			/>
		);
	},
});
