<?php

/**
 * WC Subscriptions Purchase Limit and Trial Limit handler.
 *
 * @class ENR_Subscriptions_Limiter
 * @package Class
 */
class ENR_Subscriptions_Limiter {

	/**
	 * Cache the product whether the subscription is limited to purchase or not?
	 * 
	 * @var array 
	 */
	protected static $is_purchasable_cache = array() ;

	/**
	 * Cache the product whether the trial is limited to onetime or not?
	 * 
	 * @var array 
	 */
	protected static $onetime_trial_cache = array() ;

	/**
	 * Hook in methods.
	 */
	public static function init() {
		if ( wcs_is_frontend_request() ) {
			add_filter( 'woocommerce_available_variation', __CLASS__ . '::add_variation_data', 10, 3 ) ;
			add_filter( 'woocommerce_subscriptions_product_trial_length', __CLASS__ . '::limit_trial', 99, 2 ) ;
			add_filter( 'woocommerce_subscriptions_product_limitation', __CLASS__ . '::remove_limit_on_product_level', 99, 2 ) ;
			add_filter( 'woocommerce_subscription_variation_is_purchasable', __CLASS__ . '::limit_subscription_variant_level', 99, 2 ) ;
		}

		add_action( 'wp_enqueue_scripts', __CLASS__ . '::frontend_script', 11 ) ;
	}

	/**
	 * Get the subscription's limit type before the variant level limit is applied.
	 *
	 * @param int|WC_Product $product A WC_Product object or the ID of a product
	 * @return string containing the limit type
	 */
	public static function get_product_limitation( $product ) {
		remove_filter( 'woocommerce_subscriptions_product_limitation', __CLASS__ . '::remove_limit_on_product_level', 99, 2 ) ;
		$limitation = wcs_get_product_limitation( $product ) ;
		add_filter( 'woocommerce_subscriptions_product_limitation', __CLASS__ . '::remove_limit_on_product_level', 99, 2 ) ;
		return $limitation ;
	}

	/**
	 * Check whether the product is purchasable to renew/resubscribe based upon variant/product level.
	 * 
	 * @param WC_Product $product
	 * @return boolean
	 */
	public static function is_renewable( $product ) {
		$is_renewable = false ;

		if ( isset( $_GET[ 'resubscribe' ] ) || false !== wcs_cart_contains_resubscribe() ) {
			$resubscribe_cart_item = wcs_cart_contains_resubscribe() ;
			$subscription_id       = ( isset( $_GET[ 'resubscribe' ] ) ) ? absint( $_GET[ 'resubscribe' ] ) : $resubscribe_cart_item[ 'subscription_resubscribe' ][ 'subscription_id' ] ;
			$subscription          = wcs_get_subscription( $subscription_id ) ;

			if ( false != $subscription && $subscription->has_product( $product->get_id() ) && wcs_can_user_resubscribe_to( $subscription ) ) {
				$is_renewable = true ;
			}
		} elseif ( isset( $_GET[ 'subscription_renewal' ] ) || wcs_cart_contains_renewal() ) {
			$is_renewable = true ;
		} elseif ( ! empty( WC()->session->cart ) ) {
			foreach ( WC()->session->cart as $cart_item ) {
				$item_id = ! empty( $cart_item[ 'variation_id' ] ) ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;

				if ( $product->get_id() == $item_id && ( isset( $cart_item[ 'subscription_renewal' ] ) || isset( $cart_item[ 'subscription_resubscribe' ] ) ) ) {
					$is_renewable = true ;
					break ;
				}
			}
		}

		return $is_renewable ;
	}

