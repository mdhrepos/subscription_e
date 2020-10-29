<?php

/**
 * Subscription Price Updated Email.
 *
 * This template can be overridden by copying it to yourtheme/enhancer-for-woocommerce-subscriptions/emails/plain/customer-subscription-price-updated.php.
 */
defined( 'ABSPATH' ) || exit ;

echo esc_html( $email_heading ) . "\n\n" ;

esc_html_e( 'The subscription price for your subscription has been updated. You have to pay the updated price for the future renewals. Here\'s the details of your subscription.', 'enhancer-for-woocommerce-subscriptions' ) ;

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n" ;

// translators: placeholder is localised new price string
echo sprintf( esc_html__( 'New Price: %s', 'enhancer-for-woocommerce-subscriptions' ), wp_kses_post( $to_price_string ) ) . "\n" ;
// translators: placeholder is localised old price string
echo sprintf( esc_html__( 'Old Price: %s', 'enhancer-for-woocommerce-subscriptions' ), wp_kses_post( $from_price_string ) ) . "\n" ;

do_action( 'woocommerce_email_order_meta', $subscription, $sent_to_admin, $plain_text, $email ) ;

// translators: view subscription url
echo "\n" . sprintf( esc_html_x( 'View Subscription: %s', 'in plain emails for subscription information', 'enhancer-for-woocommerce-subscriptions' ), esc_url( $subscription->get_view_order_url() ) ) . "\n" ;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

do_action( 'woocommerce_email_customer_details', $subscription, $sent_to_admin, $plain_text, $email ) ;

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) ;
	echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n" ;
}

echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ;
