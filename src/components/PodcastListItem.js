import Interweave from 'interweave';

import CastosPlayer from "./CastosPlayer";
import PlayerMeta from "./PlayerMeta";
import AudioPlayer from "./AudioPlayer";

const {Component} = wp.element;

/**
 * Podcast List Item
 *
 */
class EditPodcastListItem extends Component {
	render() {
		const {className, post} = this.props;
		if (post.audio_player){
			return (
				<div key={post.id}>
					<img src={post.episode_featured_image} />
					<a className={ className } href={ post.link }>{ post.title.rendered }</a>
					<Interweave content={post.content.rendered} />
					<AudioPlayer className={className} audioPlayer={post.audio_player}/>
					<PlayerMeta className={className} title={post.title.rendered} download={post.download_link} duration={post.meta.duration} />
				</div>
			);
		}else {
			return (
				<div key={post.id}>
					<img src={post.episode_featured_image} />
					<a className={ className } href={ post.link }>{ post.title.rendered }</a>
					<Interweave content={post.content.rendered} />
					<CastosPlayer
						className={className}
						episodeImage={post.episode_player_image}
						episodeFileUrl={post.meta.audio_file}
						episodeTitle={post.title.rendered}
						episodeDuration={post.meta.duration}
						episodeDownloadUrl={post.download_link}
					/>
					<PlayerMeta className={className} title={post.title.rendered} download={post.download_link} duration={post.meta.duration} />
				</div>
			);
		}
	}
}

export default EditPodcastListItem;
