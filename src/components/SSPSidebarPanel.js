import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import FileIsUploadedSvg from '../img/file-is-uploaded.svg';
import FileNotUploadedSvg from '../img/file-not-uploaded.svg';

const SSPSidebarPanel = () => {
	const editor = useSelect(( select ) => select('core/editor'));
	if ( ! sspAdmin.sspPostTypes.includes(editor.getCurrentPostType()) ) {
		return;
	}

	const [isSSPSectionOpen, setSSPSectionOpen] = useState(true);
	const postMeta = editor.getEditedPostAttribute('meta');

	console.log('postMeta:', postMeta);
	const fileIsUploaded = !! postMeta.audio_file;
	console.log('fileIsUploaded:', fileIsUploaded);

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
							onClick={ () => {
								document.querySelectorAll('.ssp-open').forEach(function ( element ) {
									element.click();
								});
							} }
						>{ __('Manage your Episode', 'seriously-simple-podcasting') }
						</Button>
					</div>
				) }
			</div>
		</PluginPostStatusInfo>
	);
};

export default SSPSidebarPanel;
