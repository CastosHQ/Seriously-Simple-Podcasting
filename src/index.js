import { registerBlockType } from '@wordpress/blocks';
import EditPlayer from './components/EditPlayer';
import AudioPlayer from "./components/AudioPlayer";
import CastosPlayer from "./components/CastosPlayer";
import EditCastosPlayer from './components/EditCastosPlayer';
import EditPodcastList from "./components/EditPodcastList";

/**
 * Standard Audio Player Block
 */
registerBlockType('seriously-simple-podcasting/audio-player', {
	title: 'Audio Player',
	icon: 'controls-volumeon',
	category: 'layout',
	supports: {
		multiple: false,
	},
	attributes: {
		id: {
			type: 'string',
		},
		audio_player: {
			type: 'string',
			source: 'html',
			selector: 'span',
		}
	},
	edit: EditPlayer,
	save: (props, className) => {
		const { id, audio_player } = props.attributes;
		return (
			<AudioPlayer className={className} audioPlayer={audio_player}/>
		);
	},
});

/**
 * Castos Player block
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
			type: 'string',
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
	edit: EditCastosPlayer,
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

/**
 * Podcast list block
 */
registerBlockType( 'seriously-simple-podcasting/podcast-list', {
	title: 'Podcast List',
	icon: 'megaphone',
	category: 'widgets',
	edit: EditPodcastList,
});
