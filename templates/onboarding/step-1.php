<?php
/**
 * @var int $step_number
 * @var array $step_urls
 * @var string $next_step
 * @var string $data_title
 * @var string $data_description
 * */
?>

<div class="ssp-onboarding ssp-onboarding-step-1">
	<?php include __DIR__ . '/steps-header.php'; ?>
	<div class="ssp-onboarding__settings">
		<div class="ssp-onboarding__settings-header">
			<h1>Let's get your podcast started</h1>
		</div>
		<form class="ssp-onboarding__settings-body" action="<?php echo $step_urls[ $step_number + 1 ] ?>" method="post">
			<div class="ssp-onboarding__settings-item">
				<h2>What’s the name of your show?</h2>
				<label for="show_name">This will be the title shown to listeners. You can always change it later.</label>
				<input id="show_name" class="js-onboarding-field" type="text" name="data_title" value="<?php echo $data_title ?>">
			</div>
			<div class="ssp-onboarding__settings-item">
				<h2>What’s your show about?</h2>
				<label for="show_description">Pique listeners' interest with a a few details about your podcast.</label>
				<textarea id="show_description" class="js-onboarding-field" name="data_description" rows="7"><?php echo $data_description ?></textarea>
			</div>
			<div class="ssp-onboarding__submit">
				<button type="submit" class="js-onboarding-btn" <?php if( empty( $data_title ) || empty( $data_description ) ) echo 'disabled="disabled"' ?>>Proceed</button>
			</div>
		</form>
	</div>
	<?php include __DIR__ . '/steps-footer.php'; ?>
</div>
