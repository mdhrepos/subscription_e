<?php

defined( 'ABSPATH' ) || exit ;

/**
 * Handle Enhancer for Woocommerce Subscriptions Ajax Event.
 * 
 * @class ENR_Ajax
 * @package Class
 */
class ENR_Ajax {

	/**
	 * Init ENR_Ajax.
	 */
	public static function init() {
		//Get Ajax Events.
		$prefix      = ENR_PREFIX ;
		$ajax_events = array(
			'collect_preview_email_inputs' => false,
			'preview_email'                => false
				) ;

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( "wp_ajax_{$prefix}{$ajax_event}", __CLASS__ . "::{$ajax_event}" ) ;

			if ( $nopriv ) {
				add_action( "wp_ajax_nopriv_{$prefix}{$ajax_event}", __CLASS__ . "::{$ajax_event}" ) ;
			}
		}
	}

	/**
	 * Collect the preview email inputs.
	 */
	public static function collect_preview_email_inputs() {
		check_ajax_referer( 'enr-collect-preview-email-inputs', 'security' ) ;

		if ( ! isset( $_GET[ 'email_id' ] ) ) {
			wp_die() ;
		}

		$email_id = sanitize_title( wp_unslash( $_GET[ 'email_id' ] ) ) ;

		ob_start() ;
		include 'admin/views/html-add-preview-email-inputs.php' ;
		$email_inputs = ob_get_clean() ;

		wp_send_json_success( array(
			'email_id'     => $email_id,
			'email_inputs' => $email_inputs
		) ) ;
	}

	/**
	 * Preview email.
	 */
	public static function preview_email() {
		check_ajax_referer( 'enr-preview-email', 'security' ) ;

		try {
			if ( ! isset( $_GET[ 'data' ] ) ) {
				throw new Exception( __( 'Invalid inputs', 'credits-for-woocommerce' ) ) ;
			}

			$requested        = $_GET ;
			$raw_data         = wp_parse_args( wp_unslash( $requested[ 'data' ] ) ) ;
			$email_id         = sanitize_title( wp_unslash( $raw_data[ 'email_id' ] ) ) ;
			$email_input_args = ( array ) $raw_data[ 'input_args' ] ;
			$emails           = WC()->mailer()->get_emails() ;

			foreach ( $emails as $email ) {
				if ( $email_id === $email->id ) {
					foreach ( $email_input_args as $input_arg => $input_value ) {
						$email->{$input_arg} = $input_value ;
					}

					if ( 'order' === $email->object_type ) {
						$email->object = wc_get_order( $email->object ) ;

						if ( ! $email->object ) {
							throw new Exception( __( 'Invalid Order Number', 'enhancer-for-woocommerce-subscriptions' ) ) ;
						}
					} else if ( 'subscription' === $email->object_type ) {
						$email->object = wcs_get_subscription( $email->object ) ;

						if ( ! $email->object ) {
							throw new Exception( __( 'Invalid Subscription Number', 'enhancer-for-woocommerce-subscriptions' ) ) ;
						}
					}

					if ( isset( $email->order ) ) {
						$email->order = wc_get_order( $email->order ) ;

						if ( ! $email->order ) {
							throw new Exception( __( 'Invalid Order Number', 'enhancer-for-woocommerce-subscriptions' ) ) ;
						}
					}

					if ( isset( $email->subscriptions ) ) {
						$email->subscriptions = wcs_get_subscriptions_for_switch_order( $email->object ) ;
					}

					if ( isset( $email->retry ) ) {
						$email->retry = WCS_Retry_Manager::store()->get_last_retry_for_order( $email->object->get_id() ) ;
					}

					if ( isset( $email->from_price_string ) ) {
						$email->from_price_string = wcs_price_string( array(
							'recurring_amount'    => is_numeric( $email->from_price_string ) ? $email->from_price_string : 0,
							'subscription_period' => 'month',
								) ) ;
					}

					if ( isset( $email->to_price_string ) ) {
						$email->to_price_string = wcs_price_string( array(
							'recurring_amount'    => is_numeric( $email->to_price_string ) ? $email->to_price_string : 0,
							'subscription_period' => 'month',
								) ) ;
					}

					do_action( 'enr_wc_subscriptions_before_preview_email', $email ) ;

					wp_send_json_success( array(
						'email_id'      => $email->id,
						'email_title'   => $email->get_title(),
						'email_content' => $email->style_inline( $email->get_content() )
					) ) ;
				}
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'error' => esc_html( $e->getMessage() ) ) ) ;
		}
	}

}

ENR_Ajax::init() ;
