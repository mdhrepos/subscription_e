<?php
/**
 * Auto Renewal Reminder Email.
 *
 * This template can be overridden by copying it to yourtheme/enhancer-for-woocommerce-subscriptions/emails/customer-auto-renewal-reminder.php.
 */
defined( 'ABSPATH' ) || exit ;

do_action( 'woocommerce_email_header', $email_heading, $email ) ;
?>
<p>
	<?php
	/* translators: 1: Subscription number 2: Subscription due date */
	printf( esc_html__( 'Your Subscription %1$s is due for renewal on %2$s. Please make sure that you have sufficient funds in your account.', 'enhancer-for-woocommerce-subscriptions' ), '<a href="' . esc_url( $subscription->get_view_order_url() ) . '">#' . esc_html( $subscription->get_order_number() ) . '</a>', esc_html( date_i18n( wc_date_format(), $subscription->get_time( 'next_payment', 'site' ) ) ) ) ;
	?>
</p>

<?php
do_action( 'woocommerce_email_customer_details', $subscription, $sent_to_admin, $plain_text, $email ) ;

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ) ;
}

do_action( 'woocommerce_email_footer', $email ) ;
