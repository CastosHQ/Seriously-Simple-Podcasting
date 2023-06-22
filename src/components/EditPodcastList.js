import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {InspectorControls} from '@wordpress/block-editor';
import {PanelBody, PanelRow, FormToggle, SelectControl, __experimentalNumberControl as NumberControl, Tooltip} from '@wordpress/components';
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

		const {
			showTitle,
			featuredImage,
			featuredImageSize,
			excerpt,
			player,
			playerBelowExcerpt,
			availablePodcasts,
			selectedPodcast,
			postsPerPage,
			availableImageSizes,
			orderBy,
			order,
			columnsPerRow,
			titleSize,
			titleUnderImage
		} = attributes;

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
				<div className="ssp-controls ssp-edit-podcast-list">
					<PanelBody key="podcast-list-content" title={__('Content', 'seriously-simple-podcasting')}>
						<PanelRow>
							<label htmlFor="ssp-podcast-list-show-title">
								{__('Show Title', 'seriously-simple-podcasting')}
							</label>
							<FormToggle
								id="ssp-podcast-list-show-title"
								label={__('Show Title', 'seriously-simple-podcasting')}
								checked={showTitle}
								onChange={() => {
									setAttributes({
										showTitle: !showTitle
									});
								}}
							/>
						</PanelRow>
						<PanelRow>
							<label htmlFor="ssp-podcast-list-show-featured-image">
								{__('Show Featured Image', 'seriously-simple-podcasting')}
							</label>
							<FormToggle
								id="ssp-podcast-list-show-featured-image"
								label={__('Show Featured Image', 'seriously-simple-podcasting')}
								checked={featuredImage}
								onChange={toggleFeaturedImage}
							/>
						</PanelRow>
						{featuredImage &&
							<PanelRow>
								<label htmlFor="ssp-podcast-list-image-size">
									{__('Featured Image Size', 'seriously-simple-podcasting')}
								</label>
								<SelectControl
									id="ssp-podcast-list-image-size"
									value={featuredImageSize}
									options={availableImageSizes}
									onChange={(newSize) => {
										setAttributes({
											featuredImageSize: newSize
										});
									}}
								/>
							</PanelRow>}
						<PanelRow>
							<label htmlFor="ssp-podcast-list-show-player">
								{__('Show Podcast Player', 'seriously-simple-podcasting')}
							</label>
							<FormToggle
								id="ssp-podcast-list-show-player"
								label={__('Show Podcast Player', 'seriously-simple-podcasting')}
								checked={player}
								onChange={togglePlayer}
							/>
						</PanelRow>
						<PanelRow>
							<label htmlFor="ssp-podcast-list-show-excerpt">
								{__('Show Podcast Excerpt', 'seriously-simple-podcasting')}
							</label>
							<FormToggle
								id="ssp-podcast-list-show-excerpt"
								label={__('Show Podcast Excerpt', 'seriously-simple-podcasting')}
								checked={excerpt}
								onChange={toggleExcerpt}
							/>
						</PanelRow>
					</PanelBody>

					<PanelBody key="ssp-podcast-list-query" title={__('Query', 'seriously-simple-podcasting')}>
						<PanelRow>
							<label htmlFor="ssp-podcast-list-podcast">
								{__('Select Podcast', 'seriously-simple-podcasting')}
							</label>
							<SelectControl
								id="ssp-podcast-list-podcast"
								value={selectedPodcast}
								options={availablePodcasts}
								onChange={(selectedPodcast) => {
									setAttributes({
										selectedPodcast: selectedPodcast
									});
								}}
							/>
						</PanelRow>
						<PanelRow>
							<label htmlFor="ssp-podcast-list-posts-per-page">
								{__('Posts Per Page', 'seriously-simple-podcasting')}
								<Tooltip text={__('For the default global settings, use 0', 'seriously-simple-podcasting')} htmlFor="ssp-podcast-list-posts-per-page">
									<span className="dashicon dashicons dashicons-info"></span>
								</Tooltip>
							</label>
							<NumberControl
								id="ssp-podcast-list-posts-per-page"
								value={postsPerPage}
								min={0}
								onChange={(postsPerPage) => {
									setAttributes({
										postsPerPage: postsPerPage
									});
								}}
							/>
						</PanelRow>
						<PanelRow>
							<label htmlFor="ssp-podcast-list-order-by">
								{__('Order By', 'seriously-simple-podcasting')}
							</label>
							<SelectControl
								id="ssp-podcast-list-order-by"
								value={orderBy}
								options={[
									{label: __('Date', 'seriously-simple-podcasting'), value: 'date'},
									{label: __('ID', 'seriously-simple-podcasting'), value: 'ID'},
									{label: __('Title', 'seriously-simple-podcasting'), value: 'title'},
									{label: __('Recorded Date', 'seriously-simple-podcasting'), value: 'recorded'},
								]}
								onChange={(orderBy) => {
									setAttributes({
										orderBy: orderBy
									});
								}}
							/>
						</PanelRow>
						<PanelRow>
							<label htmlFor="ssp-podcast-list-order">
								{__('Order', 'seriously-simple-podcasting')}
							</label>
							<SelectControl
								id="ssp-podcast-list-order"
								value={order}
								options={[
									{label: 'ASC', value: 'asc'},
									{label: 'DESC', value: 'desc'},
								]}
								onChange={(order) => {
									setAttributes({
										order: order
									});
								}}
							/>
						</PanelRow>
					</PanelBody>
					<PanelBody key="ssp-podcast-list-style" title={__('Style', 'seriously-simple-podcasting')}>
						<PanelRow>
							<label htmlFor="ssp-podcast-list-columns-per-row">
								{__('Columns per row', 'seriously-simple-podcasting')}
							</label>
							<NumberControl
								id="ssp-podcast-list-columns-per-row"
								value={columnsPerRow}
								min={1}
								max={6}
								onChange={(columnsPerRow) => {
									setAttributes({
										columnsPerRow: columnsPerRow
									});
								}}
							/>
						</PanelRow>
						{showTitle && <PanelRow>
							<label htmlFor="ssp-podcast-list-title-size">
								{__('Title Size', 'seriously-simple-podcasting')}
							</label>
							<NumberControl
								id="ssp-podcast-list-title-size"
								value={titleSize}
								min={8}
								max={40}
								onChange={(titleSize) => {
									setAttributes({
										titleSize: titleSize
									});
								}}
							/>
						</PanelRow>}
						{showTitle && featuredImage && <PanelRow>
							<label htmlFor="ssp-podcast-list-title-under-image">
								{__('Show Title Under Image', 'seriously-simple-podcasting')}
							</label>
							<FormToggle
								id="ssp-podcast-list-title-under"
								label={__('Show Title Under Image', 'seriously-simple-podcasting')}
								checked={titleUnderImage}
								onChange={() => {
									setAttributes({
										titleUnderImage: !titleUnderImage
									});
								}}
							/>
						</PanelRow>}
						{player && excerpt && <PanelRow>
							<label htmlFor="ssp-podcast-list-player-below-excerpt">
								{__('Show Player Below Excerpt', 'seriously-simple-podcasting')}
							</label>
							<FormToggle
								id="ssp-podcast-list-player-below-excerpt"
								label={__('Show Player Below Excerpt', 'seriously-simple-podcasting')}
								checked={playerBelowExcerpt}
								onChange={toggleShowPlayerBelowExcerpt}
							/>
						</PanelRow>}
					</PanelBody>
				</div>
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
