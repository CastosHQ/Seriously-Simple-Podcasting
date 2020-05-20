import Interweave from 'interweave';

import Player from "./Player";
import PlayerMeta from "./PlayerMeta";
import classnames from "classnames";

const {Component} = wp.element;

/**
 * Podcast List Item
 */
class EditPodcastListItem extends Component {
	render() {
		const {className, post, attributes} = this.props;
		const imageClassNames = classnames(
			"podcast-image-link",
			{ 'hide-featured-image': !attributes.featuredImage },
		);
		const excerptClassNames = classnames(
			"podcast-excerpt",
			{ 'hide-excerpt': !attributes.excerpt },
		);
		const playerClassNames = classnames(
			className,
			"podcast-player",
			{ 'hide-player': !attributes.player },
		);
		return (
			<article className={className}>
				<h2>
					<a className="entry-title-link" rel="bookmark" href={post.link}>
						{post.title.rendered}
					</a>
				</h2>
				<div className="podcast-content">
					<a className={imageClassNames} href={post.link} aria-hidden="true" tabIndex="-1">
						<img src={post.episode_featured_image}/>
					</a>
					<p className={excerptClassNames}><Interweave content={post.excerpt.rendered}/></p>
					<Player className={playerClassNames} post={post}/>
					<PlayerMeta
						className={playerClassNames}
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
