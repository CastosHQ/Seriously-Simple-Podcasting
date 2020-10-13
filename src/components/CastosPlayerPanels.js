import {Component} from '@wordpress/element';

class CastosPlayerPanels extends Component {
	render() {
<<<<<<< HEAD
		const {className, episodeId, episodeTitle, episodeFileUrl, subscribeUrls, rssFeedUrl, episodeEmbedCode} = this.props;
		const {applePodcastsUrl, stitcherUrl, spotifyUrl, googlePlayUrl} = subscribeUrls;

=======
		const {className, episodeId, episodeTitle, episodeFileUrl} = this.props
>>>>>>> Completing Castos Player Block
		return (
			<div className={'player-panels player-panels-' + episodeId}>
				<div className={'subscribe player-panel subscribe-' + episodeId}>
					<div className={'close-btn close-btn-' + episodeId}>
						<span></span>
						<span></span>
					</div>
					<div className="panel__inner">
						<div className="subscribe-icons">

<<<<<<< HEAD
							<a href={applePodcastsUrl} target="_blank" className="apple-podcasts" title="Subscribe on Apple Podcasts">
=======
							<a href="" target="_blank" className="apple-podcasts" title="Subscribe on Apple Podcasts">
>>>>>>> Completing Castos Player Block
								<span></span>
								Apple Podcasts
							</a>

<<<<<<< HEAD
							<a href={stitcherUrl} target="_blank" className="sticher" title="Subscribe on Stitcher">
=======
							<a href="" target="_blank" className="sticher" title="Subscribe on Stitcher">
>>>>>>> Completing Castos Player Block
								<span></span>
								Stitcher
							</a>

<<<<<<< HEAD
							<a href={spotifyUrl} target="_blank" className="spotify" title="Subscribe on Spotify">
=======
							<a href="" target="_blank" className="spotify" title="Subscribe on Spotify">
>>>>>>> Completing Castos Player Block
								<span></span>
								Spotify
							</a>

<<<<<<< HEAD
							<a href={googlePlayUrl} target="_blank" className="google-play"
=======
							<a href="<?php echo $googlePlay['link'] ?>" target="_blank" className="google-play"
>>>>>>> Completing Castos Player Block
							   title="Subscribe on Google Play">
								<span></span>
								Google Play
							</a>

						</div>
						<div className="player-panel-row">
							<div className="title">
								RSS Feed
							</div>
							<div>
<<<<<<< HEAD
								<input readOnly value={rssFeedUrl} className={'input-rss input-rss-' + episodeId}/>
=======
								<input readOnly value="https://domain.com/podcast/feed" className={'input-rss input-rss-' + episodeId}/>
>>>>>>> Completing Castos Player Block
							</div>
							<button className={'copy-rss copy-rss-' + episodeId}></button>
						</div>
					</div>
				</div>
				<div className={'share share-' + episodeId + ' player-panel'}>
					<div className={'close-btn close-btn-' + episodeId}>
						<span></span>
						<span></span>
					</div>
					<div className="player-panel-row">
						<div className="title">
							Share
						</div>
						<div className="icons-holder">
							<a href={'https://www.facebook.com/sharer/sharer.php?u=' + episodeFileUrl + '&t=' + episodeTitle}
							   target="_blank" className="share-icon facebook" title="Share on Facebook">
								<span></span>
							</a>
							<a href={'https://twitter.com/intent/tweet?text=' + episodeFileUrl + '&url=' + episodeTitle}
							   target="_blank" className="share-icon twitter" title="Share on Twitter">
								<span></span>
							</a>
							<a href={episodeFileUrl} target="_blank" className="share-icon download" title="Download"
							   download>
								<span></span>
							</a>
						</div>
					</div>
					<div className="player-panel-row">
						<div className="title">
							Link
						</div>
						<div>
							<input readOnly value={episodeFileUrl} className={'input-link input-link-' + episodeId}/>
						</div>
						<button className={'copy-link copy-link-' + episodeId}></button>
					</div>
					<div className="player-panel-row">
						<div className="title">
							Embed
						</div>
						<div style={{height: '10px'}}>
<<<<<<< HEAD
							<input readOnly value={episodeEmbedCode} className={'input-embed input-embed-' + episodeId}/>
=======
							<input readOnly value='embed code here' className={'input-embed input-embed-' + episodeId}/>
>>>>>>> Completing Castos Player Block
						</div>
						<button className={'copy-embed copy-embed-' + episodeId}></button>
					</div>
				</div>
			</div>
		)
	}
}

export default CastosPlayerPanels;
