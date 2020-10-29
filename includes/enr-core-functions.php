<?php

defined( 'ABSPATH' ) || exit ;

include_once('enr-time-functions.php') ;
include_once('enr-template-functions.php') ;

/**
 * Check if a given order was created for shipping fulfilment.
 *
 * @param WC_Order|int $order The WC_Order object or ID of a WC_Order order.
 */
function _enr_order_contains_shipping( $order ) {

	if ( ! is_a( $order, 'WC_Abstract_Order' ) ) {
		$order = wc_get_order( $order ) ;
	}

	$related_subscriptions = wcs_get_subscriptions_for_order( $order, array( 'order_type' => 'enr_shipping_fulfilment' ) ) ;

	if ( wcs_is_order( $order ) && ! empty( $related_subscriptions ) ) {
		$is_shipping_fulfilment_order = true ;
	} else {
		$is_shipping_fulfilment_order = false ;
	}

	return apply_filters( 'woocommerce_subscriptions_is_enr_shipping_fulfilment_order', $is_shipping_fulfilment_order, $order ) ;
}

/**
 * Get the type in which the array is sorted by.
 * 
 * @param array $array
 * @return boolean|string
 */
function _enr_array_sorted_by( $array ) {
	$o_array = $array ;

	$asc = $o_array ;
	sort( $asc ) ;
	if ( $o_array === $asc ) {
		return 'asc' ;
	}

	$desc = $o_array ;
	rsort( $desc ) ;
	if ( $o_array === $desc ) {
		return 'desc' ;
	}

	return false ;
}

/**
 * Get Number Suffix to Display.
 * 
 * @param int $number
 * @return string
 */
function _enr_get_number_suffix( $number ) {
	// Special case 'teenth'
	if ( ( $number / 10 ) % 10 != 1 ) {
		// Handle 1st, 2nd, 3rd
		switch ( $number % 10 ) {
			case 1:
				return $number . 'st' ;
			case 2:
				return $number . 'nd' ;
			case 3:
				return $number . 'rd' ;
		}
	}
	// Everything else is 'nth'
	return $number . 'th' ;
}
