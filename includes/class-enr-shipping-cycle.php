<?php

/**
 * Shipping Cycle Product and Order handler.
 *
 * @class ENR_Shipping_Cycle
 * @package Class
 */
class ENR_Shipping_Cycle {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_filter( 'wcs_new_order_types', __CLASS__ . '::register_our_order_types' ) ;
		add_filter( 'wcs_additional_related_order_relation_types', __CLASS__ . '::register_our_order_relational_types' ) ;

		add_filter( 'woocommerce_subscriptions_product_price_string', __CLASS__ . '::get_product_price_string', 50, 2 ) ;
		add_filter( 'woocommerce_subscription_price_string', __CLASS__ . '::get_price_string', 50, 2 ) ;
		add_filter( 'woocommerce_cart_subscription_string_details', __CLASS__ . '::prepare_price_args_for_cart', 50, 2 ) ;
		add_filter( 'woocommerce_subscription_price_string_details', __CLASS__ . '::prepare_price_args_for_subscription', 50, 2 ) ;

		add_action( 'woocommerce_scheduled_subscription_payment', __CLASS__ . '::shipping_done', 2 ) ;
		add_filter( 'wcs_enr_shipping_fulfilment_order_items', __CLASS__ . '::save_line_total_as_zero' ) ;
		add_filter( 'woocommerce_subscriptions_admin_related_orders_to_display', __CLASS__ . '::ouput_shipping_fulfilment_orders', 10, 3 ) ;
		add_action( 'manage_shop_order_posts_custom_column', __CLASS__ . '::show_relationship', 10 ) ;
	}

	/*
	  |--------------------------------------------------------------------------
	  | Shipping Fulfilment Order Types Registration Methods
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Register our order types.
	 * 
	 * @param array $order_types
	 * @return array
	 */
	public static function register_our_order_types( $order_types ) {
		$order_types[] = 'enr_shipping_fulfilment_order' ;
		return $order_types ;
	}

	/**
	 * Register our order relational types.
	 * 
	 * @param array $order_types
	 * @return array
	 */
	public static function register_our_order_relational_types( $order_types ) {
		$order_types[] = 'enr_shipping_fulfilment' ;
		return $order_types ;
	}

	/*
	  |--------------------------------------------------------------------------
	  | Shipping Price String Methods
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Get the price string for the shipping cycle to display in shop/product page.
	 * 
	 * @param string $subscription_string
	 * @param WC_Product $product
	 * @return string
	 */
	public static function get_product_price_string( $subscription_string, $product ) {
		if ( self::shipping_cycle_enabled( $product ) ) {
			$subscription_string .= '</br>' ;
			$subscription_string .= self::prepare_price_string( $product->get_meta( ENR_PREFIX . 'shipping_period_interval' ), $product->get_meta( ENR_PREFIX . 'shipping_period' ) ) ;
		}

		return $subscription_string ;
	}

	/**
	 * Get the price string for the shipping cycle to display in cart/admin screen/my account page.
	 * 
	 * @param string $subscription_string
	 * @param array $subscription_details
	 * @return string
	 */
	public static function get_price_string( $subscription_string, $subscription_details ) {
		if ( isset( $subscription_details[ 'enr_shipping_cycle_enabled' ] ) ) {
			$subscription_string .= '&nbsp;</br>' ;
			$subscription_string .= self::prepare_price_string( $subscription_details[ 'enr_shipping_interval' ], $subscription_details[ 'enr_shipping_period' ] ) ;
		}

		return $subscription_string ;
	}

	/**
	 * Prepare the price string to display.
	 * 
	 * @param int $interval
	 * @param string $period
	 * @return string
	 */
	public static function prepare_price_string( $interval, $period ) {
		$shipping_string = '<span class="subscription-enr-shipping-cycle-details">' ;
		/* translators: 1: subscription period strings */
		$shipping_string .= sprintf( __( 'Delivered every %s', 'enhancer-for-woocommerce-subscriptions' ), wcs_get_subscription_period_strings( $interval, $period ) ) ;
		$shipping_string .= '</span>' ;

		return $shipping_string ;
	}

	/**
	 * Prepare the array of shipping data required to display the price string by cart.
	 * 
	 * @param array $args
	 * @param WC_Cart $cart
	 * @return array
	 */
	public static function prepare_price_args_for_cart( $args, $cart ) {
		if ( 'yes' === wcs_cart_pluck( $cart, ENR_PREFIX . 'enable_seperate_shipping_cycle' ) ) {
			$billing_interval = wcs_cart_pluck( $cart, 'subscription_period_interval' ) ;
			$billing_period   = wcs_cart_pluck( $cart, 'subscription_period' ) ;

			if ( 'day' === $billing_period && '1' === $billing_interval ) {
				return $args ;
			}

			$args[ 'enr_shipping_cycle_enabled' ] = true ;
			$args[ 'enr_shipping_interval' ]      = wcs_cart_pluck( $cart, ENR_PREFIX . 'shipping_period_interval' ) ;
			$args[ 'enr_shipping_period' ]        = wcs_cart_pluck( $cart, ENR_PREFIX . 'shipping_period' ) ;
		}

		return $args ;
	}

	/**
	 * Prepare the array of shipping data required to display the price string by subscription.
	 * 
	 * @param array $args
	 * @param WC_Subscription $subscription
	 * @return array
	 */
	public static function prepare_price_args_for_subscription( $args, $subscription ) {
		if ( 'yes' === $subscription->get_meta( ENR_PREFIX . 'enable_seperate_shipping_cycle' ) ) {
			if ( 'day' === $subscription->get_billing_period() && '1' === $subscription->get_billing_interval() ) {
				return $args ;
			}

			$args[ 'enr_shipping_cycle_enabled' ] = true ;
			$args[ 'enr_shipping_interval' ]      = $subscription->get_meta( ENR_PREFIX . 'shipping_period_interval' ) ;
			$args[ 'enr_shipping_period' ]        = $subscription->get_meta( ENR_PREFIX . 'shipping_period' ) ;
		}

		return $args ;
	}

	/*
	  |--------------------------------------------------------------------------
	  | Helper Methods
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Check if the shipping cycle is enabled ?
	 * 
	 * @param mixed $product A WC_Product object or product ID
	 * @return bool 
	 */
	public static function shipping_cycle_enabled( $product ) {
		if ( 'yes' !== $product->get_meta( ENR_PREFIX . 'enable_seperate_shipping_cycle' ) ) {
			return false ;
		}

		$billing_interval = WC_Subscriptions_Product::get_interval( $product ) ;
		$billing_period   = WC_Subscriptions_Product::get_period( $product ) ;

		if ( 'day' === $billing_period && '1' === $billing_interval ) {
			return false ;
		}

		return true ;
	}

	/**
	 * Check if the shipping cycle can be scheduled ?
	 * 
	 * @param WC_Subscription $subscription
	 * @return bool
	 */
	public static function can_be_scheduled( $subscription ) {
		if ( ! is_object( $subscription ) ) {
			$subscription = wcs_get_subscription( $subscription ) ;
		}

		if ( ! $subscription ) {
			return false ;
		}

		if ( 'yes' !== $subscription->get_meta( ENR_PREFIX . 'enable_seperate_shipping_cycle' ) ) {
			return false ;
		}

		if ( 'day' === $subscription->get_billing_period() && '1' === $subscription->get_billing_interval() ) {
			return false ;
		}

		// Prevent from Synced subscription
		if ( WC_Subscriptions_Synchroniser::subscription_contains_synced_product( $subscription ) ) {
			return false ;
		}

		// Prevent from Virtual subscription
		foreach ( $subscription->get_items( 'line_item' ) as $item ) {
			$product = $item->get_product() ;

			if ( $product && $product->is_virtual() ) {
				return false ;
			}
		}

		// Prevent from Trial subscription 
		if ( $subscription->get_time( 'trial_end' ) > time() ) {
			return false ;
		}

		return true ;
	}

	/**
	 * Retrieve the shipping dates to schedule before the given timestamp.
	 * 
	 * @param int $timestamp
	 * @param string $period
	 * @param int $period_interval
	 * @return array
	 */
	public static function get_shipping_dates( $timestamp, $period, $period_interval ) {
		$shipping_dates  = array() ;
		$timestamp       = absint( $timestamp ) ;
		$period_interval = absint( $period_interval ) ;

		if ( $timestamp > 0 && $period_interval > 0 ) {
			$next_shipping_time = _enr_get_time( 'timestamp', array( 'time' => wcs_add_time( $period_interval, $period, time() ) ) ) ;

			while ( $next_shipping_time < $timestamp ) {
				$shipping_dates[]   = $next_shipping_time ;
				$next_shipping_time = _enr_get_time( 'timestamp', array( 'time' => wcs_add_time( $period_interval, $period, $next_shipping_time ) ) ) ;
			}
		}

		return $shipping_dates ;
	}

	/**
	 * Prepare and save the shipping fulfilment dates once for every billing cycle.
	 * And schedule the shipping fulfilment orders.
	 * 
	 * @param WC_Subscription $subscription
	 * @param array $action_args
	 */
	public static function schedule_shipping_fulfilment_orders( $subscription, $action_args ) {
		$shipping_dates = self::get_shipping_dates( $subscription->get_time( 'next_payment' ), $subscription->get_meta( ENR_PREFIX . 'shipping_period' ), $subscription->get_meta( ENR_PREFIX . 'shipping_period_interval' ) ) ;

		if ( ! empty( $shipping_dates ) ) {
			foreach ( $shipping_dates as $shippment_time ) {
				if ( time() <= $shippment_time ) {
					as_schedule_single_action( $shippment_time, 'enr_woocommerce_scheduled_subscription_shipping_fulfilment_order', $action_args ) ;
				}
			}
		}

		update_post_meta( $subscription->get_id(), ENR_PREFIX . 'shipping_fulfilment_dates', $shipping_dates ) ;
	}

	/**
	 * Fire after the due date gets crossed. Save the shipping in renewal order.
	 * 
	 * @param int $subscription_id
	 */
	public static function shipping_done( $subscription_id ) {
		$subscription  = wcs_get_subscription( $subscription_id ) ;
		$renewal_order = $subscription->get_last_order( 'all', 'renewal' ) ;

		if ( $renewal_order ) {
			update_post_meta( $renewal_order->get_id(), ENR_PREFIX . 'shipping_fulfilment_dates', get_post_meta( $subscription_id, ENR_PREFIX . 'shipping_fulfilment_dates', true ) ) ;
			update_post_meta( $renewal_order->get_id(), ENR_PREFIX . 'shipping_fulfilment_orders', get_post_meta( $subscription_id, ENR_PREFIX . 'shipping_fulfilment_orders', true ) ) ;
		}

		delete_post_meta( $subscription_id, ENR_PREFIX . 'shipping_fulfilment_dates' ) ;
		delete_post_meta( $subscription_id, ENR_PREFIX . 'shipping_fulfilment_orders' ) ;
	}

	/**
	 * To prepare the shipping fulfilment order, save line total as zero.
	 * 
	 * @param WC_Order_Item[] $items
	 * @return array
	 */
	public static function save_line_total_as_zero( $items ) {
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				if ( isset( $item[ 'product_id' ] ) && 'line_item' === $item[ 'type' ] ) {
					$item->set_total( 0 ) ;
					$item->set_subtotal( 0 ) ;
					$item->save() ;
				}
			}
		}

		return $items ;
	}

	/**
	 * Displays the shipping fulfilment orders in the Related Orders meta box.
	 * 
	 * @param array $orders_to_display
	 * @param WC_Subscription[] $subscriptions
	 * @param object $post A WordPress post
	 * @return array
	 */
	public static function ouput_shipping_fulfilment_orders( $orders_to_display, $subscriptions, $post ) {
		$orders_by_type = array() ;

		if ( ! wcs_is_subscription( $post->ID ) && wcs_order_contains_renewal( $post->ID ) ) {
			$shipping_fulfilment_orders = get_post_meta( $post->ID, ENR_PREFIX . 'shipping_fulfilment_orders', true ) ;

			if ( ! empty( $shipping_fulfilment_orders ) ) {
				$orders_by_type[ 'shipping_fulfilment' ] = $shipping_fulfilment_orders ;
			}
		} else {
			foreach ( $subscriptions as $subscription ) {
				$orders_by_type[ 'shipping_fulfilment' ] = $subscription->get_related_orders( 'ids', 'enr_shipping_fulfilment' ) ;
			}
		}

		foreach ( $orders_by_type as $order_type => $orders ) {
			foreach ( $orders as $order_id ) {
				$order = wc_get_order( $order_id ) ;

				switch ( $order_type ) {
					case 'shipping_fulfilment':
						$relation = _x( 'Shipping Fulfilment Order', 'relation to order', 'enhancer-for-woocommerce-subscriptions' ) ;
						break ;
					default:
						$relation = _x( 'Unknown Order Type', 'relation to order', 'enhancer-for-woocommerce-subscriptions' ) ;
						break ;
				}

				if ( $order && ! $order->has_status( 'trash' ) ) {
					$order->update_meta_data( '_relationship', $relation ) ;
					$orders_to_display[] = $order ;
				}
			}
		}

		return $orders_to_display ;
	}

	/**
	 * Add column content to the WooCommerce -> Orders admin screen to indicate whether an
	 * order is a shipping of a subscription or a regular order.
	 *
	 * @param string $column The string of the current column
	 */
	public static function show_relationship( $column ) {
		global $post ;

		if ( 'subscription_relationship' === $column ) {
			if ( _enr_order_contains_shipping( $post->ID ) ) {
				echo '<img class="_enr_shipping_fulfilment_order_relationship" src="' . esc_attr( ENR_URL ) . '/assets/images/ship.png" title="' . esc_attr__( 'Shipping Fulfilment Order', 'enhancer-for-woocommerce-subscriptions' ) . '">' ;
			}
		}
	}

}

ENR_Shipping_Cycle::init() ;
