<?php
/**
 * @var string $next_step
 * @var string $img_url
 * */
?>

<div class="ssp-onboarding">
	<?php include __DIR__ . '/steps-header.php'; ?>
	<div class="ssp-onboarding__settings">
		<div class="ssp-onboarding__settings-header">
			<h1>Podcast Cover Image</h1>
		</div>
		<form class="ssp-onboarding__settings-body" action="<?php echo $next_step ?>" method="post">
			<div class="ssp-onboarding__settings-item">
				<h2>Do you have a cover image ready?</h2>
				<label for="ss_podcasting_data_image_button" class="description">This image should be JPG or PNG format and between 1400x1400px and 3000x3000px in size.</label>
				<input id="ss_podcasting_data_image_button" type="button" class="button" value="Upload new image">
			</div>

			<div class="ssp-onboarding__submit">
				<span class="ssp-onboarding__image-info js-onboarding-img-info">
					<img id="ss_podcasting_data_image_preview" class="js-onboarding-img" src="<?php echo $img_url ?>">
					<span class="js-onboarding-img-name ssp-onboarding__image-name">My Cover.jpg</span>
					<span class="js-onboarding-delete-img-info ssp-onboarding__delete-image"></span>
					<input id="ss_podcasting_data_image" name="data_image"" class="js-onboarding-img-val" type="hidden" value="<?php echo $img_url ?>">
				</span>
				<a href="<?php echo $next_step ?>" class="button"><span>Skip</span></a>
				<button type="submit">Proceed</button>
			</div>
		</form>
	</div>
</div>
