import { decodeEntities } from '@wordpress/html-entities';
import CastosPlayer from "./CastosPlayer";
import PlayerMeta from "./PlayerMeta";

const {Component} = wp.element;

/**
 * Podcast List Item
 * @todo fix content.rendered rendering
 *
 */
class EditPodcastListItem extends Component {
	render() {
		const {className, post} = this.props;
		return (
			<div key={post.id}>
				<img src={post.episode_featured_image} />
				<a className={ className } href={ post.link }>{ post.title.rendered }</a>
				<div>{post.content.rendered}</div>
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

export default EditPodcastListItem;
