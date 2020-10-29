<?php
/**
 * Our Templates
 *
 * Functions for the templating system.
 */
defined( 'ABSPATH' ) || exit ;

/**
 * Enhanced cancel option to subscriber.
 * 
 * @param array $actions
 * @param WC_Subscription $subscription
 * @return array
 */
function _enr_account_cancel_options_to_subscriber( $actions, $subscription ) {
	if ( empty( $actions[ 'cancel' ] ) ) {
		return $actions ;
	}

	$hide               = true ;
	$start_timestamp    = $subscription->get_time( 'start', 'gmt' ) ;
	$next_due_timestamp = $subscription->get_time( 'next_payment', 'gmt' ) ;

	if ( 'yes' === get_option( ENR_PREFIX . 'allow_cancelling', 'yes' ) ) {
		if ( 'override-storewide' === $subscription->get_meta( ENR_PREFIX . 'allow_cancelling_to' ) ) {
			$no_of_days_to_wait_to_cancel = $subscription->get_meta( ENR_PREFIX . 'allow_cancelling_after' ) ;
			$hide_cancel_before_due       = $subscription->get_meta( ENR_PREFIX . 'allow_cancelling_before_due' ) ;
		} else {
			$no_of_days_to_wait_to_cancel = get_option( ENR_PREFIX . 'allow_cancelling_after', '0' ) ;
			$hide_cancel_before_due       = get_option( ENR_PREFIX . 'allow_cancelling_before_due', '0' ) ;
		}

		if ( is_numeric( $no_of_days_to_wait_to_cancel ) ) {
			$no_of_days_to_wait_to_cancel = absint( $no_of_days_to_wait_to_cancel ) ;

			if ( 0 === $no_of_days_to_wait_to_cancel || 0 === $start_timestamp ) {
				$hide = false ;
			} else {
				$min_time_user_wait_to_cancel = $start_timestamp + ( $no_of_days_to_wait_to_cancel * DAY_IN_SECONDS ) ;
				$hide                         = time() < $min_time_user_wait_to_cancel ? true : false ;
			}
		}

		if ( ! $hide && is_numeric( $hide_cancel_before_due ) ) {
			$hide_cancel_before_due = absint( $hide_cancel_before_due ) ;

			if ( 0 === $hide_cancel_before_due || 0 === $next_due_timestamp ) {
				$hide = false ;
			} else {
				$min_time_user_can_cancel_before_due = $next_due_timestamp - ( $hide_cancel_before_due * DAY_IN_SECONDS ) ;
				$hide                                = time() > $min_time_user_can_cancel_before_due ? true : false ;
			}
		}
	}

	if ( $hide ) {
		unset( $actions[ 'cancel' ] ) ;
	}

	return $actions ;
}

/**
 * Display the shipping cycle details.
 * 
 * @param WC_Subscription $subscription
 */
function _enr_account_shipping_details( $subscription ) {
	if ( 'yes' === $subscription->get_meta( ENR_PREFIX . 'enable_seperate_shipping_cycle' ) ) {
		?>
		<tr>
			<td><?php esc_html_e( 'Delivered every', 'enhancer-for-woocommerce-subscriptions' ) ; ?></td>
			<td><?php echo esc_html( wcs_get_subscription_period_strings( $subscription->get_meta( ENR_PREFIX . 'shipping_period_interval' ), $subscription->get_meta( ENR_PREFIX . 'shipping_period' ) ) ) ; ?></td>
		</tr>
		<?php
	}
}
