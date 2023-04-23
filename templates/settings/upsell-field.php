<?php
/**
 * @var string $description
 * @var array $btn
 * */
?>
<p class="upsell-field">
<span class="upsell-field__container">
	<span class="upsell-field__description"><?php echo $description; ?></span>
	<a class="upsell-field__btn" target="_blank" href="<?php echo esc_url( $btn['url'] ) ?>">
		<?php echo $btn['title'] ?>
	</a>
</span>
</p>

