<?php
/**
 * @var int $step_number
 * @var array $step_urls
 * @var string $podmotor_account_email
 * @var string $podmotor_account_api_token
 * */
?>

<div class="ssp-onboarding ssp-onboarding-step-4">
	<?php include __DIR__ . '/steps-header.php'; ?>

	<div class="ssp-onboarding__settings">
		<div class="ssp-onboarding__settings-header">
			<h1>Connect to Castos Hosting & Analytics</h1>
		</div>
		<div class="ssp-onboarding-step-4-info js-hosting-step-1">
			<div class="ssp-onboarding-step-4-info__left">
				<h2>Castos gives you the tools to grow your audience.</h2>

				<div class="ssp-onboarding-step-4-info__links">
					<h4>Distribute to</h4>
					<ul>
						<li>
							<a href="#">
								<svg width="17" height="18" viewBox="0 0 17 18" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M9.80903 17.1565C10.0903 16.137 10.5473 13.6409 10.5473 12.4807C10.5473 11.2503 9.42232 10.9338 8.29732 10.9338C7.13716 10.9338 6.04732 11.2503 6.04732 12.4807C6.04732 13.6409 6.46919 16.1721 6.75044 17.1565C6.92622 17.8245 7.62935 17.9651 8.29732 17.9651C8.93013 17.9651 9.63325 17.8245 9.80903 17.1565ZM4.35982 8.12135C4.35982 5.97681 6.04732 4.25416 8.19185 4.219C10.3715 4.14869 12.1997 5.94166 12.2348 8.12135C12.2348 9.10572 11.8833 10.0198 11.2153 10.7581C11.1801 10.8284 11.145 10.9338 11.2153 11.0042C11.3911 11.2854 11.5317 11.5667 11.602 11.9182C11.6372 12.0588 11.8129 12.1292 11.9536 12.0237C12.9731 11.0393 13.6411 9.66822 13.6411 8.12135C13.6059 5.16822 11.1801 2.74244 8.19185 2.81275C5.27388 2.84791 2.95357 5.20338 2.95357 8.12135C2.95357 9.66822 3.58638 11.0393 4.60591 12.0237C4.74653 12.1292 4.92232 12.0588 4.95747 11.9182C5.02778 11.5667 5.16841 11.2854 5.34419 11.0042C5.4145 10.9338 5.37935 10.8284 5.34419 10.7581C4.67622 10.0198 4.35982 9.10572 4.35982 8.12135ZM8.29732 0.000251417C3.727 -0.0349048 0.0355964 3.62135 0.000440168 8.19166C-0.0347161 11.637 2.0395 14.6253 4.99263 15.8909C5.13325 15.9612 5.30903 15.8206 5.27388 15.6799C5.23872 15.3284 5.13325 14.801 5.0981 14.4846C5.0981 14.4143 5.02778 14.344 4.99263 14.344C2.88325 13.1838 1.40669 10.9338 1.40669 8.26197C1.40669 4.4651 4.46528 1.37135 8.29732 1.37135C12.0942 1.37135 15.1879 4.4651 15.1879 8.26197C15.1879 10.8987 13.7114 13.1838 11.5668 14.344C11.5317 14.344 11.4614 14.4143 11.4614 14.4846C11.4262 14.801 11.3208 15.3284 11.2856 15.6799C11.2504 15.8206 11.4262 15.9612 11.5668 15.8909C14.52 14.6253 16.5942 11.7073 16.5942 8.26197C16.5942 3.69166 12.8676 0.000251417 8.29732 0.000251417ZM8.29732 5.5901C7.03169 5.5901 6.04732 6.60963 6.04732 7.8401C6.04732 9.10572 7.03169 10.0901 8.29732 10.0901C9.52778 10.0901 10.5473 9.10572 10.5473 7.8401C10.5473 6.60963 9.52778 5.5901 8.29732 5.5901Z" fill="#94A3B8"/>
								</svg>
							</a>
						</li>
						<li>
							<a href="#">
								<svg width="16" height="19" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M9.40957 4.60547C7.68691 4.67578 3.43301 5.16797 3.43301 8.75391C3.43301 12.5859 8.31972 12.7617 9.90176 10.2656C10.1127 10.6172 11.1322 11.5664 11.4838 11.918L13.4877 9.94922C13.4877 9.94922 12.3627 9.03516 12.3627 8.08594V2.91797C12.3627 2.00391 11.4838 0 8.39004 0C5.29629 0 3.6791 1.93359 3.6791 3.69141L6.24551 3.9375C6.80801 2.17969 8.14394 2.17969 8.14394 2.17969C9.58535 2.17969 9.40957 3.23438 9.40957 4.60547ZM9.40957 7.66406C9.40957 10.4766 6.45644 10.0547 6.45644 8.26172C6.45644 6.60938 8.21426 6.25781 9.40957 6.22266V7.66406ZM14.1908 13.3945C14.4721 13.043 14.2611 12.8672 13.9799 12.9727C7.51113 16.0664 3.46816 13.5 0.901755 11.918C0.725974 11.8125 0.444724 11.9531 0.690818 12.1992C1.56972 13.2539 4.38222 15.75 8.03847 15.75C11.7299 15.75 13.9096 13.7461 14.1908 13.3945ZM15.5971 13.5C15.808 12.9375 15.9486 12.1641 15.808 11.9883C15.6322 11.7422 14.7533 11.707 14.1908 11.7773C13.6283 11.8477 12.7846 12.1992 12.89 12.375C12.9252 12.4805 12.9955 12.4453 13.3822 12.4102C13.7689 12.375 14.8236 12.2344 15.0697 12.5156C15.2807 12.7969 14.7182 14.2031 14.6127 14.4492C14.5072 14.6602 14.6478 14.7305 14.8236 14.5898C15.0346 14.4141 15.351 14.0273 15.5971 13.5Z" fill="#94A3B8"/>
								</svg>
							</a>
						</li>
						<li>
							<a href="#">
								<svg width="19" height="18" viewBox="0 0 19 18" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M9.58301 0C4.7666 0 0.864258 3.9375 0.864258 8.71875C0.864258 13.5352 4.7666 17.4375 9.58301 17.4375C14.3643 17.4375 18.3018 13.5352 18.3018 8.71875C18.3018 3.9375 14.3643 0 9.58301 0ZM13.0986 12.832C12.958 12.832 12.8525 12.7969 12.7471 12.7266C10.5322 11.3906 8.00098 11.3555 5.46973 11.8477C5.3291 11.8828 5.15332 11.9531 5.04785 11.9531C4.69629 11.9531 4.48535 11.6719 4.48535 11.3906C4.48535 11.0391 4.69629 10.8633 4.97754 10.793C7.86035 10.1602 10.7783 10.2305 13.3096 11.707C13.5205 11.8477 13.626 11.9883 13.626 12.3047C13.626 12.6211 13.3799 12.832 13.0986 12.832ZM14.0479 10.5469C13.8721 10.5469 13.7314 10.4414 13.626 10.4062C11.4111 9.10547 8.1416 8.57812 5.22363 9.35156C5.04785 9.38672 4.97754 9.45703 4.80176 9.45703C4.4502 9.45703 4.13379 9.14062 4.13379 8.75391C4.13379 8.40234 4.30957 8.15625 4.66113 8.05078C5.64551 7.76953 6.66504 7.55859 8.10645 7.55859C10.3916 7.55859 12.6064 8.12109 14.3291 9.17578C14.6104 9.31641 14.751 9.5625 14.751 9.84375C14.7158 10.2305 14.4346 10.5469 14.0479 10.5469ZM15.1377 7.875C14.9619 7.875 14.8564 7.80469 14.6807 7.73438C12.1846 6.22266 7.71973 5.87109 4.80176 6.67969C4.69629 6.71484 4.52051 6.78516 4.34473 6.78516C3.8877 6.78516 3.53613 6.39844 3.53613 5.94141C3.53613 5.44922 3.85254 5.20312 4.16895 5.09766C5.39941 4.74609 6.77051 4.57031 8.28223 4.57031C10.8486 4.57031 13.5557 5.09766 15.4893 6.25781C15.7705 6.39844 15.9463 6.60938 15.9463 7.03125C15.9463 7.52344 15.5596 7.875 15.1377 7.875Z" fill="#94A3B8"/>
								</svg>
							</a>
						</li>
						<li>
							<a href="#">
								<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M17.458 8.92969C17.458 8.36719 17.3877 7.94531 17.3174 7.48828H9.02051V10.4766H13.9424C13.7666 11.7773 12.4658 14.2383 9.02051 14.2383C6.03223 14.2383 3.60645 11.7773 3.60645 8.71875C3.60645 3.83203 9.37207 1.58203 12.4658 4.57031L14.8564 2.28516C13.3447 0.878906 11.3408 0 9.02051 0C4.16895 0 0.301758 3.90234 0.301758 8.71875C0.301758 13.5703 4.16895 17.4375 9.02051 17.4375C14.0479 17.4375 17.458 13.9219 17.458 8.92969Z" fill="#94A3B8"/>
								</svg>
							</a>
						</li>
						<li>
							<a href="#">
								<svg width="20" height="10" viewBox="0 0 20 10" fill="none" xmlns="http://www.w3.org/2000/svg">
									<rect x="0.458008" y="1.55" width="3" height="7.75" fill="#94A3B8"/>
									<rect x="4.4" y="0.100008" width="3" height="8.5" fill="#94A3B8"/>
									<rect x="8.46" y="1.25" width="3" height="8.75" fill="#94A3B8"/>
									<rect x="12.55" y="0.25" width="3" height="9.08333" fill="#94A3B8"/>
									<rect x="16.458008" y="0.8" width="3" height="6.91667" fill="#94A3B8"/>
								</svg>
							</a>
						</li>
						<li>
							<a href="#">
								<svg width="19" height="18" viewBox="0 0 19 18" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M9.45801 18C4.47043 18 0.458008 13.9876 0.458008 9C0.458008 4.01243 4.47043 0 9.45801 0C14.4456 0 18.458 4.01243 18.458 9C18.458 13.9876 14.4456 18 9.45801 18ZM9.45801 14.4376L10.133 13.7626L9.45801 11.1751L8.78301 13.7626L9.45801 14.4376ZM8.59558 14.4L8.25808 15.6751L9.04558 14.8876L8.59558 14.4ZM10.3204 14.4L9.87043 14.85L10.6579 15.6375L10.3204 14.4ZM10.9581 16.7249L9.45801 15.2251L7.95793 16.7249C8.44551 16.8001 8.93308 16.875 9.45801 16.875C9.98293 16.875 10.4705 16.8374 10.9581 16.7249ZM9.45801 1.125C5.10808 1.125 1.58301 4.65007 1.58301 9C1.58301 12.4499 3.79543 15.3749 6.87051 16.425L8.55801 10.1626C8.18293 9.9 7.95793 9.45 7.95793 8.96243C7.95793 8.13758 8.63293 7.46258 9.45801 7.46258C10.2831 7.46258 10.9581 8.13758 10.9581 8.96243C10.9581 9.45 10.7331 9.86242 10.358 10.1626L12.0455 16.425C15.1206 15.3749 17.333 12.4499 17.333 9C17.333 4.65007 13.8079 1.125 9.45801 1.125ZM14.5954 12.8999C14.408 13.1249 14.0705 13.2001 13.8079 13.0124C13.5456 12.825 13.508 12.4875 13.6954 12.2625C13.6954 12.2625 14.7079 10.9125 14.7079 9C14.7079 7.0875 13.6954 5.7375 13.6954 5.7375C13.508 5.5125 13.5456 5.175 13.8079 4.98758C14.0705 4.79993 14.408 4.87508 14.5954 5.10008C14.6706 5.175 15.8329 6.71243 15.8329 9C15.8329 11.2876 14.6706 12.825 14.5954 12.8999ZM11.8581 11.4374C11.6331 11.2124 11.6704 10.8 11.8954 10.575C11.8954 10.575 12.4579 9.97493 12.4579 9C12.4579 8.02507 11.8954 7.46258 11.8954 7.425C11.6704 7.2 11.6704 6.82493 11.8581 6.56258C12.0831 6.3 12.4206 6.3 12.6456 6.525C12.6829 6.56258 13.5829 7.49993 13.5829 9C13.5829 10.5001 12.6829 11.4374 12.6456 11.475C12.4206 11.7 12.0831 11.6624 11.8581 11.4374ZM7.05793 11.4374C6.83293 11.6624 6.49543 11.7 6.27043 11.475C6.23308 11.4001 5.33308 10.5001 5.33308 9C5.33308 7.49993 6.23308 6.59993 6.27043 6.525C6.49543 6.3 6.87051 6.3 7.05793 6.56258C7.28293 6.78758 7.24558 7.2 7.02058 7.425C7.02058 7.46258 6.45808 8.02507 6.45808 9C6.45808 9.97493 7.02058 10.575 7.02058 10.575C7.24558 10.8 7.28293 11.1751 7.05793 11.4374ZM5.10808 13.0124C4.84551 13.2001 4.50801 13.1249 4.32058 12.8999C4.24543 12.825 3.08308 11.2876 3.08308 9C3.08308 6.71243 4.24543 5.175 4.32058 5.10008C4.50801 4.87508 4.84551 4.79993 5.10808 4.98758C5.37043 5.175 5.40801 5.5125 5.22058 5.7375C5.22058 5.7375 4.20808 7.0875 4.20808 9C4.20808 10.9125 5.22058 12.2625 5.22058 12.2625C5.40801 12.4875 5.33308 12.825 5.10808 13.0124Z" fill="#94A3B8"/>
								</svg>
							</a>
						</li>
						<li>
							<a href="#">
								<svg width="19" height="18" viewBox="0 0 19 18" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M18.4619 9C18.4619 13.9707 14.4317 18 9.45996 18C4.48822 18 0.458008 13.9707 0.458008 9C0.458008 4.02933 4.48822 0 9.45996 0C14.4317 0 18.4619 4.02933 18.4619 9ZM2.4585 9C2.4585 5.134 5.59318 2 9.46002 2C13.3269 2 16.4615 5.134 16.4615 9H14.7112C14.7112 6.1005 12.3601 3.75 9.46002 3.75C6.55989 3.75 4.20888 6.1005 4.20888 9C4.20888 11.8995 6.55989 14.25 9.46002 14.25V16C5.59318 16 2.4585 12.866 2.4585 9ZM9.46002 13.2C7.13991 13.2 5.2591 11.3196 5.2591 9C5.2591 6.6804 7.13991 4.8 9.46002 4.8C11.7801 4.8 13.6609 6.6804 13.6609 9H12.1333C12.1333 7.5239 10.9364 6.32727 9.46002 6.32727C7.9836 6.32727 6.7867 7.5239 6.7867 9C6.7867 10.4761 7.9836 11.6727 9.46002 11.6727V13.2Z" fill="#94A3B8"/>
								</svg>
							</a>
						</li>
					</ul>
				</div>

				<ul class="ssp-onboarding-step-4-info__usps">
					<li>Unlimited storage and podcasts on all plans.</li>
					<li>Advanced analytics.</li>
					<li>YouTube Republishing and Automated Transcriptions.</li>
					<li>Individualized Private Podcasting.</li>
				</ul>
			</div>
			<div class="ssp-onboarding-step-4-info__right">
				<a href="#have-account" class="ssp-onboarding-step-4-info-right__button ssp-onboarding-step-4-info-button">
					<span class="ssp-onboarding-step-4-info-button__title button-title">I have a Castos account</span>
					<span class="ssp-onboarding-step-4-info-button__description button-description">Let’s connect SSP and Castos through our API.</span>
				</a>
				<a href="#start-free-trial" class="ssp-onboarding-step-4-info-right__button ssp-onboarding-step-4-info-button">
					<span class="ssp-onboarding-step-4-info-button__title button-title">Start free trial on Castos</span>
					<span class="ssp-onboarding-step-4-info-button__description button-description">No Credit Card Required</span>
				</a>
				<a href="<?php echo $step_urls[ $step_number + 1 ] ?>" class="ssp-onboarding-step-4-info-right__button ssp-onboarding-step-4-info-button">
					<span class="ssp-onboarding-step-4-info-button__title button-title">Skip</span>
					<span class="ssp-onboarding-step-4-info-button__description button-description">Not interested right now.</span>
				</a>
			</div>
		</div>
		<div class="js-hosting-step-2" style="display: none">
			<div class="ssp-onboarding__hosting-steps js-hosting-registration">
				<div class="ssp-onboarding__hosting-step">
					<a href="https://app.castos.com/register" target="_blank">
						<span class="ssp-onboarding__hosting-step--header">Sign-up</span>
						<span class="ssp-onboarding__hosting-step--info">
						Create your account at <span>app.castos.com</span>
					</span>
					</a>
				</div>

				<div class="ssp-onboarding__hosting-step">
					<a href="https://app.castos.com/api-details" target="_blank">
						<span class="ssp-onboarding__hosting-step--header">Complete details below</span>
						<span class="ssp-onboarding__hosting-step--info">
						Get your API key from <span>app.castos.com/api-details</span>
					</span>
					</a>
				</div>
			</div>
			<form class="ssp-onboarding__settings-body" action="<?php echo $step_urls[ $step_number + 1 ] ?>" method="post">
				<div class="ssp-onboarding__settings-item">
					<h2>Your Email</h2>
					<label for="podmotor_account_email" class="description">
						The email address you used to register your Castos account.
					</label>
					<input id="podmotor_account_email" type="text" class="js-onboarding-validate-token-field" name="podmotor_account_email" value="<?php echo $podmotor_account_email ?>">
				</div>

				<div class="ssp-onboarding__settings-item">
					<h2>Castos API Token</h2>
					<label for="podmotor_account_api_token" class="description">
						Available from your Castos account dashboard.
					</label>
					<input id="podmotor_account_api_token" type="text" class="js-onboarding-validate-token-field" name="podmotor_account_api_token" value="<?php echo $podmotor_account_api_token ?>">
				</div>

				<div class="ssp-onboarding__submit">
					<button id="validate_api_credentials" type="button" class="button validate-token js-onboarding-validate-token" data-validating-txt="Validating Credentials" data-valid-txt="Valid Credentials" data-initial-txt="Validate Credentials" >
						Validate Credentials
					</button>
					<?php wp_nonce_field( 'ss_podcasting_castos-hosting', 'podcast_settings_tab_nonce', false ); ?>
					<span class="validate-api-credentials-message"></span>
					<button type="submit" disabled="disabled">Proceed</button>
				</div>
			</form>
		</div>
	</div>
	<?php include __DIR__ . '/steps-footer.php'; ?>
</div>
