import apiFetch from '@wordpress/api-fetch';
import {addQueryArgs} from '@wordpress/url';
import {__} from '@wordpress/i18n';

/**
 * Fetches the podcast and tag option lists the block editor needs.
 *
 * These lists used to travel as block attribute defaults, which meant WordPress ran a term query
 * every time it serialized block settings for an editor page — on every editor, site editor and
 * widgets load, whether or not the block was in use. On tag-heavy sites that query grew large
 * enough for hosts to kill it. Fetching here keeps the cost with the editor that asked for it.
 */

/** Option value standing for "every podcast". */
export const ALL_PODCASTS_VALUE = -1;

/** Option value standing for "every tag". */
export const ALL_TAGS_VALUE = '';

/** Largest page the REST API will return for terms. */
const PODCASTS_PER_REQUEST = 100;

/**
 * Page ceiling for the podcast list.
 *
 * The list this replaced was unbounded, so paging is followed rather than truncated — but not
 * forever, since the picker renders every option it is given.
 */
const MAX_PODCAST_PAGES = 20;

/** Tags are searched, never listed in full — this bounds one page of results. */
const TAGS_PER_REQUEST = 50;

const allPodcastsOption = () => ({
	label: __('-- All --', 'seriously-simple-podcasting'),
	value: ALL_PODCASTS_VALUE,
});

const allTagsOption = () => ({
	label: __('-- All --', 'seriously-simple-podcasting'),
	value: ALL_TAGS_VALUE,
});

const sspAdmin = () => (typeof window !== 'undefined' && window.sspAdmin) || {};

/**
 * The podcast taxonomy's name, REST base and REST namespace are all filterable, so the whole
 * route is passed in from PHP rather than reassembled here.
 */
const seriesRoute = () => sspAdmin().seriesRestRoute || '/wp/v2/series';

/**
 * Term names arrive ready to display.
 *
 * The default podcast is already suffixed with "(default)" server side, by the
 * rest_prepare_{taxonomy} filter in Rest_Api_Controller — decorating again here would double it.
 */
const toPodcastOption = (term) => ({
	label: term.name,
	value: term.id,
});

const toTagOption = (term) => ({
	label: term.name,
	value: term.slug,
});

/**
 * Reports a failed request without breaking the editor.
 *
 * The pickers degrade to an empty list on failure; swallowing the error silently would also hide
 * genuine bugs thrown further down the promise chain.
 *
 * @param {Error} error Rejection reason.
 */
export function logRequestFailure(error) {
	if (window.console) {
		window.console.error(error);
	}
}

/**
 * Fetches one page of podcast terms.
 *
 * @param {number} page Page number, 1-based.
 *
 * @return {Promise<Array>} Terms.
 */
function fetchPodcastPage(page) {
	return apiFetch({
		path: addQueryArgs(seriesRoute(), {
			page,
			per_page: PODCASTS_PER_REQUEST,
			orderby: 'name',
			order: 'asc',
			_fields: 'id,name',
		}),
	});
}

/**
 * Fetches every podcast as a select option, prefixed with the "all podcasts" choice.
 *
 * @return {Promise<Array<{label: string, value: number}>>} Podcast options.
 */
export function fetchPodcastOptions() {
	// An out-of-range page is answered with an empty 200 by the terms controller, so the short-page
	// check below ends pagination on its own and every rejection here is a genuine failure.
	const collect = (page, collected) => fetchPodcastPage(page).then((terms) => {
		const all = [...collected, ...terms];

		// A short page is the last page; otherwise keep going until the ceiling.
		if (terms.length < PODCASTS_PER_REQUEST || page >= MAX_PODCAST_PAGES) {
			return all;
		}

		return collect(page + 1, all);
	});

	return collect(1, []).then((terms) => [allPodcastsOption(), ...terms.map(toPodcastOption)]);
}

/**
 * Searches tags by name, returning at most one page of matches.
 *
 * @param {string} search Search term; an empty string returns the first page alphabetically.
 *
 * @return {Promise<Array<{label: string, value: string}>>} Tag options.
 */
export function searchTagOptions(search) {
	return apiFetch({
		path: addQueryArgs('/wp/v2/tags', {
			search: search || undefined,
			per_page: TAGS_PER_REQUEST,
			orderby: 'name',
			order: 'asc',
			_fields: 'name,slug',
		}),
	}).then((terms) => [allTagsOption(), ...terms.map(toTagOption)]);
}

/**
 * Resolves a single tag by slug so a saved selection shows its name rather than its slug.
 *
 * @param {string} slug Tag slug stored on the block.
 *
 * @return {Promise<{label: string, value: string}|null>} Tag option, or null when the tag is gone.
 */
export function fetchTagOptionBySlug(slug) {
	if (!slug) {
		return Promise.resolve(null);
	}

	return apiFetch({
		path: addQueryArgs('/wp/v2/tags', {slug, _fields: 'name,slug'}),
	}).then((terms) => (terms.length ? toTagOption(terms[0]) : null));
}
