<?php
/**
 * Subscription Price Updated Email.
 *
 * This template can be overridden by copying it to yourtheme/enhancer-for-woocommerce-subscriptions/emails/customer-subscription-price-updated.php.
 */
defined( 'ABSPATH' ) || exit ;

do_action( 'woocommerce_email_header', $email_heading, $email ) ;
?>
<p><?php esc_html_e( 'The subscription price for your subscription has been updated. You have to pay the updated price for the future renewals. Here\'s the details of your subscription.', 'enhancer-for-woocommerce-subscriptions' ) ; ?></p>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
	<thead>
		<tr>
			<th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Subscription', 'enhancer-for-woocommerce-subscriptions' ) ; ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php echo esc_html_x( 'New Price', 'table headings in notification email', 'enhancer-for-woocommerce-subscriptions' ) ; ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php echo esc_html_x( 'Old Price', 'table headings in notification email', 'enhancer-for-woocommerce-subscriptions' ) ; ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="td" width="1%" style="text-align:left; vertical-align:middle;">
				<a href="<?php echo esc_url( $subscription->get_view_order_url() ) ; ?>">#<?php echo esc_html( $subscription->get_order_number() ) ; ?></a>
			</td>
			<td class="td" style="text-align:left; vertical-align:middle;">
				<?php echo wp_kses_post( $to_price_string ) ; ?>
			</td>
			<td class="td" style="text-align:left; vertical-align:middle;">
				<?php echo wp_kses_post( $from_price_string ) ; ?>
			</td>
		</tr>
	</tbody>
</table>
<br/>

<?php
do_action( 'woocommerce_email_customer_details', $subscription, $sent_to_admin, $plain_text, $email ) ;

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ) ;
}

do_action( 'woocommerce_email_footer', $email ) ;
