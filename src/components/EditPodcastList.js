const {__} = wp.i18n;
const {Component} = wp.element;
const {BlockControls} = wp.blockEditor;
const {Button, Toolbar} = wp.components;

const {apiFetch} = wp;

import PodcastListItem from './PodcastListItem';

class EditPodcastList extends Component {
	constructor({className}) {
		super(...arguments);
		this.state = {
			className,
			episodes: [],
		};
	}

	componentDidMount() {
		const fetchPost = 'ssp/v1/episodes';
		apiFetch({path: fetchPost}).then(posts => {
			const episodes = []
			Object.keys(posts).map(function (key) {
				const episode = posts[key];
				episodes.push(episode);
			});
			this.setState({
				episodes: episodes,
			});
		});
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