	/**
	 * Check whether the product is purchasable to switch based upon variant/product level.
	 * 
	 * @param WC_Product $product
	 * @return boolean
	 */
	public static function is_switchable( $product ) {
		$is_switchable = false ;

		if ( ! wcs_is_product_switchable_type( $product ) ) {
			return $is_switchable ;
		}

		$subscriptions = wcs_get_subscriptions( array(
			'customer_id' => get_current_user_id(),
			'status'      => self::get_product_limitation( $product ),
			'product_id'  => $product->get_id(),
				) ) ;

		// Adding to cart
		if ( isset( $_GET[ 'switch-subscription' ] ) && array_key_exists( sanitize_title( wp_unslash( $_GET[ 'switch-subscription' ] ) ), $subscriptions ) ) {
			$is_switchable = true ;

			return $is_switchable ;
		}

		$cart_contents = array() ;
		if ( WC_Subscriptions_Switcher::cart_contains_switches() ) {
			$cart_contents = WC()->cart->cart_contents ;
		} elseif ( isset( WC()->session->cart ) ) {
			$cart_contents = WC()->session->cart ;
		}

		// Check if the cart contains a switch for this specific product.
		foreach ( $cart_contents as $cart_item ) {
			$item_id = ! empty( $cart_item[ 'variation_id' ] ) ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] ;

			if ( $product->get_id() == $item_id && isset( $cart_item[ 'subscription_switch' ][ 'subscription_id' ] ) && array_key_exists( $cart_item[ 'subscription_switch' ][ 'subscription_id' ], $subscriptions ) ) {
				$is_switchable = true ;
				break ;
			}
		}

