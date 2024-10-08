import { __ } from '@wordpress/i18n';
import { PluginSidebar } from '@wordpress/edit-post'; // Ensure you're using edit-post for PluginSidebar
import { useSelect, useDispatch } from '@wordpress/data';
import { PanelBody, RadioControl, TextControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import SSPIcon from '../img/ssp-icon.svg';
import classnames from 'classnames';

const EpisodeMetaSidebar = () => {
	const postMeta = useSelect(( select ) => select('core/editor').getEditedPostAttribute('meta'));

	// Get current meta values
	const episodeTypeMeta = postMeta.episode_type || 'audio';
	const audioFileMeta = postMeta.audio_file || '';

	// Init local states to manage the meta fields
	const [episodeType, setEpisodeType] = useState(episodeTypeMeta);
	const [audioFile, setAudioFile] = useState(audioFileMeta);

	// Callbacks map
	const callbacks = {
		audio_file: setAudioFile,
		episode_type: setEpisodeType,
	};

	const handleFieldChange = ( fieldName, value, triggerUpdate ) => {
		editPost({
			meta: {
				[ fieldName ]: value,  // Set the value for the post meta field
			},
		});
		callbacks[ fieldName ]?.(value); // Set the local value

		// Trigger event to update standard meta fields
		if ( triggerUpdate ) {
			document.dispatchEvent(new CustomEvent('changedSSPGutField', {
				'detail': { field: fieldName, value: value },
			}));
		}
	};

	// Ensure state sync with meta field value
	useEffect(() => {
		if ( episodeTypeMeta !== episodeType ) {
			setEpisodeType(episodeTypeMeta); // Sync with meta value
		}

		if ( audioFileMeta !== audioFile ) {
			setAudioFile(audioFileMeta); // Sync with meta value
		}

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
	const [isEpisodeMediaSectionOpen, setEpisodeMediaSectionOpen] = useState(true);
	const [isEpisodeMetaSectionOpen, setEpisodeMetaSectionOpen] = useState(false);

	return (
		<PluginSidebar
			name="ssp-sidebar"
			title={ __('Seriously Simple Podcasting') }
			className="ssp-episode-meta-sidebar"
			icon={ <img src={ SSPIcon } className="ssp-open" alt="SSP Icon"/> }
		>
			<PanelBody>
				<h2
					className={ classnames('ssp-accordion', { open: isEpisodeMediaSectionOpen }) }
					onClick={ () => setEpisodeMediaSectionOpen( ! isEpisodeMediaSectionOpen) }
					aria-expanded={ isEpisodeMediaSectionOpen }
				>
					{ __('Episode Media', 'seriously-simple-podcasting') }
				</h2>
				{ isEpisodeMediaSectionOpen && (
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
							<TextControl
								className={ 'w-full mb-2 ssp-field-audio_file' }
								value={ audioFile }
								onChange={ ( value ) => handleFieldChange('audio_file', value, true) }
							/>

							<input type="button"
								   className={ 'button upload_audio_file_button w-full' }
								   data-field={ 'ssp-field-audio_file' }
								   id="upload_audio_file_button"
								   value={ __('Upload File', 'seriously-simple-podcasting') }
								   data-uploader_title={ __('Choose a file', 'seriously-simple-podcasting') }
								   data-uploader_button_text={ __('Insert podcast file', 'seriously-simple-podcasting') }/>
							<div className={ 'description' }>
								{ __('Upload audio episode files as MP3 or M4A, video episodes as MP4, or paste the file URL.', 'seriously-simple-podcasting') }
							</div>
						</div>

						<div>
							<p className="upsell-field">
								<span className="upsell-field__container">
									<span className="upsell-field__description">Get lower bandwidth fees, file storage, and better stats when hosting with Castos.</span>
									<a className="upsell-field__btn" target="_blank"
									   href="https://castos.com/podcast-hosting-wordpress/?utm_source=ssp&amp;utm_medium=episode-file-box&amp;utm_campaign=upgrade">
										Try Castos for free	</a>
								</span>
							</p>
						</div>

					</div>
				) }
			</PanelBody>

			<PanelBody>
				<h2
					className={ classnames('ssp-accordion', { open: isEpisodeMetaSectionOpen }) }
					onClick={ () => setEpisodeMetaSectionOpen( ! isEpisodeMetaSectionOpen) }
					aria-expanded={ isEpisodeMetaSectionOpen }
				>
					{ __('Episode Meta', 'seriously-simple-podcasting') }
				</h2>
				{ isEpisodeMetaSectionOpen && (
					<div className="ssp-sidebar-content">
						<div className="ssp-sidebar-field-section">
							<h3>{ __('Episode Type', 'seriously-simple-podcasting') }</h3>
							<p>{ __('This is the content of Section 2', 'seriously-simple-podcasting') }</p>
						</div>
					</div>
				) }
			</PanelBody>
		</PluginSidebar>
	);
};

export default EpisodeMetaSidebar;
