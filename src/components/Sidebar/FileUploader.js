import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const FileUploader = ( { audioUrl, onChangeUrl, onSelectAudio } ) => {

	return (
		<div className="ssp-file-uploader">
			{/* Text input for the audio URL */ }
			<TextControl
				value={ audioUrl }
				onChange={ onChangeUrl }
				placeholder={ __('Enter audio file URL or upload a file', 'seriously-simple-podcasting') }
			/>

			{/* Media upload button for selecting an audio file */ }
			<MediaUploadCheck>
				<MediaUpload
					onSelect={ onSelectAudio }
					allowedTypes={ ['audio'] }
					render={ ( { open } ) => (
						<Button
							className={ 'button w-full' }
							onClick={ open }>
							{ __('Upload File', 'seriously-simple-podcasting') }
						</Button>
					) }
				/>
			</MediaUploadCheck>
		</div>
	);
};

export default FileUploader;
