<?php
defined( 'ABSPATH' ) || exit ;
?>
<div class="wc-backbone-modal enr_email_inputs_preview_wrapper">
	<div class="wc-backbone-modal-content">
		<section class="wc-backbone-modal-main" role="main">
			<header class="wc-backbone-modal-header">
				<h1><?php esc_html_e( 'Email Preview', 'enhancer-for-woocommerce-subscriptions' ) ; ?></h1>
				<button class="modal-close modal-close-link dashicons dashicons-no-alt">
					<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'enhancer-for-woocommerce-subscriptions' ) ; ?></span>
				</button>
			</header>
			<article>
				<div class="enr_email_inputs_wrapper"> 
					{{{ data.email_inputs }}}
					<input type="hidden" name="email_id" value="{{ data.email_id }}">
				</div>
			</article>
			<footer>                
				<div class="inner">
					<button id="btn-ok" class="enr-preview-submit button button-primary"><?php esc_html_e( 'Preview', 'enhancer-for-woocommerce-subscriptions' ) ; ?></button>
				</div>
			</footer>
		</section>
	</div>
</div>
<div class="wc-backbone-modal-backdrop modal-close"></div>
