/**
 * WordPress dependencies
 */
const {__} = wp.i18n;
const {Component} = wp.element;
const {BlockControls} = wp.blockEditor;
const {Button, Toolbar} = wp.components;

const {apiFetch} = wp;

import PodcastListItem from './PodcastListItem';


/*withSelect( ( select ) => {
	return {
		posts: select( 'core' ).getEntityRecords( 'postType', 'podcast' ),
	};
} )( ( { posts, className } ) => {
	if ( ! posts ) {
		return 'Loading...';
	}

	if ( posts && posts.length === 0 ) {
		return 'No podcasts';
	}

	const postItems = posts.map((post) =>
		<li key={post.id}>
			<a className={ className } href={ post.link }>
				{ post.title.rendered }
			</a>
		</li>
	);

	/!*const post = posts[ 0 ];*!/

	return (
		<ul>{postItems}</ul>
	);
} ),*/

class EditPodcastList extends Component {
	constructor({className}) {
		super(...arguments);
		this.state = {
			className,
			episodes: [],
		};
	}

	render() {

		const {className, episodes} = this.state;

		//const { setAttributes } = this.props;

		const populateEpisodes = () => {
			let fetchPost = 'ssp/v1/episodes';
			apiFetch({path: fetchPost}).then(posts => {
				let episodes = []
				Object.keys(posts).map(function (key) {
					let episode = posts[key];
					episodes.push(episode);
				});
				this.setState({
					episodes: episodes,
				});
			});
		}

		if (episodes.length === 0) {
			populateEpisodes()
		}

		console.log(episodes);

		const episodeItems = episodes.map((post) =>
			<PodcastListItem key={post.id} className={className} post={post} />
		);

		return (
			<div>{episodeItems}</div>
		);

	}
}

export default EditPodcastList;
