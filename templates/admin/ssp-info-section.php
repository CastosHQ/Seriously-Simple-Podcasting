<?php
/**
 * @see \SeriouslySimplePodcasting\Controllers\Admin_Controller::render_ssp_info_section()
 *
 * @var string $title
 * @var string $plan
 * @var bool $is_connected
 * */

?>
<div class="ssp-admin-header">
	<div class="ssp-admin-header__container">
		<div class="ssp-admin-header__logo">
			<svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
				<circle cx="30" cy="30" r="28.0408" fill="#330F63"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M30 60C46.5685 60 60 46.5685 60 30C60 13.4315 46.5685 0 30 0C13.4315 0 0 13.4315 0 30C0 46.5685 13.4315 60 30 60ZM30 58.7755C45.8923 58.7755 58.7755 45.8923 58.7755 30C58.7755 14.1077 45.8923 1.22449 30 1.22449C14.1077 1.22449 1.22449 14.1077 1.22449 30C1.22449 45.8923 14.1077 58.7755 30 58.7755Z" fill="#330F63"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M15.9389 20.1204C16.2919 19.9057 16.7482 19.6281 17.2652 19.7142C17.5477 19.7613 17.8483 19.9894 18.1948 20.2523C18.7495 20.6733 19.422 21.1836 20.3264 21.1836C21.3698 21.1836 21.9193 20.9366 22.2379 20.7934C22.3679 20.735 22.4595 20.6938 22.5305 20.6938C22.7754 20.6938 23.1428 21.0612 22.653 21.4285C22.5723 21.489 22.4716 21.5728 22.3516 21.6728C21.7431 22.1794 20.6361 23.101 19.1019 23.5101C18.8104 23.5879 18.5405 23.6533 18.2911 23.7137C16.9697 24.0339 16.2273 24.2137 15.9183 25.3469C15.5509 26.6938 15.7958 32.0816 20.3264 32.0816C22.2031 32.0816 23.6805 31.3463 25.4115 30.4848C27.8595 29.2665 30.8144 27.7959 36.1223 27.7959C45.1836 27.7959 47.0203 35.5101 47.0203 39.4285C47.0203 43.3469 42.6121 48.7346 35.0203 48.7346C29.6798 48.7346 27.8613 46.9358 26.9174 46.0021C26.7878 45.8739 26.6748 45.7621 26.5713 45.6734C27.1836 47.2652 30.2448 50.6693 36.1223 50.5714C36.0407 51.0203 34.0162 51.9183 26.5713 51.9183C17.2652 51.9183 10.0407 43.5918 10.0407 36.1224C10.0407 30.8057 11.4056 27.7224 12.1923 25.9452C12.5108 25.2256 12.7346 24.7202 12.7346 24.3673C12.7346 23.2266 11.4595 22.086 10.2952 21.0443C10.2096 20.9678 10.1246 20.8917 10.0407 20.8163C8.81622 19.7142 8.57132 17.6326 8.69377 17.2653C8.81622 16.8979 9.30602 16.7755 9.42846 17.5101C9.55091 18.2448 10.7754 18.9795 11.6325 18.9795C11.9836 18.9795 12.1704 18.9384 12.3526 18.8984C12.6153 18.8405 12.8688 18.7848 13.5917 18.8571C14.2034 18.9183 14.9434 19.6929 15.3713 20.1408C15.4398 20.2125 15.5002 20.2758 15.5509 20.3265C15.6598 20.2902 15.7902 20.2109 15.9389 20.1204ZM27.306 42.3673C27.7794 42.3673 28.1632 41.9835 28.1632 41.5102C28.1632 41.0368 27.7794 40.653 27.306 40.653C26.8326 40.653 26.4489 41.0368 26.4489 41.5102C26.4489 41.9835 26.8326 42.3673 27.306 42.3673Z" fill="white"/>
				<path d="M23.0204 14.9388C22.5306 15.3198 22.4567 16.2871 22.96 16.7816C23.464 17.3529 24.1225 17.3425 24.8028 16.8421C29.3878 13.4694 41.5102 13.5919 46.7755 16.7816C47.3719 17.1429 48.0844 17.2306 48.5409 16.766C48.5409 16.766 48.5409 16.766 48.5789 16.7273C49.0734 16.2239 49.1021 15.3158 48.4858 14.8858C42.2449 10.5307 28.5306 10.6531 23.0204 14.9388Z" fill="#CEB5FD"/>
				<path d="M25.9592 19.347C25.2245 19.7143 25.2245 20.5714 25.5919 21.1837C25.9592 21.7959 26.7325 21.8083 27.4286 21.4286C32.6101 19.1144 38.7802 18.8525 44.0817 21.551C44.674 21.8525 45.6735 21.9184 46.0409 21.1837C46.3915 20.4824 46.4082 19.8367 45.5511 19.3469C40.3003 16.3465 31.9435 15.9972 25.9592 19.347Z" fill="#CEB5FD"/>
				<path d="M28.6531 24.1225C27.9184 24.4898 27.796 25.2857 28.1633 25.8367C28.7347 26.6939 29.5102 26.4303 30.1701 26.1871C34.6531 24.5349 37.1021 24.6122 41.3878 26.1267C42.2566 26.4337 42.8572 26.449 43.347 25.8367C43.7143 25.2245 43.6404 24.3981 42.7347 24C37.4734 21.6875 33.0613 21.6735 28.6531 24.1225Z" fill="#CEB5FD"/>
			</svg>
			<div class="ssp-admin-header__logo-text">
				<span class="ssp-admin-header__logo-title">Seriously Simple Podcasting</span>
				<a target="_blank" rel="noopener" class="ssp-admin-header__logo-button" href="https://castos.com/">
					<?php esc_html_e( 'By Castos', 'seriously-simple-podcasting' ); ?>
				</a>
			</div>
		</div>
		<div class="ssp-admin-header__info">
			<div class="ssp-admin-header__info-plan">
				<?php if ( $is_connected ) : ?>
					<?php echo $plan ? esc_html( $plan ) : esc_html( __( 'Connected', 'seriously-simple-podcasting' ) ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Free version', 'seriously-simple-podcasting' ); ?>
				<?php endif; ?>
			</div>

			<div class="ssp-admin-header__info-buttons">
				<?php if ( $plan ) : ?>
					<a target="_blank" rel="noopener" href="<?php echo esc_url( SSP_CASTOS_APP_URL . 'podcasts' ); ?>">
						<?php esc_html_e( 'Manage Account', 'seriously-simple-podcasting' ); ?>
					</a>
					<a target="_blank" rel="noopener" href="<?php echo esc_url( SSP_CASTOS_APP_URL . 'analytics?utm_source=ssp&utm_medium=view-stats&utm_campaign=header' ); ?>">
						<?php esc_html_e( 'View Analytics', 'seriously-simple-podcasting' ); ?>
					</a>
				<?php else : ?>
					<a target="_blank" rel="noopener" href="<?php echo esc_url( 'https://castos.com/pricing?utm_source=ssp&utm_medium=view-stats&utm_campaign=header' ); ?>">
						<?php esc_html_e( 'Upgrade', 'seriously-simple-podcasting' ); ?>
					</a>
					<a target="_blank" rel="noopener" href="<?php echo esc_url( 'https://castos.com/podcast-analytics?utm_source=ssp&utm_medium=view-stats&utm_campaign=header' ); ?>">
						<?php esc_html_e( 'Start Analytics', 'seriously-simple-podcasting' ); ?>
					</a>
				<?php endif; ?>

			</div>
		</div>
	</div>
</div>
