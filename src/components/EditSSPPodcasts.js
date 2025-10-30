import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {InspectorControls} from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	SelectControl,
	ToggleControl,
	ColorPicker,
	__experimentalNumberControl as NumberControl,
	Tooltip,
	CheckboxControl,
	Dropdown,
	Button,
	TextControl
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

// Reusable color picker field
const ColorField = ({ id, label, value, onChange }) => (
	<div style={{ marginBottom: '32px' }}>
		<label htmlFor={id} style={{ display: 'block', marginBottom: '8px', fontWeight: '500' }}>
			{label}
		</label>
		<ColorPicker color={value} onChangeComplete={(color) => onChange(color.hex)} disableAlpha />
	</div>
);

// Podcasts multi-select dropdown
const PodcastsSelector = ({ availablePodcasts, ids, setAttributes }) => {
	const list = Array.isArray(availablePodcasts) ? availablePodcasts : [];
	const allOpt = list.find((o) => String(o.value) === '-1' || String(o.value) === '') || { value: -1 };
	const allVal = String(allOpt.value);

	const toggleId = (idValue, checked) => {
		let next = Array.isArray(ids) ? ids.slice() : [];
		const isAllOption = idValue === '-1' || idValue === '' || idValue === allVal;

		if (isAllOption) {
			next = checked ? [ allVal, ...list.filter((o) => String(o.value) !== allVal).map((o) => String(o.value)) ] : [];
		} else {
			if (checked) {
				if (!next.includes(idValue)) next = [ ...next, idValue ];
			} else {
				next = next.filter((v) => v !== idValue && v !== allVal);
			}
		}

		if (next.length === 0) {
			const firstReal = list.find((o) => String(o.value) !== allVal);
			if (firstReal) next = [ String(firstReal.value) ];
		}

		setAttributes({ ids: next });
	};

	return (
		<Dropdown
			position="bottom left"
			renderToggle={({ isOpen, onToggle }) => (
				<Button variant="secondary" onClick={onToggle} aria-expanded={isOpen}>
					{Array.isArray(ids) && ids.length > 0
						? __('Selected: ', 'seriously-simple-podcasting') + ids.length
						: __('All podcasts', 'seriously-simple-podcasting')}
				</Button>
			)}
			renderContent={() => (
				<div style={{ padding: '8px 12px', maxHeight: '260px', overflow: 'auto', minWidth: '240px' }}>
					{list.map((opt) => {
						const idValue = String(opt.value);
						const isChecked = Array.isArray(ids) && ids.includes(idValue);
						return (
							<CheckboxControl
								key={`ssp-podcast-opt-${idValue}`}
								label={opt.label}
								checked={isChecked}
								onChange={(checked) => toggleId(idValue, checked)}
							/>
						);
					})}
				</div>
			)}
		/>
	);
};

