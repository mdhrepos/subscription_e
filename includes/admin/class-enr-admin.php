<?php

defined( 'ABSPATH' ) || exit ;

/**
 * Enhancer for WooCommerce Subscriptions Admin.
 * 
 * @class ENR_Admin
 * @package Class
 */
class ENR_Admin {

	/**
	 * Init ENR_Admin.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::admin_enqueues', 11 ) ;
		add_action( 'woocommerce_product_options_general_product_data', __CLASS__ . '::admin_edit_product_fields' ) ;
		add_action( 'woocommerce_variable_subscription_pricing', __CLASS__ . '::admin_edit_variation_fields', 10, 3 ) ;
		add_action( 'woocommerce_product_options_advanced', __CLASS__ . '::admin_edit_product_advanced_fields', 11 ) ;
		add_filter( 'woocommerce_subscription_settings', __CLASS__ . '::subscription_settings', 99 ) ;

		add_action( 'woocommerce_process_product_meta', __CLASS__ . '::save_subscription_meta' ) ;
		add_action( 'woocommerce_save_product_variation', __CLASS__ . '::save_subscription_variation_meta', 10, 2 ) ;
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function admin_enqueues() {
		wp_register_script( 'enr-admin', ENR_URL . '/assets/js/admin.js', array( 'jquery', 'wc-backbone-modal' ), _enr()->get_version() ) ;
		wp_register_style( 'enr-admin', ENR_URL . '/assets/css/admin.css', array(), _enr()->get_version() ) ;

		wp_localize_script( 'enr-admin', 'enr_admin_params', array(
			'period'                     => wcs_get_subscription_period_strings(),
			'preview_email_inputs_nonce' => wp_create_nonce( 'enr-collect-preview-email-inputs' ),
			'preview_email_nonce'        => wp_create_nonce( 'enr-preview-email' )
		) ) ;

		wp_enqueue_script( 'enr-admin' ) ;
		wp_enqueue_style( 'enr-admin' ) ;
	}

	/**
	 * Output the subscription ENR fields on the admin page "Edit Product" -> "General".
	 */
	public static function admin_edit_product_fields() {
		global $post ;
		include 'views/html-product-enr-options.php' ;
	}

	/**
	 * Output the subscription variation ENR fields on the admin page "Edit Product" -> "Variations".
	 */
	public static function admin_edit_variation_fields( $loop, $variation_data, $variation ) {
		include 'views/html-product-variation-enr-options.php' ;
	}

	/**
	 * Output the subscription ENR fields on the admin page "Edit Product" -> "Advanced".
	 */
	public static function admin_edit_product_advanced_fields() {

		echo '<div class="options_group show_if_variable-subscription hidden">' ;
		woocommerce_wp_select(
				array(
					'id'      => '_enr_variable_subscription_limit_level',
					'label'   => __( 'Limit subscription level', 'enhancer-for-woocommerce-subscriptions' ),
					'options' => array(
						'product-level' => __( 'Product Level', 'enhancer-for-woocommerce-subscriptions' ),
						'variant-level' => __( 'Variant Level', 'enhancer-for-woocommerce-subscriptions' ),
					),
				)
		) ;
		echo '</div>' ;

		echo '<div class="options_group show_if_subscription show_if_variable-subscription hidden">' ;
		woocommerce_wp_checkbox(
				array(
					'id'          => '_enr_limit_trial_to_one',
					'label'       => __( 'Limit trial to one', 'enhancer-for-woocommerce-subscriptions' ),
					'description' => __( 'Restrict customers to use trial only once', 'enhancer-for-woocommerce-subscriptions' ),
				)
		) ;
		echo '</div>' ;
	}

