<div>
	Subscribe:
	<div class="ssp-subscribe-buttons">
		<?php foreach ( $subscribe_urls as $subscribe_url ) { ?>
            <?php if(!empty( $subscribe_url['url'])): ?>
                <a href="<?php echo $subscribe_url['url'] ?>" target="_blank" rel="noopener noreferrer">
                    <img src="<?php echo SSP_PLUGIN_URL ?>/assets/icons/subscribe/<?php echo $subscribe_url['icon'] ?>">
                    <span style=""><?php echo $subscribe_url['label'] ?></span>
                </a>
			<?php endif; ?>
		<?php } ?>
	</div>
</div>
