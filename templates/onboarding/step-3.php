<?php
/**
 * @var int $step_number
 * @var array $step_urls
 * @var string $next_step
 * @var array $categories
 * @var array $subcategories
 * @var string $data_category
 * @var string $data_subcategory
 * */
?>

<div class="ssp-onboarding ssp-onboarding-step-3">
	<?php include __DIR__ . '/steps-header.php'; ?>
	<div class="ssp-onboarding__settings">
		<div class="ssp-onboarding__settings-header">
			<h1><?php _e( 'Podcast Category', 'seriously-simple-podcasting' ); ?></h1>
		</div>
		<form class="ssp-onboarding__settings-body" action="<?php echo $step_urls[ $step_number + 1 ] ?>" method="post">
			<div class="ssp-onboarding__settings-item">
				<h2><?php _e( 'Primary Category', 'seriously-simple-podcasting' ); ?></h2>
				<label for="<?php echo $categories['id'] ?>" class="description">
					<?php _e( 'What primary category should we publish your podcast in?', 'seriously-simple-podcasting' ); ?>
				</label>
				<div class="ssp-onboarding__select">
					<select name="<?php echo $categories['id'] ?>" id="<?php echo $categories['id'] ?>" class="js-onboarding-field <?php echo $categories['class'] ?>" data-subcategory="data_subcategory">
						<?php foreach ( $categories['options'] as $k => $v ) : ?>
							<option value="<?php echo esc_attr($k) ?>" <?php selected( $k === $data_category ) ?>><?php echo $v ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="ssp-onboarding__settings-item">
				<h2><?php _e( 'Primary Sub-Category', 'seriously-simple-podcasting' ); ?></h2>
				<label for="<?php echo $subcategories['id'] ?>" class="description">
					<?php _e( 'Your podcast sub-category based on the primary category selected above.', 'seriously-simple-podcasting' ); ?>
				</label>
				<div class="ssp-onboarding__select">
					<select id="<?php echo $subcategories['id'] ?>" name="<?php echo $subcategories['id'] ?>">
						<?php $prev_group = ''; ?>
						<?php foreach ( $subcategories['options'] as $k => $v ) :
							$group = '';
							if ( is_array( $v ) ) {
								if ( isset( $v['group'] ) ) {
									$group = $v['group'];
								}
								$v = $v['label'];
							}
							if ( $prev_group && $group !== $prev_group ) {
								echo '</optgroup>';
							}
							if ( $group && $group !== $prev_group ) {
								echo '<optgroup label="' . esc_attr( $group ) . '">';
							}
							$prev_group = $group;
							?>
							<option value="<?php echo esc_attr($k) ?>" <?php selected( $k === $data_subcategory ) ?>>
								<?php echo $v ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="ssp-onboarding__submit">
				<?php wp_nonce_field( 'ssp_onboarding_' . $step_number, 'nonce', false ); ?>
				<a href="<?php echo $step_urls[ $step_number + 1 ] ?>" class="button skip"><span>
					<?php _e( 'Skip', 'seriously-simple-podcasting' ); ?></span></a>
				<button type="submit" class="js-onboarding-btn">
					<?php _e( 'Proceed', 'seriously-simple-podcasting' ); ?>
				</button>
			</div>
		</form>
	</div>
	<?php include __DIR__ . '/steps-footer.php'; ?>
</div>
