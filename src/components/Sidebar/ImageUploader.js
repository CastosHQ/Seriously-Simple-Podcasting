import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { IconButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ImageUploader = ({ imageId, imageUrl, onRemoveImage, onSelectImage }) => {

	return (
		<div className="ssp-image-uploader">
			<MediaUploadCheck>
				<MediaUpload
					onSelect={onSelectImage}
					allowedTypes={['image']}
					value={imageId}
					render={({ open }) => (
						<div className="image-wrapper" onClick={open} style={{ cursor: 'pointer', position: 'relative' }}>
							{/* Show placeholder or selected image */}
							{!imageUrl ? (
								<div className={'no-image'}>
									<span>{__('Upload Cover Image', 'seriously-simple-podcasting')}</span>
								</div>
							) : (
								<img
									src={imageUrl}
									alt={__('Selected Image', 'seriously-simple-podcasting')}
									style={{ maxWidth: '100%' }}
								/>
							)}

							{/* If an image is selected, show the remove (close) button */}
							{imageUrl && (
								<IconButton
									icon="no-alt"
									label={__('Remove image', 'seriously-simple-podcasting')}
									onClick={onRemoveImage}
									className={'remove-image'}
								/>
							)}
						</div>
					)}
				/>
			</MediaUploadCheck>
		</div>
	);
};

export default ImageUploader;
