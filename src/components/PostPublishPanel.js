import {Component} from '@wordpress/element';
import { PluginPostPublishPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';

class PostPublishPanel extends Component {
	render() {
		let sspPostTypes = sspAdmin.sspPostTypes, // Todo: investigate how to get rid of a global variable here
			isCastosUser = sspAdmin.isCastosUser,
			currentPostType = wp.data.select('core/editor').getCurrentPostType();

		if (!sspPostTypes instanceof Array || !sspPostTypes.includes(currentPostType) || isCastosUser) {
			return;
		}

		return (
			<PluginPostPublishPanel>
				<div className={'ssp-post-publish'}>
					<div className={'ssp-post-publish__container'}>
						<div className={'ssp-post-publish__logos'}>
							<a className={'ssp-post-publish__apple'} title={'Apple Podcasts'} href={'https://podcasters.apple.com/'}><span>Apple Podcasts</span></a>
							<a className={'ssp-post-publish__amazon'} title={'Amazon'} href={'https://podcasters.amazon.com/'}><span>Amazon</span></a>
							<a className={'ssp-post-publish__spotify'} title={'Spotify'} href={'https://podcasters.spotify.com/'}><span>Spotify</span></a>
							<a className={'ssp-post-publish__google'} title={'Google Podcasts'} href={'https://podcasts.google.com/'}><span>Google Podcasts</span></a>
							<a className={'ssp-post-publish__overcast'} title={'Overcast'} href={'https://overcast.fm/'}><span>Overcast</span></a>
						</div>
						<div className={'ssp-post-publish__description'}>
							{ __('Distribute your podcast everywhere, automatically.', 'seriously-simple-podcasting') }
						</div>
						<div className={'ssp-post-publish__btn'}>
							<a href={'https://castos.com/podcast-hosting-wordpress/?utm_source=ssp&utm_medium=episode-published-box&utm_campaign=upgrade'}>Try Castos for free</a>
						</div>
					</div>
				</div>
			</PluginPostPublishPanel>
		);
	}
}

export default PostPublishPanel;
