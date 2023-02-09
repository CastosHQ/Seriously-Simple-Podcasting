<?php
/**
 * @var string $title
 * @var string $subtitle
 * @var string $description Example: Create a custom cover with our free tool %s
 * */
?>
<span class="ssp-dynamo">
	<a target="_blank"
	   href="https://dynamo.castos.com/podcast-covers?utm_source=WordPress&utm_medium=Plugin&utm_campaign=dashboard&t=<?php echo rawurlencode( $title ) ?>&s=<?php echo rawurlencode( $subtitle ) ?>">
		<?php echo sprintf( __( $description ), '<span class="dynamo-button">Dynamo<span class="dashicons dashicons-external"></span></span>' ) ?>
	</a>
</span>
