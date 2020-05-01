import { registerBlockType } from '@wordpress/blocks';
import CastosPlayer from "./components/CastosPlayer";
import EditPlayer from './components/EditPlayer';
/**
 * Castos Player block
 * Revert back to the actual HTML and figure out the differences
 * Need to load the CSS for the player
 * And then check if it renders the correct player in the block editor
 * as well as on the front end
 * will probably need to load the css on the front end, when a block is in play?
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
				method={"save"}
				episodeImage={image}
				episodeFileUrl={file}
				episodeTitle={title}
				episodeDuration={duration}
				episodeDownloadUrl={download}
			/>
		);
	},
});
