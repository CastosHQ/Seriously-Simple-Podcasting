<?php
/**
 * @see \SeriouslySimplePodcasting\Controllers\Settings_Controller::render_seriously_simple_sidebar()
 *
 * @var string $img
 * @var bool $is_connected
 * */
?>
<div id="ssp-sidebar">
	<div class="sidebar-content <?php echo $is_connected ? 'castos-connected' : '' ?>">
		<?php echo $img; ?>

		<?php if ( ! $is_connected ) : ?>
			<form action="https://www.getdrip.com/forms/38739479/submissions" method="post"
				  data-drip-embedded-form="38739479">
				<h3 data-drip-attribute="headline"><?php _e( 'Castos Hosting Discount - Get 20% off', 'seriously-simple-podcasting' ); ?></h3>
				<p data-drip-attribute="description"><?php
					_e( 'Drop in your name and email and we’ll send you a coupon for 20% off your subscription to Castos Podcast Hosting.',
						'seriously-simple-podcasting' );
					?></p>
				<div>
					<label for="drip-first-name"><?php _e( 'First Name', 'seriously-simple-podcasting' ); ?></label>
					<input type="text" id="drip-first-name" name="fields[first_name]" value=""/>
				</div>
				<div>
					<label for="drip-last-name"><?php _e( 'Last Name', 'seriously-simple-podcasting' ); ?></label>
					<input type="text" id="drip-last-name" name="fields[last_name]" value=""/>
				</div>
				<div>
					<label for="drip-email"><?php _e( 'Email Address', 'seriously-simple-podcasting' ); ?></label>
					<input type="email" id="drip-email" name="fields[email]" value=""/>
				</div>
				<div style="display: none;" aria-hidden="true">
					<label for="website"><?php _e( 'Website', 'seriously-simple-podcasting' ); ?></label>
					<input type="text" id="website" name="website" tabindex="-1" autocomplete="false" value=""/>
				</div>
				<div>
					<input type="submit" value="Send me the coupon" data-drip-attribute="sign-up-button"/>
				</div>
				<p><?php _e( 'Spam sucks. We will not use your email for anything else and you can unsubscribe with just a click,
					anytime.', 'seriously-simple-podcasting' ); ?></p>
			</form>
		<?php endif; ?>
	</div>
</div>
