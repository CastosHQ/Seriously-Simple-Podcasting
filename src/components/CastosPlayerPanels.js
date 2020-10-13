import CastosPlayer from "./CastosPlayer";

class CastosPlayerPanels {
	render() {
		const {className, episodeId} = this.props;
		return (
			<div className={'player-panels player-panels-'+episodeId}>
				<div className="subscribe player-panel subscribe-<?php echo $episode_id ?>">
					<div className="close-btn close-btn-<?php echo $episode_id ?>">
						<span></span>
						<span></span>
					</div>
					<div className="panel__inner">
						<div className="subscribe-icons">
							<?php if($itunes['link']): ?>
							<a href="<?php echo $itunes['link'] ?>"
							   target="_blank" className="apple-podcasts" title="Subscribe on Apple Podcasts">
								<span></span>
								Apple Podcasts
							</a>
							<?php endif; ?>
							<?php if($stitcher['link']): ?>
							<a href="<?php echo $stitcher['link'] ?>" target="_blank" className="sticher"
							   title="Subscribe on Stitcher">
								<span></span>
								Stitcher
							</a>
							<?php endif; ?>
							<?php if($spotify['link']): ?>
							<a href="<?php echo $spotify['link'] ?>" target="_blank"
							   className="spotify"
							   title="Subscribe on Spotify">
								<span></span>
								Spotify
							</a>
							<?php endif; ?>
							<?php if($googlePlay['link']): ?>
							<a href="<?php echo $googlePlay['link'] ?>" target="_blank" className="google-play"
							   title="Subscribe on Google Play">
								<span></span>
								Google Play
							</a>
							<?php endif; ?>
						</div>
						<div className="player-panel-row">
							<div className="title">
								RSS Feed
							</div>
							<div>
								<input value="<?php echo $feedUrl ?>"
									   className="input-rss input-rss-<?php echo $episode_id ?>"/>
							</div>
							<button className="copy-rss copy-rss-<?php echo $episode_id ?>"></button>
						</div>
					</div>
				</div>
				<div className="share share-<?php echo $episode_id ?> player-panel">
					<div className="close-btn close-btn-<?php echo $episode_id ?>">
						<span></span>
						<span></span>
					</div>
					<div className="player-panel-row">
						<div className="title">
							Share
						</div>
						<div className="icons-holder">
							<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $audioFile; ?>&t=<?php echo $episode->post_title; ?>"
							   target="_blank" className="share-icon facebook" title="Share on Facebook">
								<span></span>
							</a>
							<a href="https://twitter.com/intent/tweet?text=<?php echo $audioFile; ?>&url=<?php echo $episode->post_title; ?>"
							   target="_blank" className="share-icon twitter" title="Share on Twitter">
								<span></span>
							</a>
							<a href="<?php echo $audioFile ?>"
							   target="_blank" className="share-icon download" title="Download" download>
								<span></span>
							</a>
						</div>
					</div>
					<div className="player-panel-row">
						<div className="title">
							Link
						</div>
						<div>
							<input value="<?php echo $audioFile ?>"
								   className="input-link input-link-<?php echo $episode_id ?>"/>
						</div>
						<button className="copy-link copy-link-<?php echo $episode_id ?>"></button>
					</div>
					<div className="player-panel-row">
						<div className="title">
							Embed
						</div>
						<div style="height: 10px;">
							<input value='<?php echo $embed_code ?>'
								   className="input-embed input-embed-<?php echo $episode_id ?>"/>
						</div>
						<button className="copy-embed copy-embed-<?php echo $episode_id ?>"></button>
					</div>
				</div>
			</div>
		)
	}
}
export default CastosPlayerPanels;
