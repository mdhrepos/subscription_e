<?php
/**
 * Processing Shipping Fulfillment Order Email.
 *
 * This template can be overridden by copying it to yourtheme/enhancer-for-woocommerce-subscriptions/emails/customer-processing-shipping-fulfilment-order.php.
 */
defined( 'ABSPATH' ) || exit ;

do_action( 'woocommerce_email_header', $email_heading, $email ) ;
?>
<p>
	<?php
	/* translators: %s: Subscription number */
	printf( esc_html__( 'Just to let you know &mdash; your shipping fulfillment order for Subscription #%s is created and it is now being processed.', 'enhancer-for-woocommerce-subscriptions' ), esc_html( $subscription->get_order_number() ) ) ;
	?>
</p>

<?php
do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email ) ;

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email ) ;

do_action( 'woocommerce_email_customer_details', $subscription, $sent_to_admin, $plain_text, $email ) ;

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ) ;
}

do_action( 'woocommerce_email_footer', $email ) ;
