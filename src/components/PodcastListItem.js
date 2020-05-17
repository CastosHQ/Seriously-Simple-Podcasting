import Interweave from 'interweave';

import Player from "./Player";
import PlayerMeta from "./PlayerMeta";

const {Component} = wp.element;

/**
 * Podcast List Item
 *
 * @todo dont render image section if there is no image
 *
 */
class EditPodcastListItem extends Component {
	render() {
		const {className, post} = this.props;
		return (
			<article className={className}>
				<header className="entry-header">
					<h2 className="entry-title">
						<a className="entry-title-link" rel="bookmark" href={post.link}>
							{post.title.rendered}
						</a>
					</h2>
				</header>
				<div className="entry-content">
					<a className="entry-image-link" href={post.link} aria-hidden="true" tabIndex="-1">
						<img src={post.episode_featured_image}/>
					</a>
					<p><Interweave content={post.excerpt.rendered}/></p>
					<Player className={className} post={post} />
					<PlayerMeta className={className} title={post.title.rendered} download={post.download_link} duration={post.meta.duration}/>
				</div>
			</article>
		);
	}
}

export default EditPodcastListItem;
