<div>
	<div class="ssp-subscribe-buttons">
        <?php $counter = 0; ?>
        <?php foreach ( $subscribe_urls as $subscribe_url ) { ?>
            <?php if(!empty( $subscribe_url['url'])): ?>
                <a href="<?php echo esc_url( $subscribe_url['url'] ) ?>" target="_blank" rel="noopener noreferrer">
                    <img src="<?php echo esc_url( SSP_PLUGIN_URL . '/assets/icons/subscribe/' . $subscribe_url['icon'] ) ?>">
                    <span style=""><?php echo esc_html( $subscribe_url['label'] ) ?></span>
                </a>
		        <?php $counter++; ?>
	        <?php endif; ?>
        <?php } ?>
        <?php if ($counter == 0): ?>
            <p><?php _e( 'You have no subscribe urls set, please go to Podcast → Settings → Feed Details to set you your subscribe urls.', 'seriously-simple-podcasting' ) ?></p>
        <?php endif; ?>
    </div>
</div>
