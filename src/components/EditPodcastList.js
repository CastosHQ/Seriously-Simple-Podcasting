import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {InspectorControls} from '@wordpress/block-editor';
import {PanelBody, PanelRow, FormToggle, SelectControl} from '@wordpress/components';
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

		const {featuredImage, featuredImageSize, excerpt, player, playerBelowExcerpt} = attributes;


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

		const toggleShowPlayerBelowExcerpt = () => {
			setAttributes({
				playerBelowExcerpt: !playerBelowExcerpt
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
							id="featured-image-form-toggle"
							label={__('Show Featured Image', 'seriously-simple-podcasting')}
							checked={featuredImage}
							onChange={toggleFeaturedImage}
						/>
					</PanelRow>
					{featuredImage &&
					<PanelRow>
						<label htmlFor="featured-image-size">
							{__('Featured Image Size', 'seriously-simple-podcasting')}
						</label>
						<SelectControl
							id="featured-image-size"
							value={featuredImageSize}
							options={[
								{label: 'Full', value: 'full'},
								{label: 'large', value: 'large'},
								{label: 'Medium', value: 'medium'},
								{label: 'Thumbnail', value: 'thumbnail'},
							]}
							onChange={(newSize) => {
								setAttributes({
									featuredImageSize: newSize
								});
							}}
						/>
					</PanelRow>}
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
				{player && excerpt && <PanelBody key="show-player-below-excerpt" title={__('Show Player Below Excerpt', 'seriously-simple-podcasting')}>
					<PanelRow>
						<label htmlFor="player-below-excerpt">
							{__('Show Player Below Excerpt', 'seriously-simple-podcasting')}
						</label>
						<FormToggle
							id="player-below-excerpt"
							label={__('Show Player Below Excerpt', 'seriously-simple-podcasting')}
							checked={playerBelowExcerpt}
							onChange={toggleShowPlayerBelowExcerpt}
						/>
					</PanelRow>
				</PanelBody>}
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
