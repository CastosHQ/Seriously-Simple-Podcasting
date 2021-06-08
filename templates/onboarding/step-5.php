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
			<h1>You’re all set - See what’s next...</h1>
		</div>

		<div class="ssp-onboarding__settings-body" >
			<div class="ssp-onboarding__settings-item iframe-wrapper">
				<iframe width="650" height="370" src="https://www.youtube.com/embed/4tljtfVhR_M?rel=0&amp;showinfo=0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
			</div>
		</div>

		<div class="ssp-onboarding__submit ssp-onboarding__links">
			<div class="ssp-onboarding__links-item">
				<h2>Join the Castos Academy</h2>
				<p>Your toolkit for for everything from starting out smart to taking your podcast to the next level.</p>
				<a target="_blank" href="https://academy.castos.com/?utm_source=WordPress&utm_medium=SSP&utm_campaign=wizard" class="button grey"><span>Join Now</span></a>
			</div>

			<div class="ssp-onboarding__links-item">
				<h2>Creating your first episode</h2>
				<p>Get started by creating your first episode with Seriously Simple Podcasting.</p>
				<a href="<?php echo admin_url('post-new.php?post_type=podcast') ?>" class="button"><span>Let’s Start</span></a>
			</div>
		</div>
	</div>
	<div class="ssp-onboarding__skip">
		<a class="ssp-onboarding__skip-button" href="<?php echo admin_url() ?>">Go to Dashboard</a>
	</div>
</div>
