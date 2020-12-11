import {Component} from '@wordpress/element';

import Interweave from 'interweave';
import classnames from "classnames";

import Player from "./Player";
import PlayerMeta from "./PlayerMeta";

/**
 * Podcast List Item
 */
class PodcastListItem extends Component {
	render() {
		const {className, post, attributes} = this.props;
		const imageClassNames = classnames(
			"podcast-image-link",
			{ 'hide-featured-image': !attributes.featuredImage },
		);
		const playerClassNames = classnames(
			className,
			"podcast-player",
			{ 'hide-player': !attributes.player },
		);
		const excerptClassNames = classnames(
			"podcast-excerpt",
			{ 'hide-excerpt': !attributes.excerpt },
		);
		const episodeFeaturedImage = post.episode_featured_image ? <img src={post.episode_featured_image}/> : '';
		return (
			<article className={className}>
				<h2>
					<a className="entry-title-link" rel="bookmark" href={post.link}>
						<Interweave content={post.title.rendered}/>
					</a>
				</h2>
				<div className="podcast-content">
					<a className={imageClassNames} href={post.link} aria-hidden="true" tabIndex="-1">
						{episodeFeaturedImage}
					</a>
					<Player className={playerClassNames} post={post}/>
					<PlayerMeta
						className={playerClassNames}
						title={post.title.rendered}
						download={post.download_link}
						duration={post.meta.duration}
					/>
					<div className={excerptClassNames}><Interweave content={post.excerpt.rendered}/></div>
				</div>
			</article>
		);
	}
}

export default PodcastListItem;
