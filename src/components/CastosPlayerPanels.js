import {Component} from '@wordpress/element';

class CastosPlayerPanels extends Component {

	render() {
		const {className, episodeId, episodeTitle, episodeFileUrl, episodeData} = this.props;
		const {rssFeedUrl, subscribeUrls, embedCode} = episodeData;
		return (
			<div className={'player-panels player-panels-' + episodeId}>
				<div className={'subscribe player-panel subscribe-' + episodeId}>
					<div className={'close-btn close-btn-' + episodeId}>
						<span></span>
						<span></span>
					</div>
					<div className="panel__inner">
						<div className="subscribe-icons">

							<a href={subscribeUrls.apple_podcasts.url} target="_blank" className="apple-podcasts" title="Subscribe on Apple Podcasts" rel="noopener noreferrer">
								<span></span>
								Apple Podcasts
							</a>

							<a href={subscribeUrls.stitcher.url} target="_blank" className="sticher" title="Subscribe on Stitcher" rel="noopener noreferrer">
								<span></span>
								Stitcher
							</a>

							<a href={subscribeUrls.spotify.url} target="_blank" className="spotify" title="Subscribe on Spotify" rel="noopener noreferrer">
								<span></span>
								Spotify
							</a>

							<a href={subscribeUrls.google_podcasts.url} target="_blank" className="google-play" title="Subscribe on Google Play" rel="noopener noreferrer">
								<span></span>
								Google Play
							</a>

						</div>
						<div className="player-panel-row">
							<div className="title">
								RSS Feed
							</div>
							<div>
								<input readOnly value={rssFeedUrl} className={'input-rss input-rss-' + episodeId}/>
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
							<a href={'https://www.facebook.com/sharer/sharer.php?u=' + episodeFileUrl + '&t=' + episodeTitle} target="_blank" className="share-icon facebook" title="Share on Facebook" rel="noopener noreferrer">
								<span></span>
							</a>
							<a href={'https://twitter.com/intent/tweet?text=' + episodeFileUrl + '&url=' + episodeTitle} target="_blank" className="share-icon twitter" title="Share on Twitter" rel="noopener noreferrer">
								<span></span>
							</a>
							<a href={episodeFileUrl} target="_blank" className="share-icon download" title="Download" rel="noopener noreferrer">
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
							<input readOnly value={embedCode} className={'input-embed input-embed-' + episodeId}/>
						</div>
						<button className={'copy-embed copy-embed-' + episodeId}></button>
					</div>
				</div>
			</div>
		)
	}
}

export default CastosPlayerPanels;
