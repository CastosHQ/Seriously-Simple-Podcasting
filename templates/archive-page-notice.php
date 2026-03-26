<?php
/**
 * Archive page upgrade notice template.
 *
 * @var string $setup_url   URL to set up the archive page.
 * @var string $dismiss_url URL to dismiss the notice.
 * @var string $archive_url Podcast archive URL.
 */
?>

<div class="notice notice-info is-dismissible">
	<p>
		<?php
		printf(
			/* translators: %s: podcast archive URL */
			esc_html__( 'You can now customize your episodes page (%s) with the block editor or any page builder.', 'seriously-simple-podcasting' ),
			'<a href="' . esc_url( $archive_url ) . '" target="_blank">' . esc_html( $archive_url ) . '</a>'
		);
		?>
	</p>
	<p>
		<a href="<?php echo esc_url( $setup_url ); ?>" class="button button-primary">
			<?php esc_html_e( 'Set up now', 'seriously-simple-podcasting' ); ?>
		</a>
		<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button">
			<?php esc_html_e( 'Dismiss', 'seriously-simple-podcasting' ); ?>
		</a>
	</p>
</div>
