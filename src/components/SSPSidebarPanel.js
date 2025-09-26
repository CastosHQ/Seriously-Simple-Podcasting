import { PluginPostStatusInfo } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useState, useRef, useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import FileIsUploadedSvg from '../img/file-is-uploaded.svg';
import FileNotUploadedSvg from '../img/file-not-uploaded.svg';
import { useDispatch, useSelect } from '@wordpress/data';

const SSPSidebarPanel = () => {
	const editor = useSelect(( select ) => select('core/editor'));
	if ( ! sspAdmin.sspPostTypes.includes(editor.getCurrentPostType()) ) {
		return;
	}

	const [isSSPSectionOpen, setSSPSectionOpen] = useState(true);
	
	// Track post save state to refresh meta data
	const isSavingPost = useSelect((select) => select('core/editor').isSavingPost(), []);
	const previousIsSaving = useRef(isSavingPost);
	const isPostJustSaved = previousIsSaving.current && !isSavingPost;
	previousIsSaving.current = isSavingPost;
	
	// Force re-render when post is saved to refresh meta data
	const [refreshTrigger, setRefreshTrigger] = useState(0);
	
	useEffect(() => {
		if (isPostJustSaved) {
			// Trigger a re-render to refresh the post meta data
			setRefreshTrigger(prev => prev + 1);
		}
	}, [isPostJustSaved]);
	
	const postMeta = editor.getEditedPostAttribute('meta');

	if ( ! postMeta ) {
		return;
	}

	const fileIsUploaded = !! postMeta.audio_file;

	const { openGeneralSidebar } = useDispatch('core/edit-post');

	const openSSPSidebar = () => {
		openGeneralSidebar('ssp-episode-meta-sidebar/ssp-episode-meta-sidebar'); // Sidebar slug
	};

	return (
		<PluginPostStatusInfo>
			<div className={ 'ssp-sidebar-panel' }>
				<h2
					className={ classnames('ssp-accordion', { open: isSSPSectionOpen }) }
					onClick={ () => setSSPSectionOpen( ! isSSPSectionOpen) }
					aria-expanded={ isSSPSectionOpen }
				>
					{ __('Seriously Simple Podcasting', 'seriously-simple-podcasting') }
					<span>
						<svg viewBox="0 0 24 24"
							 xmlns="http://www.w3.org/2000/svg" width="24"
							 height="24" className="components-panel__arrow"
							 aria-hidden="true" focusable="false"><path
							d="M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z"></path></svg>
					</span>
				</h2>
				{ isSSPSectionOpen && (
					<div className="ssp-sidebar-content">
						{ fileIsUploaded &&
							<div className={ 'ssp-file-info uploaded' }>
								<img src={ FileIsUploadedSvg }
									 className="ssp-file-upload-status"
									 alt={ __('Episode file uploaded', 'seriously-simple-podcasting') }/>
								<span>{ __('Episode file uploaded', 'seriously-simple-podcasting') }</span>
							</div> }

						{ ! fileIsUploaded &&
							<div className={ 'ssp-file-info not-uploaded' }>
								<img src={ FileNotUploadedSvg }
									 className="ssp-file-upload-status"
									 alt={ __('Episode file missing', 'seriously-simple-podcasting') }/>
								<span>{ __('Episode file missing', 'seriously-simple-podcasting') }</span>
							</div> }

						<Button
							className={ 'ssp-open-meta-btn' }
							onClick={ openSSPSidebar }
						>{ __('Manage your Episode', 'seriously-simple-podcasting') }
						</Button>
					</div>
				) }
			</div>
		</PluginPostStatusInfo>
	);
};

export default SSPSidebarPanel;
