import CastosPlayer from "./CastosPlayer";

const {Component} = wp.element;

/**
 * Podcast List Item
 */
class EditPodcastListItem extends Component {
	render() {
		const {className, post} = this.props;
		return (
			<div key={post.id}>
				<img src={post.episode_featured_image} />
				<a className={ className } href={ post.link }>{ post.title.rendered }</a>
				<p>{post.content.rendered}</p>
				<CastosPlayer
					className={className}
					episodeImage={post.episode_player_image}
					episodeFileUrl={post.meta.audio_file}
					episodeTitle={post.title.rendered}
					episodeDuration={post.meta.duration}
					episodeDownloadUrl={post.download_link}
				/>
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
