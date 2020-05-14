import { decodeEntities } from '@wordpress/html-entities';
import CastosPlayer from "./CastosPlayer";
import CastosPlayerMeta from "./CastosPlayerMeta";

const {Component} = wp.element;

/**
 * Podcast List Item
 * @todo fix content.rendered rendering
 *
 */
class EditPodcastListItem extends Component {
	render() {
		const {className, post} = this.props;
		console.log(post.content.rendered);
		const content = decodeEntities(post.content.rendered);
		console.log(content);
		return (
			<div key={post.id}>
				<img src={post.episode_featured_image} />
				<a className={ className } href={ post.link }>{ post.title.rendered }</a>
				<div>{content}</div>
				<CastosPlayer
					className={className}
					episodeImage={post.episode_player_image}
					episodeFileUrl={post.meta.audio_file}
					episodeTitle={post.title.rendered}
					episodeDuration={post.meta.duration}
					episodeDownloadUrl={post.download_link}
				/>
				<CastosPlayerMeta />
			</div>
		);
	}
}

export default EditPodcastListItem;


class PostListItem extends Component {
	render() {
		const {className, post} = this.props;
		return (
			<div key={post.id}>
				<img src={post.episode_featured_image} />
				<a className={ className } href={ post.link }>{ post.title.rendered }</a>
				{post.content.rendered}
				<div dangerouslySetInnerHTML={{__html: post.content.rendered}} />
			</div>
		);
	}
}
