/**
 * WordPress dependencies
 */
const {__} = wp.i18n;
const {Component} = wp.element;
const {BlockControls} = wp.blockEditor;
const {Button, Toolbar} = wp.components;

const {apiFetch} = wp;

import PodcastListItem from './PodcastListItem';

class EditPodcastList extends Component {
	constructor({className}) {
		super(...arguments);
		this._isMounted = false;
		this.state = {
			isLoading: true,
			className,
			episodes: [],
		};
	}

	componentDidMount() {
		this._isMounted = true;

		const fetchPost = 'ssp/v1/episodes?context=edit';
		apiFetch({path: fetchPost}).then(posts => {
			console.log(posts);
			const episodes = []
			Object.keys(posts).map(function (key) {
				const episode = posts[key];
				episodes.push(episode);
			});
			this.setState({
				episodes: episodes,
			});
			if (this._isMounted) {
				this.setState({
					isLoading: false
				})
			}
		});
	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	render() {

		const {className, episodes} = this.state;

		const episodeItems = episodes.map((post) =>
			<PodcastListItem key={post.id} className={className} post={post} />
		);

		return (
			<div>{episodeItems}</div>
		);

	}
}

export default EditPodcastList;
