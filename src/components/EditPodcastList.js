import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {InspectorControls} from '@wordpress/block-editor';
import {PanelBody, PanelRow, FormToggle} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

class EditPodcastList extends Component {
	constructor({className}) {
		super(...arguments);
		this.state = {
			className,
			episodes: [],
		};
	}

	render() {
		const {className} = this.state;

		const {attributes, setAttributes} = this.props;

		const {featuredImage, excerpt, player} = attributes;


		const toggleFeaturedImage = () => {
			setAttributes({
				featuredImage: !featuredImage
			});
		}

		const togglePlayer = () => {
			setAttributes({
				player: !player
			});
		}

		const toggleExcerpt = () => {
			setAttributes({
				excerpt: !excerpt
			});
		}

		const controls = (
			<InspectorControls key="inspector-controls">
				<PanelBody key="panel-1" title={__('Featured Image', 'seriously-simple-podcasting')}>
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
				<PanelBody key="panel-2" title={__('Podcast Player', 'seriously-simple-podcasting')}>
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
				<PanelBody key="panel-3" title={__('Podcast Excerpt', 'seriously-simple-podcasting')}>
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
			</InspectorControls>
		);

		return [
			controls,
			<ServerSideRender className={className}
							  key={"episode-items"}
							  block="seriously-simple-podcasting/podcast-list"
							  attributes={attributes}
			/>];
	}
}

export default EditPodcastList;
