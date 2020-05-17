import Interweave from 'interweave';

import Player from "./Player";
import PlayerMeta from "./PlayerMeta";

const {Component} = wp.element;

/**
 * Podcast List Item
 *
 */
class EditPodcastListItem extends Component {
	render() {
		const {className, post} = this.props;
		let imageLink = '';
		if (post.episode_featured_image){
			imageLink = <a className="podcast-image-link" href={post.link} aria-hidden="true" tabIndex="-1">
				<img src={post.episode_featured_image}/>
			</a>;
		}
		return (
			<article className={className}>
				<h2>
					<a className="entry-title-link" rel="bookmark" href={post.link}>
						{post.title.rendered}
					</a>
				</h2>
				<div className="podcast-content">
					{imageLink}
					<p><Interweave content={post.excerpt.rendered}/></p>
					<Player className={className} post={post}/>
					<PlayerMeta
						className={className}
						title={post.title.rendered}
						download={post.download_link}
						duration={post.meta.duration}
					/>
				</div>
			</article>
		);
	}
}

export default EditPodcastListItem;
