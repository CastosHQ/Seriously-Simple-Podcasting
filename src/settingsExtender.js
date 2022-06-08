import {__} from '@wordpress/i18n';

const {addFilter} = wp.hooks;
const {createHigherOrderComponent} = wp.compose;
const {Fragment} = wp.element;
const {InspectorControls} = wp.blockEditor;
const {PanelBody, ToggleControl} = wp.components;

/**
* The same list as in ssp_get_the_feed_item_content() function
* */
const enabledBlocks = [
	'core/freeform',
	'core/heading',
	'core/html',
	'core/list',
	'core/media-text',
	'core/paragraph',
	'core/preformatted',
	'core/pullquote',
	'core/quote',
	'core/table',
	'core/verse',
	'core/columns',
	'core/block',
	'create-block/castos-transcript',
];

const feedHiddenByDefaultBlocks = [
	'create-block/castos-transcript',
];

const addAttributes = ( settings, name ) => {
	// Do nothing if it's another block than our defined ones.
	if ( ! enabledBlocks.includes( name ) ) {
		return settings;
	}

	if (!settings.attributes.hasOwnProperty('hideFromFeed')) {
		settings.attributes.hideFromFeed = {
			type: "boolean",
			default: null,
		};
	}

	return settings;
};

addFilter( 'blocks.registerBlockType', 'extend-block/ssp-block-settings', addAttributes )


const extendBlockSettings = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        // Do nothing if it's not the needed block
		if ( ! enabledBlocks.includes( props.name ) ) {
			return (
				<BlockEdit {...props} />
			);
		}

		// Workaround: we change the value dynamically here to save the attributes in the database for PHP.
		// Unfortunately, default attributes are not saved.
		if (feedHiddenByDefaultBlocks.includes(props.name) && null === props.attributes.hideFromFeed) {
			props.attributes.hideFromFeed = true;
		}

        return (
            <Fragment>
                <BlockEdit {...props} />
                <InspectorControls>
                    <PanelBody
                        title={__('Feed Settings')}
                        initialOpen={true}
                    >
                        <ToggleControl
                            label={__('Hide From Podcast RSS Feed')}
                            checked={props.attributes.hideFromFeed}
                            onChange={(val) => {
                                props.setAttributes({
                                    hideFromFeed: val,
                                });
                            }}
                        />
                    </PanelBody>
                </InspectorControls>
            </Fragment>
        );
    };
});

addFilter('editor.BlockEdit', 'extend-block/ssp-block-settings', extendBlockSettings);
