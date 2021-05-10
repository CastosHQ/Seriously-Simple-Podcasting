<?php
/**
 * @var int $step_number
 * @var array $step_urls
 * */

$steps = array(
	1 => 'Welcome',
	2 => 'Cover',
	3 => 'Categories',
	4 => 'Hosting',
	5 => 'Done!',
);
?>
<div class="ssp-onboarding__logo">
	<img alt="Seriously Simple Podcasting" src="<?php echo SSP_PLUGIN_URL . '/assets/admin/img/logo.png'; ?>">
	<div class="ssp-onboarding__logo-text">
		<span class="ssp-onboarding__logo-title">Seriously Simple Podcasting</span>
		<span class="ssp-onboarding__logo-label">By Castos</span>
	</div>
</div>
<ul class="ssp-onboarding__steps">
	<?php foreach ( $steps as $k => $name ) : ?>
		<?php $class = ( $k < $step_number ) ? 'completed' : ( $k === $step_number ? 'active' : '' ); ?>
		<li class="ssp-onboarding__step<?php echo ' ' . $class ?>">
			<?php if ( $k < $step_number ) : ?>
				<a href="<?php echo $step_urls[ $k ] ?>"><span><?php echo $name ?></span></a>
			<?php else: ?>
				<span><?php echo $name ?></span>
			<?php endif ?>
		</li>
	<?php endforeach; ?>
</ul>
