<?php
/**
 * Review request notice template.
 * */
?>

<div class="notice notice-info ssp-review-notice js-ssp-review-notice">
	<div class="ssp-review-notice__text">
		<p>
			<?php _e( 'Hey, It seems you have been using Seriously Simple Podcasting for at least 7 days now -
			that\'s awesome!', 'seriously-simple-podcasting' ); ?>
		</p>
		<p>
			<?php _e( 'Could you please do us a BIG favor and give it a 5-star rating on WordPress?
			This will help us spread the word and boost our motivation - thanks!', 'seriously-simple-podcasting' ); ?>
		</p>
	</div>

	<div class="ssp-review-notice__buttons">
		<div class="ssp-review-notice__button">
			<a href="https://wordpress.org/support/plugin/seriously-simple-podcasting/reviews/?filter=5#new-post"
			   target="_blank" class="button button-primary js-ssp-change-review-status" data-status="review" data-nonce="<?php echo wp_create_nonce( 'ssp_review_notice_review' ) ?>">
				<?php _e( 'Ok, you deserve it', 'seriously-simple-podcasting' ); ?>
			</a>
		</div>
		<div class="ssp-review-notice__button">
			<span class="dashicons dashicons-calendar"></span>
			<a href="#" class="js-ssp-change-review-status" data-status="later" data-nonce="<?php echo wp_create_nonce( 'ssp_review_notice_later' ) ?>">
				<?php _e( 'Nope, maybe later', 'seriously-simple-podcasting' ); ?>
			</a>
		</div>
		<div class="ssp-review-notice__button">
			<span class="dashicons dashicons-smiley"></span>
			<a href="#" class="js-ssp-change-review-status" data-status="reviewed" data-nonce="<?php echo wp_create_nonce( 'ssp_review_notice_reviewed' ) ?>">
				<?php _e( 'I already did', 'seriously-simple-podcasting' ); ?>
			</a>
		</div>
	</div>

	<button type="button" class="notice-dismiss js-ssp-change-review-status" data-status="dismiss" data-nonce="<?php echo wp_create_nonce( 'ssp_review_notice_dismiss' ) ?>">
		<span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'seriously-simple-podcasting' ); ?></span>
	</button>
</div>
