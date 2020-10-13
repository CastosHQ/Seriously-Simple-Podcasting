import {Component} from '@wordpress/element';

class CastosPlayerPanels extends Component {
	render() {
		const {className, episodeId, episodeTitle, episodeFileUrl} = this.props
		return (
			<div className={'player-panels player-panels-' + episodeId}>
				<div className={'subscribe player-panel subscribe-' + episodeId}>
					<div className={'close-btn close-btn-' + episodeId}>
						<span></span>
						<span></span>
					</div>
					<div className="panel__inner">
						<div className="subscribe-icons">

							<a href="" target="_blank" className="apple-podcasts" title="Subscribe on Apple Podcasts">
								<span></span>
								Apple Podcasts
							</a>

							<a href="" target="_blank" className="sticher" title="Subscribe on Stitcher">
								<span></span>
								Stitcher
							</a>

							<a href="" target="_blank" className="spotify" title="Subscribe on Spotify">
								<span></span>
								Spotify
							</a>

							<a href="<?php echo $googlePlay['link'] ?>" target="_blank" className="google-play"
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
								<input readOnly value="https://domain.com/podcast/feed" className={'input-rss input-rss-' + episodeId}/>
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
							<input readOnly value='embed code here' className={'input-embed input-embed-' + episodeId}/>
						</div>
						<button className={'copy-embed copy-embed-' + episodeId}></button>
					</div>
				</div>
			</div>
		)
	}
}

export default CastosPlayerPanels;
