import Interweave from 'interweave';

import CastosPlayer from "./CastosPlayer";
import PlayerMeta from "./PlayerMeta";
import AudioPlayer from "./AudioPlayer";

const {Component} = wp.element;

/**
 * Podcast List Item
 *
 * @todo dont render image section if there is no image
 * @todo create Podcast Component, that passes the player as props
 *
 */
class EditPodcastListItem extends Component {
	render() {
		const {className, post} = this.props;
		if (post.audio_player){
			return (
				<article className={ className }>
					<header className="entry-header">
						<h2 className="entry-title">
							<a className="entry-title-link" rel="bookmark" href={ post.link }>
								{ post.title.rendered }
							</a>
						</h2>
					</header>
					<div className="entry-content">
						<a className="entry-image-link" href={ post.link } aria-hidden="true" tabIndex="-1">
							<img src={post.episode_featured_image} />
						</a>
						<p><Interweave content={post.excerpt.rendered} /></p>
						<AudioPlayer className={className} audioPlayer={post.audio_player}/>
						<PlayerMeta className={className} title={post.title.rendered} download={post.download_link} duration={post.meta.duration} />
					</div>
				</article>
			);
		}else {
			return (
				<article className={ className }>
					<header className="entry-header">
						<h2 className="entry-title">
							<a className="entry-title-link" rel="bookmark" href={ post.link }>
								{ post.title.rendered }
							</a>
						</h2>
					</header>
					<div className="entry-content">
						<a className="entry-image-link" href={ post.link } aria-hidden="true" tabIndex="-1">
							<img src={post.episode_featured_image} />
						</a>
						<p><Interweave content={post.excerpt.rendered} /></p>
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
				</article>
			);
		}
	}
}

export default EditPodcastListItem;
