/**
 * WordPress dependencies
 */
import {__} from '@wordpress/i18n';
import {registerBlockType} from '@wordpress/blocks';
import { registerPlugin } from '@wordpress/plugins';

import EditPlayer from './components/EditPlayer';
import AudioPlayer from "./components/AudioPlayer";
import CastosPlayer from "./components/CastosPlayer";
import EditCastosPlayer from './components/EditCastosPlayer';
import EditCastosHTMLPlayer from './components/EditCastosHTMLPlayer';
import EditPodcastList from "./components/EditPodcastList";
import EditSSPPodcasts from "./components/EditSSPPodcasts";
import PostPublishPanel from "./components/PostPublishPanel";
import './settingsExtender.js';
import EditPlaylistPlayer from "./components/EditPlaylistPlayer";


/**
 * Standard Audio Player Block
 */
registerBlockType('seriously-simple-podcasting/audio-player', {
	title: __('Audio Player', 'seriously-simple-podcasting'),
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
 * @deprecated Use 'seriously-simple-podcasting/castos-html-player' instead
 */
registerBlockType('seriously-simple-podcasting/castos-player', {
	title: __('Castos Player (OLD)', 'seriously-simple-podcasting'),
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
		episode_data: {
			type: 'object',
		},
	},
	edit: EditCastosPlayer,
	save: (props, className) => {
		const { id, image, file, title, duration, download, episode_data } = props.attributes;

		if( episode_data ){
			return (
				<CastosPlayer
					className={className}
					episodeId={id}
					episodeImage={image}
					episodeFileUrl={file}
					episodeTitle={title}
					episodeDuration={duration}
					episodeDownloadUrl={download}
					episodeData={episode_data}
				/>
			);
		} else {
			return ('');
		}
	},
});


/**
 * Castos HTML Player block
 */
registerBlockType('seriously-simple-podcasting/castos-html-player', {
	title: __('Castos Player', 'seriously-simple-podcasting'),
	icon: 'controls-volumeon',
	category: 'layout',
	attributes: {
		episodeId: {
			type: 'string'
		},
	},
	edit: EditCastosHTMLPlayer,
	save: () => {
		return null;
	},
});

/**
 * Podcast list block
 */
registerBlockType('seriously-simple-podcasting/podcast-list', {
	title: __('Episode List', 'seriously-simple-podcasting'),
	icon: 'megaphone',
	category: 'widgets',
	supports: {
		multiple: false,
	},
	edit: EditPodcastList
});

/**
 * Playlist player block
 */
registerBlockType('seriously-simple-podcasting/playlist-player', {
	title: __('Playlist Player', 'seriously-simple-podcasting'),
	icon: 'controls-volumeon',
	category: 'widgets',
	edit: EditPlaylistPlayer
});

/**
 * SSP Podcasts block
 */
registerBlockType('seriously-simple-podcasting/ssp-podcasts', {
	title: __('SSP Podcasts', 'seriously-simple-podcasting'),
	icon: 'megaphone',
	category: 'widgets',
	supports: {
		multiple: false,
	},
	edit: EditSSPPodcasts
});

registerPlugin( 'post-publish-panel', {
	render: PostPublishPanel
} );

import EpisodeSyncStatus from './components/EpisodeSyncStatus';
registerPlugin('episode-sync-status', { render: EpisodeSyncStatus });

import EpisodeMetaSidebar from './components/EpisodeMetaSidebar';
registerPlugin( 'ssp-episode-meta-sidebar', { render: EpisodeMetaSidebar } );

import SSPSidebarPanel from './components/SSPSidebarPanel';
registerPlugin( 'ssp-sidebar-panel', { render: SSPSidebarPanel } );
