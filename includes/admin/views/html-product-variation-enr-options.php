<?php
/**
 * Product Variation Enhancer Options.
 */
defined( 'ABSPATH' ) || exit ;
?>

<div class="show_if_variable-subscription">
	<p class="form-row form-row-first form-field show_if_variable-subscription _enr_enable_seperate_shipping_cycle_field">
		<label for="_enr_enable_seperate_shipping_cycle[<?php echo esc_attr( $loop ) ; ?>]"><?php esc_html_e( 'Separate Shipping Cycle', 'enhancer-for-woocommerce-subscriptions' ) ; ?></label>
		<input type="checkbox" class="checkbox _enr_enable_seperate_shipping_cycle" name="<?php echo esc_attr( ENR_PREFIX . "enable_seperate_shipping_cycle[$loop]" ) ; ?>" value="yes" <?php checked( 'yes', get_post_meta( $variation->ID, ENR_PREFIX . 'enable_seperate_shipping_cycle', true ) ) ; ?>/>
		<span class="woocommerce-help-tip" data-tip="<?php esc_html_e( 'Enabling this option creates separate shipping fulfilment orders for the subscription', 'enhancer-for-woocommerce-subscriptions' ) ; ?>"></span>
	</p>
	<p class="form-row form-row-first form-field show_if_variable-subscription _enr_shipping_frequency_field">
		<label for="_enr_shipping_frequency[<?php echo esc_attr( $loop ) ; ?>]"><?php esc_html_e( 'Shipping Frequency Every', 'enhancer-for-woocommerce-subscriptions' ) ; ?>
			<span class="woocommerce-help-tip" data-tip="<?php esc_html_e( 'Choose the interval to create the shipping fulfilment order', 'enhancer-for-woocommerce-subscriptions' ) ; ?>"></span>
		</label>

		<label for="_enr_shipping_period_interval[<?php echo esc_attr( $loop ) ; ?>]" class="wcs_hidden_label"><?php esc_html_e( 'Shipping interval', 'enhancer-for-woocommerce-subscriptions' ) ; ?></label>
		<input type="number" class="wc_input_price short _enr_shipping_period_interval" name="<?php echo esc_attr( ENR_PREFIX . "shipping_period_interval[$loop]" ) ; ?>" value="<?php echo esc_attr( get_post_meta( $variation->ID, ENR_PREFIX . 'shipping_period_interval', true ) ) ; ?>" min="0"/>

		<label for="_enr_shipping_period[<?php echo esc_attr( $loop ) ; ?>]" class="wcs_hidden_label"><?php esc_html_e( 'Shipping period', 'enhancer-for-woocommerce-subscriptions' ) ; ?></label>
		<select class="_enr_shipping_period" name="_enr_shipping_period[<?php echo esc_attr( $loop ) ; ?>]">
			<?php foreach ( wcs_get_subscription_period_strings() as $key => $value ) : ?>
				<option value="<?php echo esc_attr( $key ) ; ?>" <?php selected( $key, get_post_meta( $variation->ID, ENR_PREFIX . 'shipping_period', true ) ) ; ?>><?php echo esc_html( $value ) ; ?></option>
			<?php endforeach ; ?>
		</select>
	</p>
	<?php if ( 'yes' === get_option( ENR_PREFIX . 'allow_cancelling', 'yes' ) ) { ?>
		<p class="form-row form-row-first form-field show_if_variable-subscription _enr_allow_cancelling_to_field">
			<label for="_enr_allow_cancelling_to[<?php echo esc_attr( $loop ) ; ?>]"><?php esc_html_e( 'Allow Cancelling', 'enhancer-for-woocommerce-subscriptions' ) ; ?></label>
			<select class="select short _enr_allow_cancelling_to" id="<?php echo esc_attr( ENR_PREFIX . "allow_cancelling_to{$loop}" ) ; ?>" name="<?php echo esc_attr( ENR_PREFIX . "allow_cancelling_to[$loop]" ) ; ?>">
				<option value="use-storewide" <?php selected( 'use-storewide', get_post_meta( $variation->ID, ENR_PREFIX . 'allow_cancelling_to', true ), true ) ; ?>><?php esc_html_e( 'Inherit storewide settings', 'enhancer-for-woocommerce-subscriptions' ) ; ?></option>
				<option value="override-storewide" <?php selected( 'override-storewide', get_post_meta( $variation->ID, ENR_PREFIX . 'allow_cancelling_to', true ), true ) ; ?>><?php esc_html_e( 'Override storewide settings', 'enhancer-for-woocommerce-subscriptions' ) ; ?></option>
			</select>
		</p>
		<p class="form-row form-row-first form-field show_if_variable-subscription _enr_allow_cancelling_after_field">
			<label for="_enr_allow_cancelling_after[<?php echo esc_attr( $loop ) ; ?>]"><?php esc_html_e( 'Allow Cancelling After', 'enhancer-for-woocommerce-subscriptions' ) ; ?>
				<span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Set 0 to allow subscribers to cancel immediately. If empty, customers will not be allowed to cancel.', 'enhancer-for-woocommerce-subscriptions' ) ; ?>"></span>
			</label>
			<input type="number" class="short wc_input_price" name="<?php echo esc_attr( ENR_PREFIX . "allow_cancelling_after[$loop]" ) ; ?>" value="<?php echo esc_attr( metadata_exists( 'post', $variation->ID, ENR_PREFIX . 'allow_cancelling_after' ) ? get_post_meta( $variation->ID, ENR_PREFIX . 'allow_cancelling_after', true ) : '0'  ) ; ?>">
			<span class="description"><?php esc_html_e( 'day(s) from the subscription start date.', 'enhancer-for-woocommerce-subscriptions' ) ; ?></span>
		</p>
		<p class="form-row form-row-first form-field show_if_variable-subscription _enr_allow_cancelling_before_due_field">
			<label for="_enr_allow_cancelling_before_due[<?php echo esc_attr( $loop ) ; ?>]"><?php esc_html_e( 'Prevent Cancelling', 'enhancer-for-woocommerce-subscriptions' ) ; ?>
				<span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'If left empty or set 0, subscribers will not be prevented from cancelling their subscriptions until the renewal date.', 'enhancer-for-woocommerce-subscriptions' ) ; ?>"></span>
			</label>
			<input type="number" class="short wc_input_price" name="<?php echo esc_attr( ENR_PREFIX . "allow_cancelling_before_due[$loop]" ) ; ?>" value="<?php echo esc_attr( metadata_exists( 'post', $variation->ID, ENR_PREFIX . 'allow_cancelling_before_due' ) ? get_post_meta( $variation->ID, ENR_PREFIX . 'allow_cancelling_before_due', true ) : '0'  ) ; ?>">
			<span class="description"><?php esc_html_e( 'day(s) before the subscription renewal date.', 'enhancer-for-woocommerce-subscriptions' ) ; ?></span>
		</p>
	<?php } ?>
</div>
