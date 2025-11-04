<?php
/**
 * @var int $step_number
 * @var array $step_urls
 * @var string $podmotor_account_api_token
 * */

$benefits = array(
	__( '**Save cost** with cloud **hosting** for your media files.', 'seriously-simple-podcasting' ),
	__( 'See **more data** about your listeners with advanced analytics.', 'seriously-simple-podcasting' ),
	__( 'Send episodes to **YouTube** automatically.', 'seriously-simple-podcasting' ),
	__( '**Private Podcasts** with a mobile app for your listeners.', 'seriously-simple-podcasting' ),
	__( 'Create **unlimited podcasts** with **unlimited storage**.', 'seriously-simple-podcasting' ),
	__( '**Monetize** your show with donations, dynamic ad insertion or subscriptions.', 'seriously-simple-podcasting' ),
);

$trial_url = 'https://app.castos.com/register?utm_source=ssp&utm_medium=onboarding&utm_campaign=hosting';

?>

<div class="ssp-onboarding ssp-onboarding-step-4">
	<?php include __DIR__ . '/steps-header.php'; ?>

	<div class="ssp-onboarding__settings">
		<div class="ssp-onboarding__settings-header">
			<h1><?php _e( 'Would you like to host with Castos?', 'seriously-simple-podcasting' ); ?></h1>
			<p><?php _e( 'Not sure why you need a podcast host?', 'seriously-simple-podcasting' ); ?>
				<a href="https://castos.com/seriously-simple-podcasting/?utm_source=ssp&utm_medium=onboarding&utm_campaign=learn-more">
					<?php _e( 'Learn Why', 'seriously-simple-podcasting' ); ?><span class="dashicons dashicons-external"></span>
				</a>
			</p>
		</div>
		<div class="ssp-onboarding-step-4__info">
			<div class="ssp-onboarding-step-4__sync-img">
				<a href="<?php echo $trial_url; ?>">
					<img alt="<?php _e( 'Sync between SSP and Castos', 'seriously-simple-podcasting' ) ?>"
						 src="<?php echo SSP_PLUGIN_URL . '/assets/admin/img/onboarding-sync.svg'; ?>">
				</a>
			</div>
			<div class="ssp-onboarding-step-4__benefits">
				<h2><?php _e( 'Benefits of connecting SSP to your Castos account', 'seriously-simple-podcasting' ); ?></h2>
				<ul>
					<?php foreach ( $benefits as $benefit ) : ?>
						<?php $benefit = preg_replace('/\*\*(.+)\*\*/sU', '<b>$1</b>', $benefit); ?>
						<li><?php echo $benefit; ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="ssp-onboarding-step-4__start-trial">
				<a target="_blank" rel="noopener" href="<?php echo $trial_url; ?>">
					<?php _e( 'Start Free Trial on Castos', 'seriously-simple-podcasting' ) ?>
					<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M21.5 12H3.5M14.5 5L21.5 12L14.5 5ZM21.5 12L14.5 19L21.5 12Z" stroke="#F3C2C2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</a>
			</div>
		</div>
		<div class="ssp-onboarding-step-4__accordion js-accordion">
			<span><?php _e( 'Enter my API details to connect Castos', 'seriously-simple-podcasting' ) ?></span>
		</div>
		<div class="ssp-onboarding-step-4__form js-hosting-form">
			<div class="ssp-onboarding__hosting-steps">
				<div class="ssp-onboarding__hosting-step">
					<a href="https://app.castos.com/register" target="_blank">
						<span class="ssp-onboarding__hosting-step--header">
							<?php _e( 'Sign-up', 'seriously-simple-podcasting' ); ?>
						</span>
						<span class="ssp-onboarding__hosting-step--info">
							<?php printf( __( 'Create your account at %s', 'seriously-simple-podcasting' ), '<span>Castos</span>' ); ?>
						</span>
					</a>
				</div>

				<div class="ssp-onboarding__hosting-step">
					<a href="https://app.castos.com/integrations/api-details" target="_blank" rel="noopener">
						<span class="ssp-onboarding__hosting-step--header">
							<?php _e( 'Complete details below', 'seriously-simple-podcasting' ); ?>
						</span>
						<span class="ssp-onboarding__hosting-step--info">
							<?php printf( __( 'Get your API token from %s', 'seriously-simple-podcasting' ), '<span>Castos</span>' ); ?>
						</span>
					</a>
				</div>
			</div>
			<form class="ssp-onboarding__settings-body" action="<?php echo $step_urls[ $step_number + 1 ] ?>" method="post">
				<div class="ssp-onboarding__settings-item">
					<h2><?php _e( 'Castos API Token', 'seriously-simple-podcasting' ); ?></h2>
					<label for="podmotor_account_api_token" class="description">
						<?php _e( 'Available from your Castos account dashboard.', 'seriously-simple-podcasting' ); ?>
					</label>
					<input id="podmotor_account_api_token" type="text" class="js-onboarding-castos-connect-field" name="podmotor_account_api_token" value="<?php echo $podmotor_account_api_token ?>" <?php disabled( !! $podmotor_account_api_token ) ?>>
				</div>

				<div class="ssp-onboarding__submit">
					<?php wp_nonce_field( 'ssp_onboarding_' . $step_number, 'nonce', false ); ?>
					<button id="castos-connect" type="button" class="button castos-connect js-onboarding-castos-connect <?php if( $podmotor_account_api_token ) : ?>connected<?php endif ?>" data-connected-txt="<?php _e('Connected', 'seriously-simple-podcasting') ?>" data-connecting-txt="<?php
						_e('Connecting Castos', 'seriously-simple-podcasting') ?>" data-no-reload="true" data-initial-txt="<?php _e('Connect Castos', 'seriously-simple-podcasting') ?>" disabled >
						<?php echo $podmotor_account_api_token ? __('Connected', 'seriously-simple-podcasting') : __( 'Connect Castos', 'seriously-simple-podcasting' ) ?>
					</button>
					<?php wp_nonce_field( 'ss_podcasting_castos-hosting', 'podcast_settings_tab_nonce', false ); ?>
					<button type="submit" <?php disabled( ! $podmotor_account_api_token ) ?>><?php _e( 'Proceed', 'seriously-simple-podcasting' ); ?></button>
					<span class="connect-castos-message"></span>
				</div>
			</form>
		</div>
	</div>
	<div class="ssp-onboarding-step-4__skip-step">
		<a href="<?php echo $step_urls[ 5 ] ?>"><?php _e( 'Skip Step', 'seriously-simple-podcasting' ); ?></a>
	</div>
	<?php include __DIR__ . '/steps-footer.php'; ?>
</div>
