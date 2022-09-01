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
			<h1><?php _e( 'Let\'s get your podcast started', 'seriously-simple-podcasting' ); ?></h1>
		</div>
		<form class="ssp-onboarding__settings-body" action="<?php echo $step_urls[ $step_number + 1 ] ?>" method="post">
			<div class="ssp-onboarding__settings-item">
				<h2><?php _e( 'What\'s the name of your show?', 'seriously-simple-podcasting' ); ?></h2>
				<label for="show_name">
					<?php _e( 'This will be the title shown to listeners. You can always change it later.', 'seriously-simple-podcasting' ); ?>
				</label>
				<input id="show_name" class="js-onboarding-field" type="text" name="data_title" value="<?php echo $data_title ?>">
			</div>
			<div class="ssp-onboarding__settings-item">
				<h2><?php _e( 'What\'s your show about?', 'seriously-simple-podcasting' ); ?></h2>
				<label for="show_description">
					<?php _e( 'Pique listeners\' interest with a few details about your podcast.', 'seriously-simple-podcasting' ); ?>
				</label>
				<textarea id="show_description" class="js-onboarding-field" name="data_description" rows="7"><?php echo $data_description ?></textarea>
			</div>
			<div class="ssp-onboarding__submit">
				<?php wp_nonce_field( 'ssp_onboarding_' . $step_number, 'nonce', false ); ?>
				<button type="submit" class="js-onboarding-btn" <?php if( empty( $data_title ) || empty( $data_description ) ) echo 'disabled="disabled"' ?>>
					<?php _e( 'Proceed', 'seriously-simple-podcasting' ); ?>
				</button>
			</div>
		</form>
	</div>
	<?php include __DIR__ . '/steps-footer.php'; ?>
</div>
