import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {InspectorControls} from '@wordpress/block-editor';
import {PanelBody, PanelRow, SelectControl, __experimentalNumberControl as NumberControl, Tooltip} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

class EditPlaylistPlayer extends Component {
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
			availablePodcasts,
			availableTags,
			selectedTag,
			limit,
			orderBy,
			order,
			selectedPodcast
		} = attributes;

		const controls = (
			<InspectorControls key="inspector-controls">
				<div className="ssp-controls ssp-edit-podcast-list">
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
							<label htmlFor="ssp-playlist-player-tag">
								{__('Select Tag', 'seriously-simple-podcasting')}
							</label>
							<SelectControl
								id="ssp-playlist-player-tag"
								value={selectedTag}
								options={availableTags}
								onChange={(selectedTag) => {
									setAttributes({
										selectedTag: selectedTag
									});
								}}
							/>
						</PanelRow>
						<PanelRow>
							<label htmlFor="ssp-playlist-player-episodes-limit">
								{__('Episodes Limit', 'seriously-simple-podcasting')}
								<Tooltip text={
									__('For the default global settings, use 0. To remove the limit, use -1', 'seriously-simple-podcasting')
								} htmlFor="ssp-playlist-player-episodes-limit">
									<span className="dashicon dashicons dashicons-info"></span>
								</Tooltip>
							</label>
							<NumberControl
								id="ssp-playlist-player-episodes-limit"
								value={limit}
								min={-1}
								onChange={(limit) => {
									setAttributes({
										limit: limit
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
				</div>
			</InspectorControls>
		);

		return [
			controls,
			<ServerSideRender className={className}
							  key={"playlist-player"}
							  block="seriously-simple-podcasting/playlist-player"
							  attributes={attributes}
			/>];
	}
}

export default EditPlaylistPlayer;
