<?php
/**
 * @see ssp_dynamo_btn()
 *
 * @var string $title
 * @var string $subtitle
 * @var string $description Example: Create a custom cover with our free tool
 * @var string $default_podcast_title Needed to show default podcast title when user didn't check any podcast for episode
 * */
?>
<p class="ssp-dynamo" data-default-podcast-title="<?php echo esc_attr( $default_podcast_title ); ?>">
<span class="ssp-dynamo__container">
	<span class="ssp-dynamo__description"><?php echo esc_html( $description ); ?></span>
	<a class="ssp-dynamo__btn" target="_blank" rel="noopener noreferrer"
		href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'utm_source'   => 'WordPress',
					'utm_medium'   => 'Plugin',
					'utm_campaign' => 'dashboard',
					't'            => $title,
					's'            => $subtitle,
				),
				'https://dynamo.castos.com/podcast-covers'
			)
		);
		?>
		">
	<span class="ssp-dynamo__arrow-up"><?php esc_html_e( 'Dynamo', 'seriously-simple-podcasting' ); ?></span>
	</a>
</span>
</p>
