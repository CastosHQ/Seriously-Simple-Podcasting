import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {InspectorControls} from '@wordpress/block-editor';
import {PanelBody, PanelRow, FormToggle} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

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

		const {attributes, setAttributes} = this.props;

		const {featuredImage, excerpt, player} = attributes;

		const toggleFeaturedImage = () => {
			setAttributes({
				featuredImage: !featuredImage
			});
		}

		const toggleExcerpt = () => {
			setAttributes({
				excerpt: !excerpt
			});
		}

		const togglePlayer = () => {
			setAttributes({
				player: !player
			});
		}

		const controls = (
			<InspectorControls>
				<PanelBody title={__('Featured Image', 'seriously-simple-podcasting')}>
					<PanelRow>
						<label htmlFor="featured-image-form-toggle">
							{__('Show Featured Image', 'seriously-simple-podcasting')}
						</label>
						<FormToggle
							id="high-contrast-form-toggle"
							label={__('Show Featured Image', 'seriously-simple-podcasting')}
							checked={featuredImage}
							onChange={toggleFeaturedImage}
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody title={__('Podcast Excerpt', 'seriously-simple-podcasting')}>
					<PanelRow>
						<label htmlFor="podcast-excerpt-form-toggle">
							{__('Show Podcast Excerpt', 'seriously-simple-podcasting')}
						</label>
						<FormToggle
							id="podcast-excerpt-form-toggle"
							label={__('Show Podcast Excerpt', 'seriously-simple-podcasting')}
							checked={excerpt}
							onChange={toggleExcerpt}
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody title={__('Podcast Player', 'seriously-simple-podcasting')}>
					<PanelRow>
						<label htmlFor="podcast-player-form-toggle">
							{__('Show Podcast Player', 'seriously-simple-podcasting')}
						</label>
						<FormToggle
							id="podcast-player-form-toggle"
							label={__('Show Podcast Player', 'seriously-simple-podcasting')}
							checked={player}
							onChange={togglePlayer}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
		);

		const episodeItems = episodes.map((post) =>
			<PodcastListItem key={post.id} className={className} post={post} attributes={attributes} />
		);

		return [
			controls, (
				<div>{episodeItems}</div>
			)];
	}
}

export default EditPodcastList;
