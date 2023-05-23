<?php
/**
 * @var int $step_number
 * @var array $step_urls
 * @var string $podmotor_account_email
 * @var string $podmotor_account_api_token
 * */
?>

<div class="ssp-onboarding ssp-onboarding-step-5">
	<?php include __DIR__ . '/steps-header.php'; ?>
	<div class="ssp-onboarding__settings">
		<div class="ssp-onboarding__settings-header">
			<h1><?php _e( 'You\'re all set - See what\'s next', 'seriously-simple-podcasting' ); ?>...</h1>
		</div>

		<div class="ssp-onboarding__settings-body" >
			<div class="ssp-onboarding__settings-item iframe-wrapper">
				<iframe width="650" height="370" src="https://www.youtube.com/embed/4tljtfVhR_M?rel=0&amp;showinfo=0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
			</div>
		</div>

		<div class="ssp-onboarding__submit ssp-onboarding__links">
			<div class="ssp-onboarding__links-item">
				<h2><?php _e( 'Video Resources', 'seriously-simple-podcasting' ); ?></h2>
				<p><?php _e( 'Explore the world of podcasting with WordPress, Seriously Simple Podcasting & Castos.', 'seriously-simple-podcasting' ); ?></p>
				<a target="_blank" href="https://www.youtube.com/playlist?list=PLQX-MHyR9D1X3U5YYXY4HO7goClC8hBQW" class="button grey">
					<span><?php _e( 'Browse Resources', 'seriously-simple-podcasting' ); ?></span>
				</a>
			</div>

			<div class="ssp-onboarding__links-item">
				<h2><?php _e( 'Creating your first episode', 'seriously-simple-podcasting' ); ?></h2>
				<p><?php _e( 'Get started by creating your first episode with Seriously Simple Podcasting.', 'seriously-simple-podcasting' ); ?></p>
				<a href="<?php echo admin_url('post-new.php?post_type=' . SSP_CPT_PODCAST) ?>" class="button">
					<span><?php _e( 'Let\'s Start', 'seriously-simple-podcasting' ); ?></span>
				</a>
			</div>
		</div>
	</div>
	<div class="ssp-onboarding__skip">
		<a class="ssp-onboarding__skip-button" href="<?php echo admin_url() ?>">
			<?php _e( 'Go to Dashboard', 'seriously-simple-podcasting' ); ?>
		</a>
	</div>
</div>
