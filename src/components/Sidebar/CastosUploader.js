import { TextControl, Button, Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useEffect } from 'react';
import { dispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';

const CastosUploader = ( { audioUrl, onChangeUrl, onFileUploaded, fileName } ) => {

	const [notice, setNotice] = useState('');

	// Run uploader initialization on component mount
	useEffect(() => {
		if ( typeof upload_credentials !== 'undefined' ) {
			initUploader({
				runtimes: 'html5',
				browse_button: 'ssp_gut_select_file',
				multi_selection: false,
				container: 'ssp_gut_upload_container',
				url: upload_credentials.castos_api_url + 'files',
			});
		}
	}, []); // Only run once on component mount

	const sendNotification = ( msg ) => {
		setNotice(msg);
		dispatch('core/notices').createNotice(
			'info',
			msg,
			{
				isDismissible: false,
				id: 'ssp-castos-uploader-notice',
			},
		);
	};

	const removeNotification = () => {
		setNotice('');
		dispatch('core/notices').removeNotice('ssp-castos-uploader-notice');
	};

	/**
	 * Initializes the uploader instance with the given configuration.
	 */
	function initUploader( config ) {
		const uploader = new plupload.Uploader(config);

		uploader.init();

		uploader.bind('FilesAdded', ( up, files ) => {
			const file = files[ 0 ];
			if ( isFileAllowed(file) ) {
				sendNotification(__('Uploading file to Castos Hosting...', 'seriously-simple-podcasting'));
				uploader.start();
			} else {
				sendNotification(__('Invalid file type. Please select an audio or video file.', 'seriously-simple-podcasting'));
				uploader.removeFile(file);
			}
		});

		uploader.bind('BeforeUpload', ( up, file ) => {
			const sanitizedFileName = sanitizeName(file.name);
			uploader.settings.multipart_params = {
				token: upload_credentials.castos_api_token,
				episode_id: upload_credentials.castos_episode_id,
				file_name: sanitizedFileName,
			};
		});

		uploader.bind('Error', ( up, err ) => {
			let msg = err.message;
			try {
				const res = JSON.parse(err.response);
				if ( res && res.message ) {
					msg = res.message;
				}
			} catch ( e ) {}
			alert(__('Error: ', 'seriously-simple-podcasting') + msg);
		});

		uploader.bind('UploadProgress', ( up, file ) => {
			let notice = __(
				'Uploading file to Castos Hosting. You can continue editing this post while the file uploads. Progress: %d%%', // Use %% for the percent symbol
				'seriously-simple-podcasting',
			);
			notice = sprintf(notice, file.percent);
			sendNotification(notice);
		});

		uploader.bind('FileUploaded', ( up, file, result ) => {
			const response = JSON.parse(result.response);
			if ( response.status === 200 ) {
				const uploadedFile = response.file;
				const fileName = up.files[ 0 ].name;
				sendNotification(__('Upload complete!', 'seriously-simple-podcasting'));

				onFileUploaded(uploadedFile, fileName);
			}
			uploader.splice();
		});

		uploader.bind('UploadComplete', () => {
			setTimeout(() => {
				removeNotification();
			}, 5000);
		});
	}

	/**
	 * Checks if the file type is allowed (audio/video).
	 */
	function isFileAllowed( file ) {
		const [fileType] = file.type.split('/');
		return fileType === 'audio' || fileType === 'video';
	}

	/**
	 * Sanitizes the file name.
	 */
	function sanitizeName( name ) {
		const punctuationlessName = name.replace(/[^\w-]+/g, '-');
		return punctuationlessName.replace(/\s+/g, '-');
	}

	return (
		<div className="castos-uploader-component">
			<TextControl
				value={ audioUrl }
				onChange={ onChangeUrl }
				placeholder={ __('Enter audio file URL or upload a file', 'seriously-simple-podcasting') }
			/>

			<div id="ssp_gut_upload_container">
				<Button
					id="ssp_gut_select_file"
					className={ 'button w-full' }
					onClick={ () => {} } isSecondary>
					{ __('Select File', 'seriously-simple-podcasting') }
				</Button>
			</div>

			{ fileName && <div style={ { marginTop: '8px' } }>
				<span className={ 'ssp-episode-details-label' }>
					{ __('Original Filename: ', 'seriously-simple-podcasting') }
				</span>
				<span>{ fileName }</span>
			</div> }

			{ notice && <Notice className={ 'ssp-gut-sidebar-notice' }
								status={ 'info' }
								isDismissible={ false }>
				<p>{ notice }</p>
			</Notice> }
		</div>
	);
};

export default CastosUploader;
