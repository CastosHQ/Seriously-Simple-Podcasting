import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {InspectorControls} from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	SelectControl,
	ToggleControl,
	RangeControl,
	TextControl,
	ColorPicker,
	__experimentalNumberControl as NumberControl,
	Tooltip
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

class EditSSPPodcasts extends Component {
	constructor({className}) {
		super(...arguments);
		this.state = {
			className,
			availablePodcasts: [],
		};
	}

	componentDidMount() {
		// Get available podcasts from the localized script data
		if (window.sspAdmin && window.sspAdmin.sspPostTypes) {
			// For now, we'll use a simple approach - in a real implementation,
			// we might want to fetch this via REST API
			this.setState({
				availablePodcasts: [
					{ label: __('-- All --', 'seriously-simple-podcasting'), value: '' }
				]
			});
		}
	}

	render() {
		const {className} = this.state;
		const {attributes, setAttributes} = this.props;

		const {
			ids,
			columns,
			sort_by,
			sort,
			clickable,
			show_button,
			show_description,
			show_episode_count,
			description_words,
			description_chars,
			background,
			background_hover,
			button_color,
			button_hover_color,
			button_text_color,
			button_text,
			title_color,
			episode_count_color,
			description_color,
		} = attributes;

		// Sort options
		const sortByOptions = [
			{ label: __('ID', 'seriously-simple-podcasting'), value: 'id' },
			{ label: __('Name', 'seriously-simple-podcasting'), value: 'name' },
			{ label: __('Episode Count', 'seriously-simple-podcasting'), value: 'episode_count' },
		];

		const sortOptions = [
			{ label: __('Ascending', 'seriously-simple-podcasting'), value: 'asc' },
			{ label: __('Descending', 'seriously-simple-podcasting'), value: 'desc' },
		];

		const clickableOptions = [
			{ label: __('Button', 'seriously-simple-podcasting'), value: 'button' },
			{ label: __('Card', 'seriously-simple-podcasting'), value: 'card' },
			{ label: __('Title', 'seriously-simple-podcasting'), value: 'title' },
		];

		const controls = (
			<InspectorControls key="inspector-controls">
				<div className="ssp-controls ssp-edit-ssp-podcasts">
					
					{/* Content Panel */}
					<PanelBody key="ssp-podcasts-content" title={__('Content', 'seriously-simple-podcasting')} initialOpen={true}>
						<PanelRow>
							<label htmlFor="ssp-podcasts-ids">
								{__('Podcast IDs', 'seriously-simple-podcasting')}
								<Tooltip text={__('Comma-separated podcast IDs. Leave empty to show all podcasts.', 'seriously-simple-podcasting')}>
									<span className="dashicon dashicons dashicons-info"></span>
								</Tooltip>
							</label>
							<TextControl
								id="ssp-podcasts-ids"
								value={ids}
								onChange={(value) => setAttributes({ ids: value })}
								placeholder={__('e.g., 1,2,3', 'seriously-simple-podcasting')}
							/>
						</PanelRow>
						
						<PanelRow>
							<label htmlFor="ssp-podcasts-sort-by">
								{__('Sort By', 'seriously-simple-podcasting')}
							</label>
							<SelectControl
								id="ssp-podcasts-sort-by"
								value={sort_by}
								options={sortByOptions}
								onChange={(value) => setAttributes({ sort_by: value })}
							/>
						</PanelRow>
						
						<PanelRow>
							<label htmlFor="ssp-podcasts-sort">
								{__('Sort Direction', 'seriously-simple-podcasting')}
							</label>
							<SelectControl
								id="ssp-podcasts-sort"
								value={sort}
								options={sortOptions}
								onChange={(value) => setAttributes({ sort: value })}
							/>
						</PanelRow>
						
						<PanelRow>
							<label htmlFor="ssp-podcasts-columns">
								{__('Columns', 'seriously-simple-podcasting')}
							</label>
							<RangeControl
								id="ssp-podcasts-columns"
								value={columns}
								onChange={(value) => setAttributes({ columns: value })}
								min={1}
								max={3}
								step={1}
							/>
						</PanelRow>
						
						<PanelRow>
							<label htmlFor="ssp-podcasts-clickable">
								{__('Clickable Mode', 'seriously-simple-podcasting')}
							</label>
							<SelectControl
								id="ssp-podcasts-clickable"
								value={clickable}
								options={clickableOptions}
								onChange={(value) => setAttributes({ clickable: value })}
							/>
						</PanelRow>
					</PanelBody>

					{/* Display Panel */}
					<PanelBody key="ssp-podcasts-display" title={__('Display', 'seriously-simple-podcasting')}>
						<PanelRow>
							<label htmlFor="ssp-podcasts-show-description">
								{__('Show Description', 'seriously-simple-podcasting')}
							</label>
							<ToggleControl
								id="ssp-podcasts-show-description"
								checked={show_description === 'true'}
								onChange={(value) => setAttributes({ show_description: value ? 'true' : 'false' })}
							/>
						</PanelRow>
						
						<PanelRow>
							<label htmlFor="ssp-podcasts-show-episode-count">
								{__('Show Episode Count', 'seriously-simple-podcasting')}
							</label>
							<ToggleControl
								id="ssp-podcasts-show-episode-count"
								checked={show_episode_count === 'true'}
								onChange={(value) => setAttributes({ show_episode_count: value ? 'true' : 'false' })}
							/>
						</PanelRow>
						
						<PanelRow>
							<label htmlFor="ssp-podcasts-show-button">
								{__('Show Button', 'seriously-simple-podcasting')}
							</label>
							<ToggleControl
								id="ssp-podcasts-show-button"
								checked={show_button === 'true'}
								onChange={(value) => setAttributes({ show_button: value ? 'true' : 'false' })}
							/>
						</PanelRow>
						
						{show_button === 'true' && (
							<PanelRow>
								<label htmlFor="ssp-podcasts-button-text">
									{__('Button Text', 'seriously-simple-podcasting')}
								</label>
								<TextControl
									id="ssp-podcasts-button-text"
									value={button_text}
									onChange={(value) => setAttributes({ button_text: value })}
									placeholder={__('Listen Now', 'seriously-simple-podcasting')}
								/>
							</PanelRow>
						)}
						
						{show_description === 'true' && (
							<>
								<PanelRow>
									<label htmlFor="ssp-podcasts-description-words">
										{__('Description Word Limit', 'seriously-simple-podcasting')}
										<Tooltip text={__('Limit description by number of words. Set to 0 for no limit.', 'seriously-simple-podcasting')}>
											<span className="dashicon dashicons dashicons-info"></span>
										</Tooltip>
									</label>
									<NumberControl
										id="ssp-podcasts-description-words"
										value={description_words}
										onChange={(value) => setAttributes({ description_words: value })}
										min={0}
										step={1}
									/>
								</PanelRow>
								
								<PanelRow>
									<label htmlFor="ssp-podcasts-description-chars">
										{__('Description Character Limit', 'seriously-simple-podcasting')}
										<Tooltip text={__('Limit description by number of characters. Set to 0 for no limit. Takes priority over word limit.', 'seriously-simple-podcasting')}>
											<span className="dashicon dashicons dashicons-info"></span>
										</Tooltip>
									</label>
									<NumberControl
										id="ssp-podcasts-description-chars"
										value={description_chars}
										onChange={(value) => setAttributes({ description_chars: value })}
										min={0}
										step={1}
									/>
								</PanelRow>
							</>
						)}
					</PanelBody>

					{/* Styling Panel */}
					<PanelBody key="ssp-podcasts-styling" title={__('Styling', 'seriously-simple-podcasting')}>
						<PanelRow>
							<label htmlFor="ssp-podcasts-background">
								{__('Background Color', 'seriously-simple-podcasting')}
							</label>
							<ColorPicker
								color={background}
								onChangeComplete={(color) => setAttributes({ background: color.hex })}
								disableAlpha
							/>
						</PanelRow>
						
						<PanelRow>
							<label htmlFor="ssp-podcasts-background-hover">
								{__('Background Hover Color', 'seriously-simple-podcasting')}
							</label>
							<ColorPicker
								color={background_hover}
								onChangeComplete={(color) => setAttributes({ background_hover: color.hex })}
								disableAlpha
							/>
						</PanelRow>
						
						<PanelRow>
							<label htmlFor="ssp-podcasts-title-color">
								{__('Title Color', 'seriously-simple-podcasting')}
							</label>
							<ColorPicker
								color={title_color}
								onChangeComplete={(color) => setAttributes({ title_color: color.hex })}
								disableAlpha
							/>
						</PanelRow>
						
						<PanelRow>
							<label htmlFor="ssp-podcasts-episode-count-color">
								{__('Episode Count Color', 'seriously-simple-podcasting')}
							</label>
							<ColorPicker
								color={episode_count_color}
								onChangeComplete={(color) => setAttributes({ episode_count_color: color.hex })}
								disableAlpha
							/>
						</PanelRow>
						
						<PanelRow>
							<label htmlFor="ssp-podcasts-description-color">
								{__('Description Color', 'seriously-simple-podcasting')}
							</label>
							<ColorPicker
								color={description_color}
								onChangeComplete={(color) => setAttributes({ description_color: color.hex })}
								disableAlpha
							/>
						</PanelRow>
						
						{show_button === 'true' && (
							<>
								<PanelRow>
									<label htmlFor="ssp-podcasts-button-color">
										{__('Button Color', 'seriously-simple-podcasting')}
									</label>
									<ColorPicker
										color={button_color}
										onChangeComplete={(color) => setAttributes({ button_color: color.hex })}
										disableAlpha
									/>
								</PanelRow>
								
								<PanelRow>
									<label htmlFor="ssp-podcasts-button-hover-color">
										{__('Button Hover Color', 'seriously-simple-podcasting')}
									</label>
									<ColorPicker
										color={button_hover_color}
										onChangeComplete={(color) => setAttributes({ button_hover_color: color.hex })}
										disableAlpha
									/>
								</PanelRow>
								
								<PanelRow>
									<label htmlFor="ssp-podcasts-button-text-color">
										{__('Button Text Color', 'seriously-simple-podcasting')}
									</label>
									<ColorPicker
										color={button_text_color}
										onChangeComplete={(color) => setAttributes({ button_text_color: color.hex })}
										disableAlpha
									/>
								</PanelRow>
							</>
						)}
					</PanelBody>
				</div>
			</InspectorControls>
		);

		return [
			controls,
			<div key="ssp-podcasts-preview" className={className}>
				<ServerSideRender
					block="seriously-simple-podcasting/ssp-podcasts"
					attributes={attributes}
				/>
			</div>
		];
	}
}

export default EditSSPPodcasts;
