<?php
/**
 * @var int $step_number
 * @var array $step_urls
 * @var string $data_image
 * */

$img_name = $data_image ? pathinfo( $data_image, PATHINFO_BASENAME ) : '';
?>

<div class="ssp-onboarding ssp-onboarding-step-2">
	<?php include __DIR__ . '/steps-header.php'; ?>
	<div class="ssp-onboarding__settings">
		<div class="ssp-onboarding__settings-header">
			<h1>Artwork</h1>
		</div>
		<form class="ssp-onboarding__settings-body" action="<?php echo $step_urls[ $step_number + 1 ] ?>" method="post">
			<div class="ssp-onboarding__settings-item">
				<h2>Upload your podcast's cover image</h2>

				<label for="ss_podcasting_data_image_button" class="description">Image must be JPG or PNG format and between 1400 x 1400px and 3000 x 3000px.</label>
				<input id="ss_podcasting_data_image_button" type="hidden" class="button" value="Upload new image">

				<div class="ssp-onboarding__dragable js-onboarding-dragable">
					<span>Upload image...</span>
				</div>
			</div>

			<div class="ssp-onboarding__submit">
				<span class="ssp-onboarding__image-info js-onboarding-img-info" style="display: none">
					<img id="ss_podcasting_data_image_preview" class="js-onboarding-img" src="<?php echo $data_image ?>">
					<span class="js-onboarding-img-name ssp-onboarding__image-name"><?php echo $img_name; ?></span>
					<span class="js-onboarding-delete-img-info ssp-onboarding__delete-image"></span>
					<input id="ss_podcasting_data_image" name="data_image" class="js-onboarding-img-val js-onboarding-field" type="hidden" value="<?php echo $data_image ?>">
				</span>
				<a href="<?php echo $step_urls[ $step_number + 1 ] ?>" class="button skip"><span>Skip</span></a>
				<button type="submit" class="js-onboarding-btn">Proceed</button>
			</div>
		</form>
	</div>
	<?php include __DIR__ . '/steps-footer.php'; ?>
</div>
