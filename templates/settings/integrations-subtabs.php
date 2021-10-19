<?php
/**
 * @var array $integrations
 * @var array $current
 * */
?>
<div class="feed-series-list-container">
	<ul id="feed-series-list" class="subsubsub series-open">
		<?php foreach ( $integrations as $id => $item ) :
			$url = esc_url( add_query_arg( array( 'integration' => $id ) ) );
			$class = $current === $id ? 'current' : '';

			?><li><a href="<?php esc_attr_e( $url ) ?>" class="<?php
			esc_attr_e( $class ) ?>"><?php esc_html_e( $item['title'] ) ?></a></li>
		<?php endforeach; ?>
	</ul>
	<br class="clear"/>
</div>
