import {Component} from '@wordpress/element';
import {fetchPodcastOptions, logRequestFailure} from '../utils/taxonomyOptions';

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

		fetchPodcastOptions()
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
