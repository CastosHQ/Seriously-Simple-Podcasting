import { __ } from '@wordpress/i18n';
import { PluginSidebar } from '@wordpress/edit-post'; // Ensure you're using edit-post for PluginSidebar
import { useSelect, useDispatch } from '@wordpress/data';
import { PanelBody, RadioControl, TextControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import SSPIcon from '../img/ssp-icon.svg';
import classnames from 'classnames';
import ImageUploader from './Sidebar/ImageUploader';
import FileUploader from './Sidebar/FileUploader';
import Dynamo from './Sidebar/Dynamo';
import Promo from './Sidebar/Promo';

const EpisodeMetaSidebar = () => {
	const postMeta = useSelect(( select ) => select('core/editor').getEditedPostAttribute('meta'));

	// Get current meta values
	const episodeTypeMeta = postMeta.episode_type || 'audio';
	const audioFileMeta = postMeta.audio_file || '';
	const coverImageIdMeta = postMeta.cover_image_id || '';
	const coverImageMeta = postMeta.cover_image || '';
	const durationMeta = postMeta.duration || '';
	const filesizeMeta = postMeta.filesize || '';

	// Init local states to manage the meta fields
	const [episodeType, setEpisodeType] = useState(episodeTypeMeta);
	const [audioFile, setAudioFile] = useState(audioFileMeta);
	const [imageId, setImageId] = useState(coverImageIdMeta);
	const [imageUrl, setImageUrl] = useState(coverImageMeta);
	const [duration, setDuration] = useState(durationMeta);
	const [filesize, setFilesize] = useState(filesizeMeta);

	const handleFieldChange = ( fieldName, value, triggerUpdate ) => {
		// Callbacks map
		const setCallbacks = {
			audio_file: setAudioFile,
			episode_type: setEpisodeType,
			cover_image_id: setImageId,
			cover_image: setImageUrl,
			duration: setDuration,
			filesize: setFilesize,
		};

		editPost({
			meta: {
				[ fieldName ]: value,  // Set the value for the post meta field
			},
		});
		setCallbacks[ fieldName ]?.(value); // Set the local value

		// Trigger event to update standard meta fields
		if ( triggerUpdate ) {
			document.dispatchEvent(new CustomEvent('changedSSPGutField', {
				'detail': { field: fieldName, value: value },
			}));
		}
	};

	const removeCoverImage = ( event ) => {
		event.stopPropagation();
		handleFieldChange('cover_image', '', true);
		handleFieldChange('cover_image_id', '', true);
	};

	// Ensure state sync with meta field value
	useEffect(() => {
		const handleChangeSSPField = ( event ) => {
			handleFieldChange(event.detail.field, event.detail.value);
		};

		// Listen the standard meta field changed event
		document.addEventListener('changedSSPField', handleChangeSSPField);

		// Cleanup the event listener when the component unmounts
		return () => {
			document.removeEventListener('changedSSPField', handleChangeSSPField);
		};
	}, []);

	// Use `useDispatch` to update post meta
	const { editPost } = useDispatch('core/editor');

	// Toggle sections
	const [isMediaSectionOpen, setMediaSectionOpen] = useState(true);
	const [isImageSectionOpen, setImageSectionOpen] = useState(true);
	const [isMetaSectionOpen, setMetaSectionOpen] = useState(true);

	return (
		<PluginSidebar
			name="ssp-sidebar"
			title={ __('Seriously Simple Podcasting') }
			className="ssp-episode-meta-sidebar"
			icon={ <img src={ SSPIcon } className="ssp-open" alt="SSP Icon"/> }
		>
			<PanelBody>
				<h2
					className={ classnames('ssp-accordion', { open: isMediaSectionOpen }) }
					onClick={ () => setMediaSectionOpen( ! isMediaSectionOpen) }
					aria-expanded={ isMediaSectionOpen }
				>
					{ __('Episode Media', 'seriously-simple-podcasting') }
				</h2>
				{ isMediaSectionOpen && (
					<div className="ssp-sidebar-content">
						<div className="ssp-sidebar-field-section">
							<h3>{ __('Episode Type', 'seriously-simple-podcasting') }</h3>
							<RadioControl
								selected={ episodeType }
								options={ [
									{ label: __('Audio', 'seriously-simple-podcasting'), value: 'audio' },
									{ label: __('Video', 'seriously-simple-podcasting'), value: 'video' },
								] }
								onChange={ ( value ) => handleFieldChange('episode_type', value, true) }
							/>
						</div>
						<div className="ssp-sidebar-field-section">
							<h3>{ __('Episode File', 'seriously-simple-podcasting') }</h3>

							<FileUploader
								audioUrl={ audioFile }
								onChangeUrl={ ( value ) => handleFieldChange('audio_file', value, true) }
								onSelectAudio={ ( media ) => handleFieldChange('audio_file', media.url, true) }
							/>
							<div className={ 'description' }>
								{ __('Upload audio episode files as MP3 or M4A, video episodes as MP4, or paste the file URL.', 'seriously-simple-podcasting') }
							</div>
						</div>

						<div className="ssp-sidebar-field-section">
							<Promo
								description={ __('Get lower bandwidth fees, file storage, and better stats when hosting with Castos.', 'seriously-simple-podcasting')}
								title={__('Try Castos for free', 'seriously-simple-podcasting')}
								url={'https://castos.com/podcast-hosting-wordpress/?utm_source=ssp&amp;utm_medium=episode-file-box&amp;utm_campaign=upgrade'}
							/>
						</div>

					</div>
				) }
			</PanelBody>

			<PanelBody>
				<h2
					className={ classnames('ssp-accordion', { open: isImageSectionOpen }) }
					onClick={ () => setImageSectionOpen( ! isImageSectionOpen) }
					aria-expanded={ isImageSectionOpen }
				>
					{ __('Episode Image', 'seriously-simple-podcasting') }
				</h2>
				{ isImageSectionOpen && (
					<div className="ssp-sidebar-content">
						<div className="ssp-sidebar-field-section">
							<ImageUploader
								imageId={ imageId }
								imageUrl={ imageUrl }
								onRemoveImage={ removeCoverImage }
								onSelectImage={ ( media ) => {
									handleFieldChange('cover_image', media.url, true);
									handleFieldChange('cover_image_id', media.id.toString() + '', true);
								} }/>
							<div className={ 'description' }>
								{ __('The episode image should be square to display properly in podcasting apps and directories, ' +
									'and should be at least 300x300px in size.',
									'seriously-simple-podcasting') }
							</div>
						</div>
						<div className="ssp-sidebar-field-section">
							<Dynamo/>
						</div>

					</div>
				) }
			</PanelBody>

			<PanelBody>
				<h2
					className={ classnames('ssp-accordion', { open: isMetaSectionOpen }) }
					onClick={ () => setMetaSectionOpen( ! isMetaSectionOpen) }
					aria-expanded={ isMetaSectionOpen }
				>
					{ __('Episode Meta', 'seriously-simple-podcasting') }
				</h2>
				{ isMetaSectionOpen && (
					<div className="ssp-sidebar-content">
						<div className="ssp-sidebar-field-section">
							<h3>{ __('Duration', 'seriously-simple-podcasting') }</h3>

							<TextControl
								__nextHasNoMarginBottom
								value={ duration }
								onChange={ ( value ) => handleFieldChange('duration', value, true) }
							/>

							<div className={ 'description' }>
								{ __('Duration of podcast file for display (calculated automatically if possible).',
									'seriously-simple-podcasting') }
							</div>
						</div>
						<div className="ssp-sidebar-field-section">
							<h3>{ __('File Size', 'seriously-simple-podcasting') }</h3>

							<TextControl
								__nextHasNoMarginBottom
								value={ filesize }
								onChange={ ( value ) => handleFieldChange('filesize', value, true) }
							/>

							<div className={ 'description' }>
								{ __('Size of the podcast file for display (calculated automatically if possible).',
									'seriously-simple-podcasting') }
							</div>
						</div>
					</div>
				) }
			</PanelBody>
		</PluginSidebar>
	);
};

export default EpisodeMetaSidebar;
