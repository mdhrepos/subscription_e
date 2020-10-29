<?php

/**
 * Scheduler for subscription enhancer events that uses the Action Scheduler
 *
 * @class ENR_Action_Scheduler
 * @package Class
 */
class ENR_Action_Scheduler {

	/**
	 * An internal cache of action hooks and corresponding date types.
	 *
	 * @var array An array of $action_hook => $date_type values
	 */
	protected static $action_hooks = array(
		'enr_woocommerce_scheduled_subscription_auto_renewal_reminder'     => 'next_payment',
		'enr_woocommerce_scheduled_subscription_manual_renewal_reminder'   => 'next_payment',
		'enr_woocommerce_scheduled_subscription_price_changed_reminder'    => 'next_payment',
		'enr_woocommerce_scheduled_subscription_shipping_fulfilment_order' => 'next_payment',
		'enr_woocommerce_scheduled_subscription_expiration_reminder'       => 'end'
			) ;

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'woocommerce_subscription_status_updated', __CLASS__ . '::maybe_schedule_when_status_updated', 0, 2 ) ;
		add_action( 'woocommerce_subscription_date_updated', __CLASS__ . '::maybe_schedule_when_date_updated', 0, 2 ) ;
		add_action( 'woocommerce_subscription_date_deleted', __CLASS__ . '::maybe_schedule_when_date_updated', 0, 2 ) ;
	}

	/**
	 * Get the args to set on the scheduled action.
	 *
	 * @param string $date_type Can be 'trial_end', 'next_payment', 'expiration', 'end_of_prepaid_term' or a custom date type
	 * @param object $subscription An instance of WC_Subscription object
	 * @return array Array of name => value pairs stored against the scheduled action.
	 */
	public static function get_action_args( $date_type, $subscription ) {
		return apply_filters( 'woocommerce_subscriptions_scheduled_action_args', array( 'subscription_id' => $subscription->get_id() ), $date_type, $subscription ) ;
	}

	/**
	 * Schedule the multiple reminders before the end time.
	 * 
	 * @param mixed $end_time
	 * @param string $hook
	 * @param array $days_to_remind
	 * @param array $action_args
	 */
	public static function schedule_reminders( $end_time, $hook, $days_to_remind, $action_args ) {
		if ( '' === $days_to_remind || false === $days_to_remind ) {
			return ;
		}

		$days_to_remind = array_map( 'trim', explode( ',', $days_to_remind ) ) ;
		$days_to_remind = _enr_get_dates( time(), $end_time, $days_to_remind ) ;

		if ( ! empty( $days_to_remind ) ) {
			foreach ( $days_to_remind as $count => $timestamp ) {
				as_schedule_single_action( $timestamp, $hook, $action_args ) ;
			}
		}
	}

	/**
	 * When a subscription's status is updated, maybe schedule some events.
	 *
	 * @param object $subscription An instance of a WC_Subscription object
	 * @param string $new_status
	 * @param string $old_status
	 */
	public static function maybe_schedule_when_status_updated( $subscription, $new_status ) {
		switch ( $new_status ) {
			case 'active':
				self::maybe_schedule_when_date_updated( $subscription, 'next_payment' ) ;
				self::maybe_schedule_when_date_updated( $subscription, 'end' ) ;
				break ;
			case 'pending-cancel':
			case 'on-hold':
			case 'cancelled':
			case 'switched':
			case 'expired':
			case 'trash':
				self::unschedule_all_actions( $subscription ) ;
				break ;
		}
	}

	/**
	 * When a subscription's date is updated, maybe schedule some events.
	 *
	 * @param WC_Subscription $subscription
	 * @param string $date_type Can be 'trial_end', 'next_payment', 'end', 'end_of_prepaid_term' or a custom date type
	 */
	public static function maybe_schedule_when_date_updated( $subscription, $date_type ) {

		if ( ! $subscription->has_status( 'active' ) ) {
			self::unschedule_all_actions( $subscription ) ;
			return ;
		}

		$timestamp   = $subscription->get_time( $date_type ) ;
		$action_args = self::get_action_args( $date_type, $subscription ) ;

		switch ( $date_type ) {
			case 'next_payment':
				if ( $timestamp > 0 ) {
					$payment_scheduled = as_next_scheduled_action( 'woocommerce_scheduled_subscription_payment', $action_args ) ;
					$day_to_remind     = absint( get_option( ENR_PREFIX . 'notify_subscription_price_update_before' ) ) ;

					if ( $timestamp !== $payment_scheduled ) {
						self::unschedule_actions( $action_args, 'next_payment' ) ;

						if ( $day_to_remind > 0 ) {
							$remind_timestamp = _enr_get_time( 'timestamp', array( 'time' => "-{$day_to_remind} days", 'base' => $timestamp ) ) ;

							if ( $remind_timestamp ) {
								as_schedule_single_action( $remind_timestamp, 'enr_woocommerce_scheduled_subscription_price_changed_reminder', $action_args ) ;
							}
						}

						if ( $subscription->is_manual() ) {
							self::schedule_reminders( $timestamp, 'enr_woocommerce_scheduled_subscription_manual_renewal_reminder', get_option( ENR_PREFIX . 'send_manual_renewal_reminder_before' ), $action_args ) ;
						} else {
							self::schedule_reminders( $timestamp, 'enr_woocommerce_scheduled_subscription_auto_renewal_reminder', get_option( ENR_PREFIX . 'send_auto_renewal_reminder_before' ), $action_args ) ;
						}

						if ( ENR_Shipping_Cycle::can_be_scheduled( $subscription ) ) {
							ENR_Shipping_Cycle::schedule_shipping_fulfilment_orders( $subscription, $action_args ) ;
						}
					}
				} else {
					self::unschedule_actions( $action_args, 'next_payment' ) ;
				}
				break ;
			case 'end':
				if ( $timestamp > 0 ) {
					$expiration_scheduled = as_next_scheduled_action( 'woocommerce_scheduled_subscription_expiration', $action_args ) ;

					if ( $timestamp !== $expiration_scheduled ) {
						as_unschedule_all_actions( 'enr_woocommerce_scheduled_subscription_expiration_reminder', $action_args ) ;
						self::schedule_reminders( $timestamp, 'enr_woocommerce_scheduled_subscription_expiration_reminder', get_option( ENR_PREFIX . 'send_expiry_reminder_before' ), $action_args ) ;
					}
				} else {
					as_unschedule_all_actions( 'enr_woocommerce_scheduled_subscription_expiration_reminder', $action_args ) ;
				}
				break ;
		}
	}

	/**
	 * Unschedule actions by date_type in bulk.
	 * 
	 * @param array $action_args
	 * @param string $date_type
	 */
	public static function unschedule_actions( $action_args, $date_type ) {
		foreach ( self::$action_hooks as $hook => $_date_type ) {
			if ( $_date_type === $date_type ) {
				as_unschedule_all_actions( $hook, $action_args ) ;
			}
		}
	}

	/**
	 * Unschedule all actions in bulk.
	 * 
	 * @param object $subscription An instance of a WC_Subscription object
	 */
	public static function unschedule_all_actions( $subscription ) {
		foreach ( self::$action_hooks as $hook => $date_type ) {
			as_unschedule_all_actions( $hook, self::get_action_args( $date_type, $subscription ) ) ;
		}
	}

}

ENR_Action_Scheduler::init() ;
