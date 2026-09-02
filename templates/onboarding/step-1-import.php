<?php
/**
 * Step 1's import branch: confirm a feed, then import it.
 *
 * @see \SeriouslySimplePodcasting\Controllers\Onboarding_Controller::step_1()
 *
 * @var int $step_number
 * @var array $step_urls
 * @var string $ajax_url
 * @var string $ajax_nonce
 * @var string $cancel_url
 *
 * @package Seriously Simple Podcasting
 */

?>

<div class="ssp-onboarding-import js-ssp-import"
	data-ajax-url="<?php echo esc_url( $ajax_url ); ?>"
	data-nonce="<?php echo esc_attr( $ajax_nonce ); ?>"
	data-next-url="<?php echo esc_url( $step_urls[ $step_number + 1 ] ); ?>">

	<div class="ssp-onboarding-import__stage js-ssp-import-stage" data-stage="url">
		<div class="ssp-onboarding__settings-header">
			<h1><?php esc_html_e( 'Import your existing podcast', 'seriously-simple-podcasting' ); ?></h1>
			<p><?php esc_html_e( 'Paste your show\'s RSS feed and we\'ll bring it in, episodes and all.', 'seriously-simple-podcasting' ); ?></p>
		</div>
		<div class="ssp-onboarding__settings-body">
			<div class="ssp-onboarding__settings-item">
				<h2><?php esc_html_e( 'RSS feed URL', 'seriously-simple-podcasting' ); ?></h2>
				<label for="ssp_import_feed_url">
					<?php esc_html_e( 'Find this in your current host\'s settings — it usually ends in /feed or /rss.', 'seriously-simple-podcasting' ); ?>
				</label>
				<input id="ssp_import_feed_url" class="js-ssp-import-url" type="url"
					placeholder="https://feeds.castos.com/your-show" autocomplete="off">
				<p class="ssp-onboarding-import__note"><?php esc_html_e( 'Works with Castos, Libsyn, Buzzsprout, Spreaker, Transistor, Anchor, Simplecast and any other public feed.', 'seriously-simple-podcasting' ); ?></p>
				<p class="ssp-onboarding-import__error js-ssp-import-error" role="alert" hidden></p>
			</div>
			<div class="ssp-onboarding__submit">
				<a href="<?php echo esc_url( $cancel_url ); ?>" class="button skip">
					<span><?php esc_html_e( 'Create a new podcast instead', 'seriously-simple-podcasting' ); ?></span>
				</a>
				<button type="button" class="js-ssp-import-fetch" disabled="disabled"
					data-busy-txt="<?php esc_attr_e( 'Checking feed…', 'seriously-simple-podcasting' ); ?>">
					<?php esc_html_e( 'Find my podcast', 'seriously-simple-podcasting' ); ?>
				</button>
			</div>
		</div>
	</div>

	<div class="ssp-onboarding-import__stage js-ssp-import-stage" data-stage="preview" hidden>
		<div class="ssp-onboarding__settings-header">
			<h1><?php esc_html_e( 'Is this your podcast?', 'seriously-simple-podcasting' ); ?></h1>
			<p><?php esc_html_e( 'Check the details below, then we\'ll import it to this site.', 'seriously-simple-podcasting' ); ?></p>
		</div>
		<div class="ssp-onboarding__settings-body">
			<div class="ssp-onboarding-import__found">
				<div class="ssp-onboarding-import__cover">
					<img class="js-ssp-import-image" src="" alt="" hidden>
				</div>
				<div class="ssp-onboarding-import__details">
					<h3 class="js-ssp-import-title"></h3>
					<p class="js-ssp-import-meta"></p>
					<span class="ssp-onboarding-import__count js-ssp-import-count"></span>
				</div>
			</div>
			<div class="ssp-onboarding__submit">
				<a href="#" class="button skip js-ssp-import-back">
					<span><?php esc_html_e( 'Use a different feed', 'seriously-simple-podcasting' ); ?></span>
				</a>
				<button type="button" class="js-ssp-import-start"
					data-busy-txt="<?php esc_attr_e( 'Starting…', 'seriously-simple-podcasting' ); ?>">
					<?php esc_html_e( 'Import this podcast', 'seriously-simple-podcasting' ); ?>
				</button>
			</div>
		</div>
	</div>

	<div class="ssp-onboarding-import__stage js-ssp-import-stage" data-stage="running" hidden>
		<div class="ssp-onboarding__settings-header">
			<h1><?php esc_html_e( 'Importing your podcast', 'seriously-simple-podcasting' ); ?></h1>
			<p><?php esc_html_e( 'This usually takes a minute or two.', 'seriously-simple-podcasting' ); ?></p>
		</div>
		<div class="ssp-onboarding__settings-body">
			<div class="ssp-onboarding-import__progress">
				<div class="ssp-onboarding-import__progress-bar js-ssp-import-bar" style="width: 0"></div>
			</div>
			<p class="ssp-onboarding-import__progress-label js-ssp-import-progress"></p>
			<p class="ssp-onboarding-import__note">
				<?php esc_html_e( 'Keep this tab open until the import finishes. Large shows can take a few minutes.', 'seriously-simple-podcasting' ); ?>
			</p>
		</div>
	</div>

	<div class="ssp-onboarding-import__stage js-ssp-import-stage" data-stage="failed" hidden>
		<div class="ssp-onboarding__settings-header">
			<h1><?php esc_html_e( 'The import stopped', 'seriously-simple-podcasting' ); ?></h1>
			<p><?php esc_html_e( 'Nothing already imported was lost — you can pick up where it stopped.', 'seriously-simple-podcasting' ); ?></p>
		</div>
		<div class="ssp-onboarding__settings-body">
			<p class="ssp-onboarding-import__error js-ssp-import-failure" role="alert"></p>
			<div class="ssp-onboarding__submit">
				<a href="<?php echo esc_url( $step_urls[ $step_number + 1 ] ); ?>" class="button skip">
					<span><?php esc_html_e( 'Continue anyway', 'seriously-simple-podcasting' ); ?></span>
				</a>
				<button type="button" class="js-ssp-import-retry">
					<?php esc_html_e( 'Retry', 'seriously-simple-podcasting' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
