import {Component} from '@wordpress/element';

import CastosPlayer from "./CastosPlayer";
import AudioPlayer from './AudioPlayer';

/**
 * Renders either the standard player or the html player, depending on the value in post.audio_player
 */
class Player extends Component {
	render() {
		const {className, post} = this.props;
		if (post.audio_player) {
			return (
				<AudioPlayer className={className} audioPlayer={post.audio_player}/>
			);
		} else {
			return (
				<CastosPlayer
					className={className}
					episodeImage={post.episode_player_image}
					episodeFileUrl={post.meta.audio_file}
					episodeTitle={post.title.rendered}
					episodeDuration={post.meta.duration}
					episodeDownloadUrl={post.download_link}
					episodeData={post.episode_data}
				/>
			);
		}
	}
}

export default Player;