	/**
	 * Return the array of our settings to WC Subscriptions.
	 * 
	 * @param array $settings
	 * @return array
	 */
	public static function subscription_settings( $settings ) {
		$renewal_options_end = wp_list_filter( $settings, array( 'id' => 'woocommerce_subscriptions_renewal_options', 'type' => 'sectionend' ) ) ;

		array_splice( $settings, key( $renewal_options_end ), 0, array(
			array(
				'name'     => __( 'Subscription Price for Old Subscriptions', 'enhancer-for-woocommerce-subscriptions' ),
				'id'       => ENR_PREFIX . 'apply_old_subscription_price_as',
				'default'  => 'old-price',
				'type'     => 'select',
				'options'  => array(
					'old-price' => __( 'Old price', 'enhancer-for-woocommerce-subscriptions' ),
					'new-price' => __( 'New price', 'enhancer-for-woocommerce-subscriptions' )
				),
				'desc_tip' => true,
				'desc'     => __( 'If the subscription price for products are updated and if you want to update new price for the old subscriptions which are renewed hereafter, then select "New price" option. The customers will be notified by email regarding the subscription price update. Note: If the subscription is placed using Auto Renewal, then new price will be updated only if the payment gateway supports "amount change" subscription feature', 'enhancer-for-woocommerce-subscriptions' ),
			),
			array(
				'name'     => __( 'Notify Subscription Price Update for Old Subscriptions', 'enhancer-for-woocommerce-subscriptions' ),
				'id'       => ENR_PREFIX . 'notify_subscription_price_update_before',
				'default'  => '',
				'type'     => 'number',
				'desc_tip' => false,
				'desc'     => __( 'day(s) before the subscription due date', 'enhancer-for-woocommerce-subscriptions' ),
			),
		) ) ;

		$misc_section_start = wp_list_filter( $settings, array( 'id' => 'woocommerce_subscriptions_miscellaneous', 'type' => 'title' ) ) ;

		array_splice( $settings, key( $misc_section_start ), 0, array(
			array(
				'name' => _x( 'Cancelling', 'options section heading', 'enhancer-for-woocommerce-subscriptions' ),
				'type' => 'title',
				/* translators: 1: learn more */
				'desc' => sprintf( _x( 'Be aware that removing cancellation buttons can have legal implications. For example, %1$sCalifornia has an Automatic Renewal Law%2$s which requires stores to provide an easy-to-use mechanism for cancelling. Before removing cancellation button, we recommend you discuss potential implications with a legal professional.', 'used in the general subscription options page', 'enhancer-for-woocommerce-subscriptions' ), '<a href="' . esc_url( 'https://www.dlapiper.com/en/us/insights/publications/2014/09/california-automatic-renewal-law/' ) . '">', '</a>' ),
				'id'   => ENR_PREFIX . 'cancelling',
			),
			array(
				'name'    => __( 'Allow Cancelling', 'enhancer-for-woocommerce-subscriptions' ),
				'id'      => ENR_PREFIX . 'allow_cancelling',
				'default' => 'yes',
				'type'    => 'checkbox',
				'desc'    => __( 'Allow subscribers to cancel their subscriptions', 'enhancer-for-woocommerce-subscriptions' ),
			),
			array(
				'name'     => __( 'Allow Cancelling After', 'enhancer-for-woocommerce-subscriptions' ),
				'id'       => ENR_PREFIX . 'allow_cancelling_after',
				'default'  => '0',
				'type'     => 'number',
				'desc_tip' => __( 'Set 0 to allow subscribers to cancel immediately. If left empty, customers will not be able to cancel their subscriptions. You can also set cancel delay duration for each product separately within the product settings.', 'enhancer-for-woocommerce-subscriptions' ),
				'desc'     => __( 'day(s) from the subscription start date', 'enhancer-for-woocommerce-subscriptions' ),
			),
			array(
				'name'     => __( 'Prevent Cancelling', 'enhancer-for-woocommerce-subscriptions' ),
				'id'       => ENR_PREFIX . 'allow_cancelling_before_due',
				'default'  => '0',
				'type'     => 'number',
				'desc_tip' => __( 'If left empty or set 0, subscribers will not be prevented from cancelling their subscriptions until the renewal date. You can also set cancel prevention duration for each product separately within the product settings.', 'enhancer-for-woocommerce-subscriptions' ),
				'desc'     => __( 'day(s) before the subscription renewal date', 'enhancer-for-woocommerce-subscriptions' ),
			),
			array( 'type' => 'sectionend', 'id' => ENR_PREFIX . 'cancelling' ),
		) ) ;

		$misc_section_end = wp_list_filter( $settings, array( 'id' => 'woocommerce_subscriptions_miscellaneous', 'type' => 'sectionend' ) ) ;

		array_splice( $settings, key( $misc_section_end ), 0, array(
			array(
				'name'        => __( 'Send Auto Renewal Reminder', 'enhancer-for-woocommerce-subscriptions' ),
				'id'          => ENR_PREFIX . 'send_auto_renewal_reminder_before',
				'default'     => '',
				'placeholder' => __( 'e.g. 3,2,1', 'enhancer-for-woocommerce-subscriptions' ),
				'type'        => 'text',
				'desc'        => __( 'day(s) before subscription due date', 'enhancer-for-woocommerce-subscriptions' ),
				'desc_tip'    => __( 'Multiple auto renewal reminders can be sent to the customer. To send multiple reminders, enter the day(s) to send notification before the renewal date in descending order. Multiple values should be separated by comma(for example 3,2,1)', 'enhancer-for-woocommerce-subscriptions' ),
			),
			array(
				'name'        => __( 'Send Manual Renewal Reminder', 'enhancer-for-woocommerce-subscriptions' ),
				'id'          => ENR_PREFIX . 'send_manual_renewal_reminder_before',
				'default'     => '',
				'placeholder' => __( 'e.g. 3,2,1', 'enhancer-for-woocommerce-subscriptions' ),
				'type'        => 'text',
				'desc'        => __( 'day(s) before subscription due date', 'enhancer-for-woocommerce-subscriptions' ),
				'desc_tip'    => __( 'Multiple manual renewal reminders can be sent to the customer. To send multiple reminders, enter the day(s) to send notification before the renewal date in descending order. Multiple values should be separated by comma(for example 3,2,1)', 'enhancer-for-woocommerce-subscriptions' ),
			),
			array(
				'name'        => __( 'Send Expiry Reminder', 'enhancer-for-woocommerce-subscriptions' ),
				'id'          => ENR_PREFIX . 'send_expiry_reminder_before',
				'default'     => '',
				'placeholder' => __( 'e.g. 3,2,1', 'enhancer-for-woocommerce-subscriptions' ),
				'type'        => 'text',
				'desc'        => __( 'day(s) before subscription expiry date', 'enhancer-for-woocommerce-subscriptions' ),
				'desc_tip'    => __( 'Multiple expiry reminders can be sent to the customer. To send multiple reminders, enter the day(s) to send notification before the expiry date in descending order. Multiple values should be separated by comma(for example 3,2,1)', 'enhancer-for-woocommerce-subscriptions' ),
			),
		) ) ;

		return $settings ;
	}

