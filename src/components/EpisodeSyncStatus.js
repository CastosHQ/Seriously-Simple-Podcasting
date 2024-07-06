import { useDispatch, useSelect } from '@wordpress/data'
import { useRef} from '@wordpress/element'

const EpisodeSyncStatus = () => {

	const isSavingPost = useSelect((select) => select('core/editor').isSavingPost(), [])
	const previousIsSaving = useRef(isSavingPost)
	const currentPost = useSelect((select) => select('core/editor').getCurrentPost(), [])
	const { createWarningNotice } = useDispatch('core/notices')

	const displaySyncData = async (currentPost) => {
		try {
			const updatedPost = await fetchPostData(currentPost.id);

			if (updatedPost && updatedPost[ 'episode_data' ]) {
				let syncStatus = updatedPost[ 'episode_data' ].syncStatus
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
			createWarningNotice(msg, { type: 'snackbar', id: noticeId })
		}
	}

	const fetchPostData = async (postId) => {
		const response = await fetch(`/wp-json/wp/v2/podcast/${ postId }`)
		return await response.json()
	}

	const isPostJustSaved = previousIsSaving.current && ! isSavingPost

	if (isPostJustSaved) {
		setTimeout(async () => {
			await displaySyncData(currentPost)
		}, 1000)
	}

	previousIsSaving.current = isSavingPost
}



export default EpisodeSyncStatus
