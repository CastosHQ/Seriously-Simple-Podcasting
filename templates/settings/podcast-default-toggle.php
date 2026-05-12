<?php
/**
 * @var bool $is_default
 */
?>
<tr class="form-field term-default-podcast-wrap">
	<th scope="row">
		<label><?php esc_html_e( 'Default Podcast', 'seriously-simple-podcasting' ); ?></label>
	</th>
	<td>
		<?php if ( $is_default ) : ?>
			<p class="ssp-default-podcast-label">
				<?php esc_html_e( 'This is the default podcast', 'seriously-simple-podcasting' ); ?>
			</p>
		<?php else : ?>
			<label class="ssp-toggle" for="ssp_default_podcast">
				<input id="ssp_default_podcast" type="checkbox" name="ssp_default_podcast" value="1" />
				<span class="ssp-toggle__slider" aria-hidden="true"></span>
				<span class="ssp-toggle__label">
					<?php esc_html_e( 'Set as default podcast', 'seriously-simple-podcasting' ); ?>
				</span>
			</label>
		<?php endif; ?>
	</td>
</tr>