	/**
	 * Save subscription meta.
	 */
	public static function save_subscription_meta( $product_id ) {
		if ( empty( $_POST[ '_wcsnonce' ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ '_wcsnonce' ] ) ), 'wcs_subscription_meta' ) ) {
			return ;
		}

		update_post_meta( $product_id, ENR_PREFIX . 'enable_seperate_shipping_cycle', isset( $_POST[ ENR_PREFIX . 'enable_seperate_shipping_cycle' ] ) ? sanitize_title( wp_unslash( $_POST[ ENR_PREFIX . 'enable_seperate_shipping_cycle' ] ) ) : ''  ) ;
		update_post_meta( $product_id, ENR_PREFIX . 'limit_trial_to_one', isset( $_POST[ ENR_PREFIX . 'limit_trial_to_one' ] ) ? sanitize_title( wp_unslash( $_POST[ ENR_PREFIX . 'limit_trial_to_one' ] ) ) : ''  ) ;

		if ( isset( $_POST[ ENR_PREFIX . 'shipping_period_interval' ] ) ) {
			update_post_meta( $product_id, ENR_PREFIX . 'shipping_period_interval', is_numeric( $_POST[ ENR_PREFIX . 'shipping_period_interval' ] ) ? absint( wp_unslash( $_POST[ ENR_PREFIX . 'shipping_period_interval' ] ) ) : ''  ) ;
		}

		if ( isset( $_POST[ ENR_PREFIX . 'shipping_period' ] ) ) {
			update_post_meta( $product_id, ENR_PREFIX . 'shipping_period', sanitize_title( wp_unslash( $_POST[ ENR_PREFIX . 'shipping_period' ] ) ) ) ;
		}

		if ( isset( $_POST[ ENR_PREFIX . 'allow_cancelling_to' ] ) ) {
			update_post_meta( $product_id, ENR_PREFIX . 'allow_cancelling_to', sanitize_title( wp_unslash( $_POST[ ENR_PREFIX . 'allow_cancelling_to' ] ) ) ) ;
		}

		if ( isset( $_POST[ ENR_PREFIX . 'allow_cancelling_after' ] ) ) {
			update_post_meta( $product_id, ENR_PREFIX . 'allow_cancelling_after', is_numeric( $_POST[ ENR_PREFIX . 'allow_cancelling_after' ] ) ? absint( wp_unslash( $_POST[ ENR_PREFIX . 'allow_cancelling_after' ] ) ) : ''  ) ;
		}

		if ( isset( $_POST[ ENR_PREFIX . 'allow_cancelling_before_due' ] ) ) {
			update_post_meta( $product_id, ENR_PREFIX . 'allow_cancelling_before_due', is_numeric( $_POST[ ENR_PREFIX . 'allow_cancelling_before_due' ] ) ? absint( wp_unslash( $_POST[ ENR_PREFIX . 'allow_cancelling_before_due' ] ) ) : ''  ) ;
		}

		if ( isset( $_POST[ ENR_PREFIX . 'variable_subscription_limit_level' ] ) ) {
			update_post_meta( $product_id, ENR_PREFIX . 'variable_subscription_limit_level', sanitize_title( wp_unslash( $_POST[ ENR_PREFIX . 'variable_subscription_limit_level' ] ) ) ) ;
		}
	}

	/**
	 * Save subscription variation meta.
	 */
	public static function save_subscription_variation_meta( $variation_id, $loop ) {
		if ( empty( $_POST[ '_wcsnonce_save_variations' ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ '_wcsnonce_save_variations' ] ) ), 'wcs_subscription_variations' ) ) {
			return ;
		}

		update_post_meta( $variation_id, ENR_PREFIX . 'enable_seperate_shipping_cycle', isset( $_POST[ ENR_PREFIX . 'enable_seperate_shipping_cycle' ][ $loop ] ) ? sanitize_title( wp_unslash( $_POST[ ENR_PREFIX . 'enable_seperate_shipping_cycle' ][ $loop ] ) ) : ''  ) ;

		if ( isset( $_POST[ ENR_PREFIX . 'shipping_period_interval' ][ $loop ] ) ) {
			update_post_meta( $variation_id, ENR_PREFIX . 'shipping_period_interval', is_numeric( $_POST[ ENR_PREFIX . 'shipping_period_interval' ][ $loop ] ) ? absint( wp_unslash( $_POST[ ENR_PREFIX . 'shipping_period_interval' ][ $loop ] ) ) : ''  ) ;
		}

		if ( isset( $_POST[ ENR_PREFIX . 'shipping_period' ][ $loop ] ) ) {
			update_post_meta( $variation_id, ENR_PREFIX . 'shipping_period', sanitize_title( wp_unslash( $_POST[ ENR_PREFIX . 'shipping_period' ][ $loop ] ) ) ) ;
		}

		if ( isset( $_POST[ ENR_PREFIX . 'allow_cancelling_to' ][ $loop ] ) ) {
			update_post_meta( $variation_id, ENR_PREFIX . 'allow_cancelling_to', sanitize_title( wp_unslash( $_POST[ ENR_PREFIX . 'allow_cancelling_to' ][ $loop ] ) ) ) ;
		}

		if ( isset( $_POST[ ENR_PREFIX . 'allow_cancelling_after' ][ $loop ] ) ) {
			update_post_meta( $variation_id, ENR_PREFIX . 'allow_cancelling_after', is_numeric( $_POST[ ENR_PREFIX . 'allow_cancelling_after' ][ $loop ] ) ? absint( wp_unslash( $_POST[ ENR_PREFIX . 'allow_cancelling_after' ][ $loop ] ) ) : ''  ) ;
		}

		if ( isset( $_POST[ ENR_PREFIX . 'allow_cancelling_before_due' ][ $loop ] ) ) {
			update_post_meta( $variation_id, ENR_PREFIX . 'allow_cancelling_before_due', is_numeric( $_POST[ ENR_PREFIX . 'allow_cancelling_before_due' ][ $loop ] ) ? absint( wp_unslash( $_POST[ ENR_PREFIX . 'allow_cancelling_before_due' ][ $loop ] ) ) : ''  ) ;
		}
	}

}

ENR_Admin::init() ;
