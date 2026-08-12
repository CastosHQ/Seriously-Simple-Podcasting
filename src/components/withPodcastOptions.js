import {Component} from '@wordpress/element';
import {fetchPodcastOptions, logRequestFailure} from '../utils/taxonomyOptions';

/**
 * Shared request, so every block on the page reuses one walk through the podcast list.
 *
 * A post can hold several Playlist Players, and each one would otherwise page through the whole
 * taxonomy by itself.
 *
 * @type {Promise|null}
 */
let podcastOptionsRequest = null;

/**
 * Starts the shared request, or joins the one already running.
 *
 * A failed request is discarded rather than cached, so the next block to mount can try again.
 *
 * @return {Promise<Array>} Podcast options.
 */
const getPodcastOptions = () => {
	if (!podcastOptionsRequest) {
		podcastOptionsRequest = fetchPodcastOptions().catch((error) => {
			podcastOptionsRequest = null;
			throw error;
		});
	}

	return podcastOptionsRequest;
};

/**
 * Supplies the podcast option list to a block edit component.
 *
 * Three blocks need the same list, and none of them can carry it as an attribute default without
 * putting a term query on every editor page load. Each wrapped component receives:
 *
 * - `availablePodcasts`   — select options, empty until the request resolves
 * - `isLoadingPodcasts`   — true while the request is in flight
 *
 * @param {Function} WrappedComponent Block edit component.
 *
 * @return {Function} Wrapped component.
 */
const withPodcastOptions = (WrappedComponent) => class WithPodcastOptions extends Component {
	constructor(props) {
		super(props);

		this.state = {
			availablePodcasts: [],
			isLoadingPodcasts: true,
		};

		this.isActive = false;
	}

	componentDidMount() {
		this.isActive = true;

		getPodcastOptions()
			.then((availablePodcasts) => {
				if (this.isActive) {
					this.setState({availablePodcasts, isLoadingPodcasts: false});
				}
			})
			.catch((error) => {
				// Leaving the list empty is the honest outcome — better an empty picker than one
				// silently claiming the site has no podcasts.
				if (this.isActive) {
					this.setState({isLoadingPodcasts: false});
				}

				logRequestFailure(error);
			});
	}

	componentWillUnmount() {
		this.isActive = false;
	}

	render() {
		return (
			<WrappedComponent
				{...this.props}
				availablePodcasts={this.state.availablePodcasts}
				isLoadingPodcasts={this.state.isLoadingPodcasts}
			/>
		);
	}
};

export default withPodcastOptions;
