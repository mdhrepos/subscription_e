<?php
defined( 'ABSPATH' ) || exit ;

switch ( $email_id ) {
	case 'customer_completed_renewal_order':
	case 'customer_processing_renewal_order':
	case 'customer_on_hold_renewal_order':
	case 'customer_renewal_invoice':
	case 'new_renewal_order':
		?>
		<p class="enr_preview_email_inputs">
			<label for="order_id"><?php esc_html_e( 'Enter Order Number', 'enhancer-for-woocommerce-subscriptions' ) ; ?></label>
			<input type="text" name="input_args[object]" value="">
			<input type="hidden" name="input_args[object_type]" value="order">
		</p>
		<p class="enr_preview_email_description">
			<span class="description"><?php esc_html_e( 'Note: To Preview the Email with the exact data, give the correct Order Number.', 'enhancer-for-woocommerce-subscriptions' ) ; ?></span>
		</p>
		<?php
		break ;
	case 'customer_completed_switch_order':
	case 'new_switch_order':
		?>
		<p class="enr_preview_email_inputs">
			<label for="order_id"><?php esc_html_e( 'Enter Order Number', 'enhancer-for-woocommerce-subscriptions' ) ; ?></label>
			<input type="text" name="input_args[object]" value="">
			<input type="hidden" name="input_args[object_type]" value="order">
			<input type="hidden" name="input_args[subscriptions]" value="populate">
		</p>
		<p class="enr_preview_email_description">
			<span class="description"><?php esc_html_e( 'Note: To Preview the Email with the exact data, give the correct Order Number.', 'enhancer-for-woocommerce-subscriptions' ) ; ?></span>
		</p>
		<?php
		break ;
	case 'customer_payment_retry':
	case 'payment_retry':
		?>
		<p class="enr_preview_email_inputs">
			<label for="order_id"><?php esc_html_e( 'Enter Order Number', 'enhancer-for-woocommerce-subscriptions' ) ; ?></label>
			<input type="text" name="input_args[object]" value="">
			<input type="hidden" name="input_args[object_type]" value="order">
			<input type="hidden" name="input_args[retry]" value="populate">
		</p>
		<p class="enr_preview_email_description">
			<span class="description"><?php esc_html_e( 'Note: To Preview the Email with the exact data, give the correct Order Number.', 'enhancer-for-woocommerce-subscriptions' ) ; ?></span>
		</p>
		<?php
		break ;
	case 'suspended_subscription':
	case 'cancelled_subscription':
	case 'expired_subscription':
	case '_enr_customer_auto_renewal_reminder':
	case '_enr_customer_manual_renewal_reminder':
	case '_enr_customer_expiry_reminder':
		?>
		<p class="enr_preview_email_inputs">
			<label for="subscription_id"><?php esc_html_e( 'Enter Subscription Number', 'enhancer-for-woocommerce-subscriptions' ) ; ?></label>
			<input type="text" name="input_args[object]" value="">
			<input type="hidden" name="input_args[object_type]" value="subscription">
		</p>
		<?php
		break ;
	case '_enr_customer_processing_shipping_fulfilment_order':
		?>
		<p class="enr_preview_email_inputs">
			<label for="subscription_id"><?php esc_html_e( 'Enter Subscription Number', 'enhancer-for-woocommerce-subscriptions' ) ; ?></label>
			<input type="text" name="input_args[object]" value="">
			<input type="hidden" name="input_args[object_type]" value="subscription">
		</p>
		<p class="enr_preview_email_inputs">
			<label for="shipping_fulfilment_order_id"><?php esc_html_e( 'Enter Order Number', 'enhancer-for-woocommerce-subscriptions' ) ; ?></label>
			<input type="text" name="input_args[order]" value="">
			<input type="hidden" name="input_args[shipping_fulfilment_orders_count]" value="1">
		</p>
		<p class="enr_preview_email_description">
			<span class="description"><?php esc_html_e( 'Note: To Preview the Email with the exact data, give the correct Subscription Number and Order Number in the respective fields.', 'enhancer-for-woocommerce-subscriptions' ) ; ?></span>
		</p>
		<?php
		break ;
	case '_enr_customer_subscription_price_updated':
		?>
		<p class="enr_preview_email_inputs">
			<label for="subscription_id"><?php esc_html_e( 'Enter Subscription Number', 'enhancer-for-woocommerce-subscriptions' ) ; ?></label>
			<input type="text" name="input_args[object]" value="">
			<input type="hidden" name="input_args[object_type]" value="subscription">
			<input type="hidden" name="input_args[from_price_string]" value="50">
			<input type="hidden" name="input_args[to_price_string]" value="100">
		</p>
		<p class="enr_preview_email_description">
			<span class="description"><?php esc_html_e( 'Note: The "Old Price" and "New Price" displayed in the Preview is only for reference purpose and it is not the actual data.', 'enhancer-for-woocommerce-subscriptions' ) ; ?></span>
		</p>
		<?php
		break ;
	default:
		do_action( 'enr_wc_subscriptions_preview_email_inputs', $email_id ) ;
		break ;
}
?>
