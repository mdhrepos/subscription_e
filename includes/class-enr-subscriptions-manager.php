<?php

/**
 * Subscriptions Management Class 
 *
 * @class ENR_Subscriptions_Manager
 * @package Class
 */
class ENR_Subscriptions_Manager {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'woocommerce_checkout_create_subscription', __CLASS__ . '::update_subscription_meta', 10, 4 ) ;
		add_action( 'woocommerce_scheduled_subscription_payment', __CLASS__ . '::maybe_update_old_subscription_price', -1 ) ;

		add_action( 'enr_woocommerce_scheduled_subscription_price_changed_reminder', __CLASS__ . '::remind_subscription_price_changed_before_renewal' ) ;
		add_action( 'enr_woocommerce_scheduled_subscription_auto_renewal_reminder', __CLASS__ . '::remind_before_renewal' ) ;
		add_action( 'enr_woocommerce_scheduled_subscription_manual_renewal_reminder', __CLASS__ . '::remind_before_renewal' ) ;
		add_action( 'enr_woocommerce_scheduled_subscription_expiration_reminder', __CLASS__ . '::remind_before_expiry' ) ;
		add_action( 'enr_woocommerce_scheduled_subscription_shipping_fulfilment_order', __CLASS__ . '::create_shipping_fulfilment_order' ) ;
	}

	/*
	  |--------------------------------------------------------------------------
	  | Helper Methods
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Return the array of price changed data for the subscription.
	 * 
	 * @param WC_Subscription $subscription
	 * @return array
	 */
	public static function get_prices_changed( $subscription ) {
		$changed            = array() ;
		$amount_is_editable = $subscription->is_manual() || $subscription->payment_method_supports( 'subscription_amount_changes' ) ;

		if ( ! $amount_is_editable ) {
			return $changed ;
		}

		$items = $subscription->get_items( 'line_item' ) ;

		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				$product = $item->get_product() ;

				if ( $product && $product->is_type( array( 'subscription', 'subscription_variation' ) ) ) {
					$new_price     = floatval( wc_get_price_excluding_tax( $product, array( 'qty' => 1 ) ) ) ;
					$current_price = floatval( $subscription->get_item_total( $item, false, false ) ) ;

					if ( $new_price !== $current_price ) {
						$changed = array(
							'new'  => $new_price,
							'old'  => $current_price,
							'item' => $item
								) ;
						break ;
					}
				}
			}
		}

		return $changed ;
	}

	/**
	 * Update subscription meta while creating subscription.
	 * 
	 * @param WC_Subscription $subscription
	 * @param array $posted_data
	 * @param WC_Order $order
	 * @param WC_Cart $cart
	 */
	public static function update_subscription_meta( $subscription, $posted_data, $order, $cart ) {
		$meta = array(
			'enable_seperate_shipping_cycle' => 'no',
			'shipping_period_interval'       => '',
			'shipping_period'                => '',
			'allow_cancelling_to'            => 'use-storewide',
			'allow_cancelling_after'         => '0',
			'allow_cancelling_before_due'    => '0',
				) ;

		foreach ( $meta as $key => $default_value ) {
			update_post_meta( $subscription->get_id(), ENR_PREFIX . $key, wcs_cart_pluck( $cart, ENR_PREFIX . $key, $default_value ) ) ;
		}
	}

	/**
	 * When the subscription is preparing to renew ensure whether we need to update the old subscription price to new price or leave it as old price.
	 * 
	 * @param int $subscription_id
	 */
	public static function maybe_update_old_subscription_price( $subscription_id ) {
		if ( 'new-price' !== get_option( ENR_PREFIX . 'apply_old_subscription_price_as', 'old-price' ) ) {
			return ;
		}

		$subscription = wcs_get_subscription( $subscription_id ) ;

		if ( $subscription ) {
			$changed_prices = self::get_prices_changed( $subscription ) ;

			if ( ! empty( $changed_prices ) ) {
				$row_total = $changed_prices[ 'new' ] * $changed_prices[ 'item' ]->get_quantity() ;

				$changed_prices[ 'item' ]->set_subtotal( $row_total ) ;
				$changed_prices[ 'item' ]->set_total( $row_total ) ;
				$changed_prices[ 'item' ]->save() ;
				$subscription->calculate_totals() ;
			}
		}
	}

	/*
	  |--------------------------------------------------------------------------
	  | Action Scheduler Callback Methods
	  |--------------------------------------------------------------------------
	 */

	/**
	 * Remind users about any changes in the subscription price before the renewal.
	 * 
	 * @param int $subscription_id The ID of a 'shop_subscription' post
	 */
	public static function remind_subscription_price_changed_before_renewal( $subscription_id ) {
		$subscription = wcs_get_subscription( $subscription_id ) ;

		if ( ! $subscription ) {
			return ;
		}

		$changed_prices = self::get_prices_changed( $subscription ) ;

		if ( ! empty( $changed_prices ) ) {
			$price_args = array(
				'currency'                    => $subscription->get_currency(),
				'subscription_period'         => $subscription->get_billing_period(),
				'subscription_interval'       => $subscription->get_billing_interval(),
				'display_excluding_tax_label' => false,
					) ;

			$price_args[ 'recurring_amount' ] = $changed_prices[ 'old' ] ;
			$from_price_string                = wcs_price_string( $price_args ) ;

			$price_args[ 'recurring_amount' ] = $changed_prices[ 'new' ] ;
			$to_price_string                  = wcs_price_string( $price_args ) ;

			do_action( 'enr_wc_subscriptions_remind_subscription_price_changed_before_renewal', $subscription, $from_price_string, $to_price_string ) ;
		}
	}

	/**
	 * Remind users before the subscription is going to renew automatically/manually.
	 * 
	 * @param int $subscription_id The ID of a 'shop_subscription' post
	 */
	public static function remind_before_renewal( $subscription_id ) {
		$subscription = wcs_get_subscription( $subscription_id ) ;

		if ( ! $subscription ) {
			return ;
		}

		if ( $subscription->is_manual() ) {
			do_action( 'enr_wc_subscriptions_remind_before_manual_renewal', $subscription ) ;
		} else {
			do_action( 'enr_wc_subscriptions_remind_before_auto_renewal', $subscription ) ;
		}
	}

	/**
	 * Remind users before the subscription gets expired.
	 * 
	 * @param int $subscription_id The ID of a 'shop_subscription' post
	 */
	public static function remind_before_expiry( $subscription_id ) {
		$subscription = wcs_get_subscription( $subscription_id ) ;

		if ( ! $subscription ) {
			return ;
		}

		do_action( 'enr_wc_subscriptions_remind_before_expiry', $subscription ) ;
	}

	/**
	 * Create the shipping fulfilment order.
	 * 
	 * @param int $subscription_id The ID of a 'shop_subscription' post
	 */
	public static function create_shipping_fulfilment_order( $subscription_id ) {
		$subscription = wcs_get_subscription( $subscription_id ) ;

		if ( ! $subscription ) {
			return ;
		}

		$shipping_fulfilment_order = wcs_create_order_from_subscription( $subscription, 'enr_shipping_fulfilment_order' ) ;

		if ( is_wp_error( $shipping_fulfilment_order ) ) {
			do_action( 'enr_wc_subscriptions_failed_to_create_shipping_fulfilment_order', $shipping_fulfilment_order, $subscription ) ;
			return ;
		}

		// Update as shipping fulfilment order.
		update_post_meta( $shipping_fulfilment_order->get_id(), ENR_PREFIX . 'shipping_fulfilment_order', 'yes' ) ;

		$shipping_fulfilment_orders       = $subscription->get_meta( ENR_PREFIX . 'shipping_fulfilment_orders' ) ;
		$shipping_fulfilment_orders       = is_array( $shipping_fulfilment_orders ) ? $shipping_fulfilment_orders : array() ;
		$shipping_fulfilment_orders[]     = $shipping_fulfilment_order->get_id() ;
		$shipping_fulfilment_orders_count = count( $shipping_fulfilment_orders ) ;

		// Make sure to calculate the total for the order since we are saving the line total/subtotal alone zero not calculating it while creating the order.
		$shipping_fulfilment_order->calculate_totals() ;
		/* translators: 1: shipping fulfilment orders count */
		$shipping_fulfilment_order->update_status( 'processing', sprintf( __( '%s shipping fulfilment order for the subscription.', 'enhancer-for-woocommerce-subscriptions' ), _enr_get_number_suffix( $shipping_fulfilment_orders_count ) ) ) ;
		/* translators: 1: shipping fulfilment orders count 2: shipping fulfilment order admin url 3: shipping fulfilment order ID */
		$subscription->add_order_note( sprintf( __( '%1$s shipping fulfilment order <a href="%2$s">#%3$s</a>', 'enhancer-for-woocommerce-subscriptions' ), _enr_get_number_suffix( $shipping_fulfilment_orders_count ), esc_url( wcs_get_edit_post_link( $shipping_fulfilment_order->get_id() ) ), $shipping_fulfilment_order->get_id() ) ) ;

		// Add relation to the order.
		WCS_Related_Order_Store::instance()->add_relation( $shipping_fulfilment_order, $subscription, 'enr_shipping_fulfilment' ) ;

		// Update the order as shipping fulfilment.
		update_post_meta( $subscription->get_id(), ENR_PREFIX . 'shipping_fulfilment_orders', $shipping_fulfilment_orders ) ;

		do_action( 'enr_wc_subscriptions_shipping_fulfilment_order_created', $subscription, $shipping_fulfilment_order, $shipping_fulfilment_orders_count ) ;
	}

}

ENR_Subscriptions_Manager::init() ;
