<?php
/**
 * @var $step_number
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
		<li class="ssp-onboarding__step<?php echo ' ' . $class ?>"><?php echo $name ?></li>
	<?php endforeach; ?>
</ul>