		return $is_switchable ;
	}

	/**
	 * Add the variation data on demand which will used upon selecting each variation.
	 * 
	 * @param array $variation_data
	 * @param WC_Product_Variable $variable
	 * @param WC_Product_Variation $variation
	 * @return array
	 */
	public static function add_variation_data( $variation_data, $variable, $variation ) {
		$user_id = get_current_user_id() ;

		if ( ! $user_id ) {
			return $variation_data ;
		}

		$limitation = self::get_product_limitation( $variation ) ;

		if ( 'no' !== $limitation && 'variant-level' === get_post_meta( $variable->get_id(), '_enr_variable_subscription_limit_level', true ) && ! $variation->is_purchasable() ) {
			$variation_data[ 'enr_limited_subscription_notice' ] = '<p class="enr-variation-wrapper enr-limited-subscription-notice notice">' . esc_html__( 'You have an active subscription to this product already.', 'enhancer-for-woocommerce-subscriptions' ) . '</p>' ;

			$resubscribe_link = wcs_get_users_resubscribe_link_for_product( $variation->get_id() ) ;

			if ( ! empty( $resubscribe_link ) && 'any' === $limitation && wcs_user_has_subscription( $user_id, $variation->get_id(), $limitation ) && ! wcs_user_has_subscription( $user_id, $variation->get_id(), 'active' ) && ! wcs_user_has_subscription( $user_id, $variation->get_id(), 'on-hold' ) ) {
				$variation_data[ 'enr_resubscribe_link' ] = '<p class="enr-variation-wrapper"><a href="' . esc_url( $resubscribe_link ) . '" class="woocommerce-button button product-resubscribe-link">' . esc_html__( 'Resubscribe', 'enhancer-for-woocommerce-subscriptions' ) . '</a></p>' ;
			}
		}

		return $variation_data ;
	}

	/**
	 * Limit the trial onetime for the user.
	 * 
	 * @param string $trial_length
	 * @param mixed $product A WC_Product object or product ID
	 * @return string
	 */
	public static function limit_trial( $trial_length, $product ) {
		if ( $trial_length <= 0 ) {
			return $trial_length ;
		}

		$user_id = get_current_user_id() ;

		if ( ! $user_id ) {
			return $trial_length ;
		}

		if ( isset( self::$onetime_trial_cache[ $user_id ][ $product->get_id() ] ) ) {
			return self::$onetime_trial_cache[ $user_id ][ $product->get_id() ] ? 0 : $trial_length ;
		}

		if ( $product->is_type( 'variation' ) ) {
			$parent_product = wc_get_product( $product->get_parent_id() ) ;
		} else {
			$parent_product = $product ;
		}

		if ( 'no' !== self::get_product_limitation( $parent_product ) ) {
			self::$onetime_trial_cache[ $user_id ][ $product->get_id() ] = false ;
			return $trial_length ;
		}

		if ( 'yes' !== get_post_meta( $parent_product->get_id(), '_enr_limit_trial_to_one', true ) ) {
			self::$onetime_trial_cache[ $user_id ][ $product->get_id() ] = false ;
			return $trial_length ;
		}

		$subscriptions = wcs_get_users_subscriptions( $user_id ) ;

		foreach ( $subscriptions as $subscription ) {
			if ( $subscription->has_product( $product->get_id() ) && ( '' !== $subscription->get_trial_period() || 0 !== $subscription->get_time( 'trial_end' ) ) ) {
				self::$onetime_trial_cache[ $user_id ][ $product->get_id() ] = true ;
				return 0 ;
			}
		}

		self::$onetime_trial_cache[ $user_id ][ $product->get_id() ] = false ;
		return $trial_length ;
	}

	/**
	 * Remove the limit product level and prepare to apply on variant level.
	 * 
	 * @param string $limitation
	 * @param WC_Product $product
	 * @return string
	 */
	public static function remove_limit_on_product_level( $limitation, $product ) {
		if ( 'no' === $limitation ) {
			return $limitation ;
		}

		if ( ! $product->is_type( array( 'variation', 'variable-subscription' ) ) ) {
			return $limitation ;
		}

		if ( $product->is_type( 'variation' ) ) {
			$parent_product = wc_get_product( $product->get_parent_id() ) ;
		} else {
			$parent_product = $product ;
		}

		if ( 'variant-level' !== get_post_meta( $parent_product->get_id(), '_enr_variable_subscription_limit_level', true ) ) {
			return $limitation ;
		}

		return 'no' ;
	}

	/**
	 * Limit the subscription by variant level.
	 * 
	 * @param bool $is_purchasable
	 * @param WC_Product $variation
	 * @return bool
	 */
	public static function limit_subscription_variant_level( $is_purchasable, $variation ) {
		if ( $is_purchasable ) {
			$user_id = get_current_user_id() ;

			if ( ! $user_id ) {
				return $is_purchasable ;
			}

			if ( isset( self::$is_purchasable_cache[ $user_id ][ $variation->get_id() ] ) ) {
				return self::$is_purchasable_cache[ $user_id ][ $variation->get_id() ] ;
			}

			$limitation = self::get_product_limitation( $variation ) ;

			if ( 'no' === $limitation || 'variant-level' !== get_post_meta( $variation->get_parent_id(), '_enr_variable_subscription_limit_level', true ) ) {
				self::$is_purchasable_cache[ $user_id ][ $variation->get_id() ] = $is_purchasable ;
				return self::$is_purchasable_cache[ $user_id ][ $variation->get_id() ] ;
			}

			if ( self::is_renewable( $variation ) || self::is_switchable( $variation ) ) {
				self::$is_purchasable_cache[ $user_id ][ $variation->get_id() ] = $is_purchasable ;
				return self::$is_purchasable_cache[ $user_id ][ $variation->get_id() ] ;
			}

			remove_filter( 'woocommerce_subscriptions_product_limitation', __CLASS__ . '::remove_limit_on_product_level', 99, 2 ) ;
			$is_limited_for_user = wcs_is_product_limited_for_user( $variation, $user_id ) ;
			add_filter( 'woocommerce_subscriptions_product_limitation', __CLASS__ . '::remove_limit_on_product_level', 99, 2 ) ;

			if ( $is_limited_for_user ) {
				$is_purchasable = false ;
			}

			self::$is_purchasable_cache[ $user_id ][ $variation->get_id() ] = $is_purchasable ;
		}

		return $is_purchasable ;
	}

	/**
	 * Perform script localization in frontend.
	 */
	public static function frontend_script() {
		global $post ;

		if ( $post && 'product' === get_post_type( $post ) ) {
			wp_register_script( 'enr-frontend', ENR_URL . '/assets/js/frontend.js', array( 'jquery' ), _enr()->get_version() ) ;

			wp_localize_script( 'enr-frontend', 'enr_frontend_params', array(
				'hide_variable_limited_notice' => 'no' !== self::get_product_limitation( $post->ID ) && 'variant-level' === get_post_meta( $post->ID, '_enr_variable_subscription_limit_level', true ) ? 'yes' : '',
			) ) ;

			wp_enqueue_script( 'enr-frontend' ) ;
		}
	}

}

ENR_Subscriptions_Limiter::init() ;
