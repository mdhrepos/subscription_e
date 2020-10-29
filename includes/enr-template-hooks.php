<?php

/**
 * Our Template Hooks
 *
 * Action/filter hooks used for Our functions/templates.
 */
defined( 'ABSPATH' ) || exit ;

/**
 * My Account.
 */
add_filter( 'wcs_view_subscription_actions', '_enr_account_cancel_options_to_subscriber', 99, 2 ) ;
add_filter( 'wcs_subscription_details_table_before_dates', '_enr_account_shipping_details' ) ;
