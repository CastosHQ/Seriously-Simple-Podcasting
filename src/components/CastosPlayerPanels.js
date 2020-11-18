import {Component} from '@wordpress/element';

class CastosPlayerPanels extends Component {

	render() {
		const {className, episodeId, episodeTitle, episodeFileUrl, episodeData} = this.props;
		const {rssFeedUrl, subscribeUrls, embedCode} = episodeData;
		const subscribeButtons = [];
		const subscribeKeys = Object.keys(subscribeUrls);
		subscribeKeys.forEach((key, index) => {
			const url = subscribeUrls[key].url;
			if ("" !== url) {
				const className = subscribeUrls[key].key;
				const label = subscribeUrls[key].label;
				const title = "Subscribe on " + subscribeUrls[key].label;
				subscribeButtons.push(
					<a key={key} href={url} target="_blank" className={className} title={title} rel="noopener noreferrer">
						<span></span>
						{label}
					</a>
				);
			}
		});

		return (
			<div className={'player-panels player-panels-' + episodeId}>
				<div className={'subscribe player-panel subscribe-' + episodeId}>
					<div className={'close-btn close-btn-' + episodeId}>
						<span></span>
						<span></span>
					</div>
					<div className="panel__inner">
						<div className="subscribe-icons">
							{subscribeButtons}
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
