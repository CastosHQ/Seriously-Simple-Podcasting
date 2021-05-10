<?php
/**
 * @var int $step_number
 * @var array $step_urls
 * @var string $podmotor_account_email
 * @var string $podmotor_account_api_token
 * */
?>

<div class="ssp-onboarding ssp-onboarding__step-4">
	<?php include __DIR__ . '/steps-header.php'; ?>
	<div class="ssp-onboarding__settings">
		<div class="ssp-onboarding__settings-header">
			<h1>Connect to Castos Hosting & Analytics</h1>
		</div>
		<form class="ssp-onboarding__settings-body" action="<?php echo $step_urls[ $step_number + 1 ] ?>" method="post">
			<div class="ssp-onboarding__settings-item">
				<h2>Your Email</h2>
				<label for="podmotor_account_email" class="description">
					The email address you used to register your Castos account.
				</label>
				<input id="podmotor_account_email" type="text" name="podmotor_account_email" value="<?php echo $podmotor_account_email ?>">
			</div>

			<div class="ssp-onboarding__settings-item">
				<h2>Castos API Token</h2>
				<label for="podmotor_account_api_token" class="description">
					Available from your Castos account dashboard.
				</label>
				<input id="podmotor_account_api_token" type="text" name="podmotor_account_api_token" value="<?php echo $podmotor_account_api_token ?>">
			</div>

			<div class="ssp-onboarding__submit">
				<input id="validate_api_credentials" type="button" class="button" value="Validate Credentials">
				<?php wp_nonce_field( 'ss_podcasting_castos-hosting', 'podcast_settings_tab_nonce', false ); ?>
				<span class="validate-api-credentials-message"></span>
				<button type="submit">Proceed</button>
			</div>
		</form>
	</div>
</div>
