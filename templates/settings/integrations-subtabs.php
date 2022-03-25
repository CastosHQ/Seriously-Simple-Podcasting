<?php
/**
 * @var array $integrations
 * @var array $current
 * */
?>
<div class="integrations-list-container">
	<ul id="integrations-list" class="subsubsub integration-open">
		<?php foreach ( $integrations as $id => $item ) :
			$url = esc_url( add_query_arg( array( 'integration' => $id ) ) );
			$class = $current === $id ? 'current' : '';

			?><li><a href="<?php esc_attr_e( $url ) ?>" class="<?php
			esc_attr_e( $class ) ?>"><?php esc_html_e( $item['title'] ) ?></a></li>
		<?php endforeach; ?>
	</ul>
	<br class="clear"/>
</div>