// Content Panel grouping
const ContentPanel = ({
    ids,
    availablePodcasts,
    sort_by,
    sort,
    columns,
    clickable,
    setAttributes,
    dragIndex,
    dragOverIndex,
    onDragStartItem,
	onDragOverItem,
    onDropItem,
    onDragEnd,
    moveItem,
}) => {
    const sortByOptions = [
        { label: __('ID', 'seriously-simple-podcasting'), value: 'id' },
        { label: __('Name', 'seriously-simple-podcasting'), value: 'name' },
        { label: __('Episode Count', 'seriously-simple-podcasting'), value: 'episode_count' },
        { label: __('Manual order', 'seriously-simple-podcasting'), value: 'manual' },
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

    // Detect global All value for manual list
    const apList = Array.isArray(availablePodcasts) ? availablePodcasts : [];
    const allOptGlobal = apList.find((o) => String(o.value) === '-1' || String(o.value) === '') || { value: -1 };
    const allValGlobal = String(allOptGlobal.value);

    return (
        <PanelBody key="ssp-podcasts-content" title={__('Content', 'seriously-simple-podcasting')} initialOpen={true}>
            <PanelRow>
                <label htmlFor="ssp-podcasts-ids">
                    {__('Podcasts', 'seriously-simple-podcasting')}
                    <Tooltip text={__('Select one or more podcasts.', 'seriously-simple-podcasting')}>
                        <span className="dashicon dashicons dashicons-info"></span>
                    </Tooltip>
                </label>
            </PanelRow>

            <PodcastsSelector availablePodcasts={availablePodcasts} ids={ids} setAttributes={setAttributes} />

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

            {sort_by !== 'manual' && (
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
            )}

            <PanelRow>
                <label htmlFor="ssp-podcasts-columns">
                    {__('Columns', 'seriously-simple-podcasting')}
                </label>
                <NumberControl
                    id="ssp-podcasts-columns"
                    value={columns}
                    onChange={(value) => setAttributes({ columns: parseInt(value, 10) || 1 })}
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

            {sort_by === 'manual' && (
                <ManualOrderList
                    ids={ids}
                    availablePodcasts={availablePodcasts}
                    allValue={allValGlobal}
                    dragIndex={dragIndex}
                    dragOverIndex={dragOverIndex}
                    onDragStartItem={onDragStartItem}
					onDragOverItem={onDragOverItem}
                    onDropItem={onDropItem}
                    onDragEnd={onDragEnd}
                    moveItem={moveItem}
                />
            )}
        </PanelBody>
    );
};

// Display Panel grouping
const DisplayPanel = ({
    show_description,
    show_episode_count,
    show_button,
    button_text,
    description_words,
    description_chars,
    setAttributes,
}) => (
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
);

// Styling Panel grouping
const StylingPanel = ({
    background,
    background_hover,
    title_color,
    episode_count_color,
    description_color,
    button_color,
    button_hover_color,
    button_text_color,
    show_button,
    setAttributes,
}) => (
    <PanelBody key="ssp-podcasts-styling" title={__('Styling', 'seriously-simple-podcasting')}>
        <ColorField id="ssp-podcasts-background" label={__('Background Color', 'seriously-simple-podcasting')} value={background} onChange={(hex) => setAttributes({ background: hex })} />
        <ColorField id="ssp-podcasts-background-hover" label={__('Background Hover Color', 'seriously-simple-podcasting')} value={background_hover} onChange={(hex) => setAttributes({ background_hover: hex })} />
        <ColorField id="ssp-podcasts-title-color" label={__('Title Color', 'seriously-simple-podcasting')} value={title_color} onChange={(hex) => setAttributes({ title_color: hex })} />
        <ColorField id="ssp-podcasts-episode-count-color" label={__('Episode Count Color', 'seriously-simple-podcasting')} value={episode_count_color} onChange={(hex) => setAttributes({ episode_count_color: hex })} />
        <ColorField id="ssp-podcasts-description-color" label={__('Description Color', 'seriously-simple-podcasting')} value={description_color} onChange={(hex) => setAttributes({ description_color: hex })} />
        {show_button === 'true' && (
            <>
                <ColorField id="ssp-podcasts-button-color" label={__('Button Color', 'seriously-simple-podcasting')} value={button_color} onChange={(hex) => setAttributes({ button_color: hex })} />
                <ColorField id="ssp-podcasts-button-hover-color" label={__('Button Hover Color', 'seriously-simple-podcasting')} value={button_hover_color} onChange={(hex) => setAttributes({ button_hover_color: hex })} />
                <ColorField id="ssp-podcasts-button-text-color" label={__('Button Text Color', 'seriously-simple-podcasting')} value={button_text_color} onChange={(hex) => setAttributes({ button_text_color: hex })} />
            </>
        )}
    </PanelBody>
);

// Manual order list when sort_by === 'manual'
const ManualOrderList = ({
	ids,
	availablePodcasts,
	allValue,
	dragIndex,
	dragOverIndex,
	onDragStartItem,
	onDragOverItem,
	onDropItem,
	onDragEnd,
	moveItem,
}) => {
	const list = Array.isArray(availablePodcasts) ? availablePodcasts : [];
	const getLabelById = (id) => {
		const found = list.find((o) => String(o.value) === String(id));
		return found ? found.label : String(id);
	};

	return (
		<div style={{ marginTop: '8px', marginBottom: '8px' }}>
			<div style={{ fontWeight: 500, margin: '6px 0' }}>{__('Manual order', 'seriously-simple-podcasting')}</div>
			{Array.isArray(ids) && ids.length > 0 ? (
				<ol style={{ listStyle: 'none', padding: 0, margin: 0 }}>
					{ids.filter((selectedId) => String(selectedId) !== allValue).map((selectedId) => {
						const actualIndex = ids.indexOf(selectedId);
						const allIndex = ids.indexOf(allValue);
						const minIndex = allIndex >= 0 ? Math.max(1, allIndex + 1) : 0;
						const isDragOver = dragOverIndex === actualIndex;
						const isDragging = dragIndex === actualIndex;
						return (
							<>
								<div style={{
									height: isDragOver ? '2px' : '0px',
									background: isDragOver ? 'var(--wp-admin-theme-color, #1e78ff)' : 'transparent',
									margin: isDragOver ? '4px 0' : '0'
								}} />
								<li
									key={`ssp-selected-${selectedId}`}
									style={{
										display: 'flex',
										alignItems: 'center',
										gap: '8px',
										padding: '6px 8px',
										border: '1px solid #ddd',
										borderRadius: '4px',
										marginBottom: '6px',
										background: isDragging ? '#eef5ff' : '#fff',
										cursor: 'move'
									}}
									draggable
									onDragStart={() => onDragStartItem(actualIndex)}
							onDragOver={(e) => { e.preventDefault(); if (onDragOverItem) { onDragOverItem(actualIndex); } }}
									onDrop={() => onDropItem(actualIndex)}
									onDragEnd={onDragEnd}
								>
									<span className="dashicons dashicons-move" aria-hidden="true" />
									<span style={{ flex: 1 }}>{getLabelById(selectedId)}</span>
									<Button
										isSmall
										variant="secondary"
										aria-label={__('Move up', 'seriously-simple-podcasting')}
										onClick={() => moveItem(actualIndex, Math.max(minIndex, actualIndex - 1))}
										disabled={actualIndex <= minIndex}
									>
										▲
									</Button>
									<Button
										isSmall
										variant="secondary"
										aria-label={__('Move down', 'seriously-simple-podcasting')}
										onClick={() => moveItem(actualIndex, Math.min(ids.length - 1, actualIndex + 1))}
										disabled={actualIndex === ids.length - 1}
									>
										▼
									</Button>
									{null}
								</li>
							</>
						);
					})}
				</ol>
			) : (
				<div style={{ color: '#555' }}>
					{__('No podcasts selected. Use the selector above to choose podcasts to order.', 'seriously-simple-podcasting')}
				</div>
			)}
		</div>
	);
};

class EditSSPPodcasts extends Component {
	constructor({className}) {
		super(...arguments);
		this.state = {
			className,
			availablePodcasts: [],
			dragIndex: null,
			dragOverIndex: null,
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

		// Ensure default selection includes "-- All --" and every podcast on insert
		const { attributes, setAttributes } = this.props;

		const available = Array.isArray(attributes.availablePodcasts) ? attributes.availablePodcasts : [];
		const idsSelected = Array.isArray(attributes.ids) ? attributes.ids : [];

		// Detect the special “All” option and normalize its value as a string
		const allOption = available.find((o) => String(o.value) === '-1' || String(o.value) === '') || { value: -1 };
		const allValue = String(allOption.value);

		// Readability guards
		const isUninitialized = idsSelected.length === 0;
		const isOnlyAllSelected = idsSelected.length === 1 && (idsSelected[0] === allValue);

		if (isUninitialized || isOnlyAllSelected) {
			const realIds = available
				.filter((o) => String(o.value) !== allValue)
				.map((o) => String(o.value));

			setAttributes({ ids: [ allValue, ...realIds ] });
		}
	}

	componentDidUpdate(prevProps) {
		const prevAP = prevProps.attributes && Array.isArray(prevProps.attributes.availablePodcasts) ? prevProps.attributes.availablePodcasts : [];
		const currAP = this.props.attributes && Array.isArray(this.props.attributes.availablePodcasts) ? this.props.attributes.availablePodcasts : [];
		if (prevAP !== currAP) {
			const { attributes, setAttributes } = this.props;
		const allOpt = currAP.find((o) => String(o.value) === '-1' || String(o.value) === '') || { value: -1 };
			const allVal = String(allOpt.value);
			if (!Array.isArray(attributes.ids) || attributes.ids.length === 0 || 
				(attributes.ids.length === 1 && (attributes.ids[0] === '-1' || attributes.ids[0] === '')) ) {
				const realIds = currAP.filter((o) => String(o.value) !== allVal).map((o) => String(o.value));
				setAttributes({ ids: [ allVal, ...realIds ] });
			}
		}
	}

	render() {
		const {className} = this.state;
		const {attributes, setAttributes} = this.props;

		const {
			ids,
			availablePodcasts,
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
			{ label: __('Manual order', 'seriously-simple-podcasting'), value: 'manual' },
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

		const getLabelById = (id) => {
			const list = Array.isArray(availablePodcasts) ? availablePodcasts : [];
			const found = list.find((o) => String(o.value) === String(id));
			return found ? found.label : String(id);
		};

		const onDragStartItem = (index) => {
			this.setState({ dragIndex: index, dragOverIndex: null });
		};

		const onDropItem = (targetIndex) => {
			const from = this.state.dragIndex;
			if (from === null || from === undefined || from === targetIndex) return;
			const current = Array.isArray(ids) ? ids.slice() : [];
			const item = current.splice(from, 1)[0];
			// Adjust insertion index when dragging downwards because the list shrinks after removal
			const insertIndex = from < targetIndex ? Math.max(0, targetIndex - 1) : targetIndex;
			current.splice(insertIndex, 0, item);
			setAttributes({ ids: current });
			this.setState({ dragIndex: null, dragOverIndex: null });
		};

		const moveItem = (from, to) => {
			const current = Array.isArray(ids) ? ids.slice() : [];
			if (from < 0 || to < 0 || from >= current.length || to >= current.length) return;
			const item = current.splice(from, 1)[0];
			current.splice(to, 0, item);
			setAttributes({ ids: current });
		};

		// Detect the "All" option value from availablePodcasts by value (-1 or empty)
		const apList = Array.isArray(availablePodcasts) ? availablePodcasts : [];
		const allOptGlobal = apList.find((o) => String(o.value) === '-1' || String(o.value) === '') || { value: -1 };
		const allValGlobal = String(allOptGlobal.value);

		const controls = (
			<InspectorControls key="inspector-controls">
				<div className="ssp-controls ssp-edit-ssp-podcasts">
					<ContentPanel
						ids={ids}
						availablePodcasts={availablePodcasts}
						sort_by={sort_by}
						sort={sort}
						columns={columns}
						clickable={clickable}
						setAttributes={setAttributes}
						dragIndex={this.state.dragIndex}
						dragOverIndex={this.state.dragOverIndex}
						onDragStartItem={onDragStartItem}
						onDragOverItem={(index) => this.setState({ dragOverIndex: index })}
						onDropItem={onDropItem}
						onDragEnd={() => this.setState({ dragIndex: null, dragOverIndex: null })}
						moveItem={moveItem}
					/>

					<DisplayPanel
						show_description={show_description}
						show_episode_count={show_episode_count}
						show_button={show_button}
						button_text={button_text}
						description_words={description_words}
						description_chars={description_chars}
						setAttributes={setAttributes}
					/>

					<StylingPanel
						background={background}
						background_hover={background_hover}
						title_color={title_color}
						episode_count_color={episode_count_color}
						description_color={description_color}
						button_color={button_color}
						button_hover_color={button_hover_color}
						button_text_color={button_text_color}
						show_button={show_button}
						setAttributes={setAttributes}
					/>
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
