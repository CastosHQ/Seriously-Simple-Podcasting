import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {InspectorControls, PanelColorSettings} from '@wordpress/block-editor';
import {PanelBody, PanelRow, FormToggle, SelectControl, TextControl, __experimentalNumberControl as NumberControl, Tooltip} from '@wordpress/components';
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
			postsPerPage,
			availableImageSizes,
			orderBy,
			order,
			columnsPerRow,
			titleSize,
			titleUnderImage,
			defaultPodcastId,
			paginationType,
			titleColor,
			layout,
			clickable,
			buttonText,
			textColor,
			linkColor,
			cardBackground,
			buttonColor,
			buttonBackground,
			paginationColor,
			paginationActiveColor,
		} = attributes;

		let {selectedPodcast} = attributes;

		// In version 3.0.0 default 0 was changed to the real Podcast(Series) term
		selectedPodcast = '0' === selectedPodcast ? defaultPodcastId : selectedPodcast;

		const isCards = layout === 'cards';

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

					<PanelBody key="ssp-podcast-list-layout" title={__('Layout', 'seriously-simple-podcasting')}>
						<PanelRow>
							<label htmlFor="ssp-podcast-list-layout-select">
								{__('Layout', 'seriously-simple-podcasting')}
							</label>
							<SelectControl
								id="ssp-podcast-list-layout-select"
								value={layout || 'list'}
								options={[
									{label: __('List', 'seriously-simple-podcasting'), value: 'list'},
									{label: __('Cards', 'seriously-simple-podcasting'), value: 'cards'},
								]}
								onChange={(layout) => setAttributes({layout})}
							/>
						</PanelRow>
						{isCards && <PanelRow>
							<TextControl
								label={__('Button Text', 'seriously-simple-podcasting')}
								value={buttonText || __('Listen Now', 'seriously-simple-podcasting')}
								onChange={(buttonText) => setAttributes({buttonText})}
							/>
						</PanelRow>}
						{isCards && <PanelRow>
							<label htmlFor="ssp-podcast-list-clickable">
								{__('Clickable', 'seriously-simple-podcasting')}
							</label>
							<SelectControl
								id="ssp-podcast-list-clickable"
								value={clickable || 'button'}
								options={[
									{label: __('Button', 'seriously-simple-podcasting'), value: 'button'},
									{label: __('Card', 'seriously-simple-podcasting'), value: 'card'},
									{label: __('Title', 'seriously-simple-podcasting'), value: 'title'},
								]}
								onChange={(clickable) => setAttributes({clickable})}
							/>
						</PanelRow>}
						<PanelRow>
							<label htmlFor="ssp-podcast-list-pagination-type">
								{__('Pagination', 'seriously-simple-podcasting')}
							</label>
							<SelectControl
								id="ssp-podcast-list-pagination-type"
								value={paginationType || 'simple'}
								options={[
									{label: __('Simple (Prev/Next)', 'seriously-simple-podcasting'), value: 'simple'},
									{label: __('Full (Numbered)', 'seriously-simple-podcasting'), value: 'full'},
								]}
								onChange={(paginationType) => setAttributes({paginationType})}
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
								max={2}
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
						{showTitle && featuredImage && !isCards && <PanelRow>
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

					<PanelColorSettings
						title={__('Colors', 'seriously-simple-podcasting')}
						initialOpen={false}
						colorSettings={[
							{
								label: __('Title Color', 'seriously-simple-podcasting'),
								value: titleColor,
								onChange: (value) => setAttributes({titleColor: value || ''}),
							},
							{
								label: __('Text Color', 'seriously-simple-podcasting'),
								value: textColor,
								onChange: (value) => setAttributes({textColor: value || ''}),
							},
							{
								label: __('Link Color', 'seriously-simple-podcasting'),
								value: linkColor,
								onChange: (value) => setAttributes({linkColor: value || ''}),
							},
							{
								label: __('Pagination Link Color', 'seriously-simple-podcasting'),
								value: paginationColor,
								onChange: (value) => setAttributes({paginationColor: value || ''}),
							},
							{
								label: __('Pagination Active Color', 'seriously-simple-podcasting'),
								value: paginationActiveColor,
								onChange: (value) => setAttributes({paginationActiveColor: value || ''}),
							},
							...(isCards ? [
								{
									label: __('Card Background', 'seriously-simple-podcasting'),
									value: cardBackground,
									onChange: (value) => setAttributes({cardBackground: value || ''}),
								},
								{
									label: __('Button Color', 'seriously-simple-podcasting'),
									value: buttonColor,
									onChange: (value) => setAttributes({buttonColor: value || ''}),
								},
								{
									label: __('Button Background', 'seriously-simple-podcasting'),
									value: buttonBackground,
									onChange: (value) => setAttributes({buttonBackground: value || ''}),
								},
							] : []),
						]}
					/>
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
