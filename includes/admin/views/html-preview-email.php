<?php
defined( 'ABSPATH' ) || exit ;
?>
<div class="wc-backbone-modal enr_email_preview_wrapper">
	<div class="wc-backbone-modal-content">
		<section class="wc-backbone-modal-main" role="main">
			<header class="wc-backbone-modal-header">
				<h1>{{ data.email_title }}</h1>
				<button class="modal-close modal-close-link dashicons dashicons-no-alt">
					<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'enhancer-for-woocommerce-subscriptions' ) ; ?></span>
				</button>
			</header>
			<article>
				<div class="enr_email_content_wrapper">                    
					{{{ data.email_content }}}
				</div>
			</article>
		</section>
	</div>
</div>
<div class="wc-backbone-modal-backdrop modal-close"></div>
