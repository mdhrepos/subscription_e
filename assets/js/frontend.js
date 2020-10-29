/* global enr_frontend_params */

jQuery( function( $ ) {
	'use strict' ;

	// Hide variable level limit notice when it is limited to variation level
	if ( 'yes' === enr_frontend_params.hide_variable_limited_notice && $( 'form.variations_form' ).length && $( 'form.variations_form' ).find( '.limited-subscription-notice' ).length ) {
		$( 'form.variations_form' ).find( '.limited-subscription-notice' ).hide() ;
	}

	/**
	 * Handle subscribe events.
	 */
	var wcs_variations_form = {
		variationForm : $( '.variations_form' ),
		cartForm : $( 'form.cart' ),
		init : function() {
			$( document ).on( 'found_variation.wc-variation-form', this.onFoundVariation ) ;
			$( document ).on( 'reset_data', this.onResetVariation ) ;
		},
		onFoundVariation : function( evt, variation ) {
			wcs_variations_form.onResetVariation() ;

			if ( variation.enr_limited_subscription_notice ) {
				wcs_variations_form.variationForm.find( '.woocommerce-variation-add-to-cart' ).after( variation.enr_limited_subscription_notice ) ;
			}

			if ( variation.enr_resubscribe_link ) {
				wcs_variations_form.variationForm.find( '.woocommerce-variation-add-to-cart' ).after( variation.enr_resubscribe_link ) ;
			}
		},
		onResetVariation : function( evt, variation ) {
			if ( wcs_variations_form.variationForm.find( '.enr-variation-wrapper' ).length ) {
				wcs_variations_form.variationForm.find( '.enr-variation-wrapper' ).remove() ;
			}
		},
	} ;

	wcs_variations_form.init() ;
} ) ;
