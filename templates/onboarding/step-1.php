<?php
/**
 * @var string $title
 * @var string $description
 * @var string $next_step
 * */
?>

<div class="ssp-onboarding">
	<div class="ssp-onboarding__logo">
		<img alt="Seriously Simple Podcasting" src="<?php echo SSP_PLUGIN_URL . '/assets/admin/img/logo.png'; ?>">
		<div class="ssp-onboarding__logo-text">
			<span class="ssp-onboarding__logo-title">Seriously Simple Podcasting</span>
			<span class="ssp-onboarding__logo-label">By Castos</span>
		</div>
	</div>
	<ul class="ssp-onboarding__steps">
		<li class="ssp-onboarding__step ssp-onboarding__step-1 active">Welcome</li>
		<li class="ssp-onboarding__step ssp-onboarding__step-2">Cover</li>
		<li class="ssp-onboarding__step ssp-onboarding__step-3">Categories</li>
		<li class="ssp-onboarding__step ssp-onboarding__step-4">Hosting</li>
		<li class="ssp-onboarding__step ssp-onboarding__step-5">Done!</li>
	</ul>
	<div class="ssp-onboarding__settings">
		<div class="ssp-onboarding__settings-header">
			<h1>Let's get your podcast started</h1>
		</div>
		<form class="ssp-onboarding__settings-body" action="<?php echo $next_step ?>" method="post">
			<div class="ssp-onboarding__settings-item">
				<h2>What’s the name of your show?</h2>
				<label for="show_name">This will be the “Title” field in the feed details area.</label>
				<input id="show_name" type="text" name="data_title" value="<?php echo $title ?>">
			</div>
			<div class="ssp-onboarding__settings-item">
				<h2>What’s your show about?</h2>
				<label for="show_description">Just a couple of sentences to let listeners know what to expect.</label>
				<textarea id="show_description" name="data_description" rows="7"><?php echo $description ?></textarea>
			</div>
			<div class="ssp-onboarding__submit">
				<button type="submit">Proceed</button>
			</div>
		</form>
	</div>
</div>
