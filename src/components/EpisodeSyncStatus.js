import { useDispatch, useSelect } from '@wordpress/data'
import { useRef} from '@wordpress/element'
import apiFetch from '@wordpress/api-fetch';

const EpisodeSyncStatus = () => {
	if(!isEpisode(useSelect((select) => select('core/editor')))){
		return;
	}

	const isSavingPost = useSelect((select) => select('core/editor').isSavingPost(), [])
	const previousIsSaving = useRef(isSavingPost)
	const currentPost = useSelect((select) => select('core/editor').getCurrentPost(), [])
	const { createWarningNotice, createInfoNotice } = useDispatch('core/notices')
	const isPostJustSaved = previousIsSaving.current && ! isSavingPost
	previousIsSaving.current = isSavingPost

	if (isPostJustSaved) {
		setTimeout(async () => {
			await displaySyncData(currentPost)
		}, 1000)
	}

	const displaySyncData = async (currentPost) => {
		try {
			const updatedPost = await fetchPostData(currentPost);

			if (updatedPost && updatedPost[ 'episode_data' ]) {
				let syncStatus = updatedPost[ 'episode_data' ].syncStatus
				if ( ! syncStatus ||  'none' === syncStatus.status ) {
					return; // Do not show redundant "Not Synced yet" status.
				}
				displayNotice(syncStatus)
				updateSyncStatus(syncStatus)
			}

		} catch (error) {
			console.error('Error:', error)
			return null
		}
	}

	const updateSyncStatus = (syncStatus) => {
		let syncLabel = document.querySelector('.js-ssp-sync-label')
		syncLabel.textContent = syncStatus.title
		syncLabel.setAttribute('title', syncStatus.title)
		syncLabel.classList.remove('failed', 'synced', 'syncing')
		syncLabel.classList.add(syncStatus.status)

		let syncMessage = document.querySelector('.js-ssp-sync-message')
		syncMessage.textContent = syncStatus.message

		let description = document.querySelector('.js-ssp-sync-description')
		description.textContent = syncStatus.error
	}

	const displayNotice = (syncStatus) => {
		const noticeId = 'ssp-sync-notice'
		let msg = syncStatus.message
		if ( ! syncStatus.isSynced) {
			if (syncStatus.error) {
				msg += ' ' + syncStatus.error
			}
			createWarningNotice(msg, { id: noticeId })
		} else {
			createInfoNotice(msg, { type: 'snackbar', id: noticeId })
		}
	}

	const getRestBaseForPostType = async (postType)=> {
		try {
			// Fetch the post types data from the REST API
			const types = await apiFetch({ path: '/wp/v2/types' });

			// Check if the specified post type exists in the fetched data
			if (types[postType] && types[postType].rest_base) {
				console.log('Returning rest base:', types[postType].rest_base);
				return types[postType].rest_base;
			} else {
				console.warn(`Post type "${postType}" not found.`);
				return null;
			}
		} catch (error) {
			console.error('There was a problem with the fetch operation:', error);
			return null;
		}
	}

	const fetchPostData = async (post) => {
		const restBase = await getRestBaseForPostType(post.type)
		return await apiFetch({ path: `/wp/v2/${restBase}/${post.id}` })
	}
}

const isEpisode = (editor) => {
	if(!editor){
		return false;
	}
	const sspPostTypes = sspAdmin.sspPostTypes
	let currentPost = editor.getCurrentPost();
	return (sspPostTypes instanceof Array) && currentPost && sspPostTypes.includes(currentPost.type)
}



export default EpisodeSyncStatus
