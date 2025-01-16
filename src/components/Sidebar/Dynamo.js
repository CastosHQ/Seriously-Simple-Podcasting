import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import Promo from './Promo';

const Dynamo = () => {
	const postId = useSelect(( select ) => select('core/editor').getCurrentPostId());
	const taxonomySlug = 'series';

	// Get the selected series IDs
	const selectedSeries = useSelect(( select ) =>
		select('core/editor').getEditedPostAttribute(taxonomySlug),
	);

	// Get all series terms
	const seriesTerms = useSelect(( select ) =>
		select('core').getEntityRecords('taxonomy', taxonomySlug, {
			object_id: postId,
		})
	);

	// Filter selected terms based on the selected series IDs
	const selectedTerms = seriesTerms
		? seriesTerms.filter(( term ) => selectedSeries.includes(term.id))
		: [];

	const postTitle = useSelect(( select ) =>
		select('core/editor').getEditedPostAttribute('title'),
	);

	const [generatedLink, setGeneratedLink] = useState('');

	useEffect(() => {
		const generateLink = () => {
			const title = postTitle || __('My new episode', 'seriously-simple-podcasting'); // Use an empty string if no title is set

			// Get the first selected series term or an empty string
			const series = selectedTerms.length > 0 ?
				selectedTerms[ 0 ].name :
				__('My Podcast Title', 'seriously-simple-podcasting');

			// Create the URL with title and series encoded
			const url =
				`https://dynamo.castos.com/podcast-covers?utm_source=WordPress&utm_medium=Plugin&utm_campaign=dashboard&t=${
					encodeURIComponent(title) }&s=${ encodeURIComponent(series.replace(' (default)', '')) }`;

			setGeneratedLink(url);
		};

		// Call the generateLink function whenever title or selected term changes
		generateLink();
	}, [postTitle, selectedTerms]); // Re-run this effect when postTitle or selectedSeriesTerms changes

	return generatedLink && (
		<Promo
			description={__('Create an episode cover image with our free tool Dynamo.', 'seriously-simple-podcasting')}
			title={__('Create Image with Dynamo', 'seriously-simple-podcasting')}
			url={generatedLink}
		/>
	);
};

export default Dynamo;
