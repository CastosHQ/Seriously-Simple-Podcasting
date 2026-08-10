import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {InspectorControls} from '@wordpress/block-editor';
import {PanelBody, PanelRow, SelectControl, ComboboxControl, __experimentalNumberControl as NumberControl, Tooltip} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import withPodcastOptions from './withPodcastOptions';
import {searchTagOptions, fetchTagOptionBySlug, logRequestFailure, ALL_TAGS_VALUE} from '../utils/taxonomyOptions';

/** Debounce for tag search, so typing does not fire a request per keystroke. */
const TAG_SEARCH_DELAY = 300;

class EditPlaylistPlayer extends Component {
	constructor({className}) {
		super(...arguments);
		this.state = {
			className,
			episodes: [],
			searchResults: [],
			selectedTagOption: null,
		};

		this.isActive = false;
		this.searchTimer = null;
		this.latestSearch = 0;
		this.searchTags = this.searchTags.bind(this);
	}

	componentDidMount() {
		this.isActive = true;

		// Show the first page of tags before the editor types anything, and resolve the tag already
		// saved on the block — it may sort well past the first page, or have been deleted since.
		this.searchTags('', true);
		this.resolveSelectedTag();
	}

	componentWillUnmount() {
		this.isActive = false;
		clearTimeout(this.searchTimer);
	}

	/**
	 * Resolves the saved tag so the control can render its name rather than its slug.
	 *
	 * A tag deleted since the block was saved resolves to nothing, so the slug itself becomes the
	 * label — showing the stale value beats showing an empty field while the block still filters
	 * by it.
	 */
	resolveSelectedTag() {
		const slug = this.props.attributes.selectedTag;

		if (!slug) {
			return;
		}

		fetchTagOptionBySlug(slug)
			.then((selectedTagOption) => {
				if (this.isActive) {
					this.setState({selectedTagOption: selectedTagOption || {label: slug, value: slug}});
				}
			})
			.catch(logRequestFailure);
	}

	/**
	 * Searches tags, debounced, keeping only the response to the most recent request.
	 *
	 * @param {string}  search    Search term.
	 * @param {boolean} immediate Whether to skip the debounce, for the initial load.
	 */
	searchTags(search, immediate = false) {
		clearTimeout(this.searchTimer);

		this.latestSearch += 1;
		const requestId = this.latestSearch;

		const run = () => {
			searchTagOptions(search)
				.then((searchResults) => {
					// Slower earlier requests must not overwrite a newer response.
					if (this.isActive && requestId === this.latestSearch) {
						this.setState({searchResults});
					}
				})
				.catch(logRequestFailure);
		};

		if (immediate) {
			run();
			return;
		}

		this.searchTimer = setTimeout(run, TAG_SEARCH_DELAY);
	}

	/**
	 * Search results with the saved tag pinned in.
	 *
	 * ComboboxControl renders the label of whichever option matches its value, and it refreshes
	 * the options on focus — so the saved tag has to be present in every list it is handed, not
	 * merged in once at mount.
	 *
	 * @return {Array<{label: string, value: string}>} Tag options.
	 */
	tagOptions() {
		const {searchResults, selectedTagOption} = this.state;

		if (!selectedTagOption || searchResults.some((option) => option.value === selectedTagOption.value)) {
			return searchResults;
		}

		return [...searchResults, selectedTagOption];
	}

	render() {
		const {className} = this.state;

		const {attributes, setAttributes, availablePodcasts, isLoadingPodcasts} = this.props;

		const {
			selectedTag,
			limit,
			orderBy,
			order,
			selectedPodcast
		} = attributes;

		const podcastOptions = isLoadingPodcasts
			? [{label: __('Loading…', 'seriously-simple-podcasting'), value: selectedPodcast}]
			: availablePodcasts;

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
								options={podcastOptions}
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
							<ComboboxControl
								id="ssp-playlist-player-tag"
								value={selectedTag}
								options={this.tagOptions()}
								onFilterValueChange={this.searchTags}
								onChange={(selectedTag) => {
									const value = selectedTag === null ? ALL_TAGS_VALUE : selectedTag;
									const chosen = this.tagOptions().find((option) => option.value === value);

									// Pin the new choice so it survives the next options refresh too.
									this.setState({selectedTagOption: chosen || null});
									setAttributes({selectedTag: value});
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

export default withPodcastOptions(EditPlaylistPlayer);
