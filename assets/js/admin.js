/* global enr_admin_params, ajaxurl */

jQuery( function( $ ) {
	'use strict' ;

	var is_blocked = function( $node ) {
		return $node.is( '.processing' ) || $node.parents( '.processing' ).length ;
	} ;

	/**
	 * Block a node visually for processing.
	 *
	 * @param {JQuery Object} $node
	 */
	var block = function( $node ) {
		$.blockUI.defaults.overlayCSS.cursor = 'wait' ;

		if ( ! is_blocked( $node ) ) {
			$node.addClass( 'processing' ).block( {
				message : null,
				overlayCSS : {
					background : '#fff',
					opacity : 0.6
				}
			} ) ;
		}
	} ;

	/**
	 * Unblock a node after processing is complete.
	 *
	 * @param {JQuery Object} $node
	 */
	var unblock = function( $node ) {
		$node.removeClass( 'processing' ).unblock() ;
	} ;

	// Storewide options
	$( '#_enr_allow_cancelling' ).change( function() {
		$( '#_enr_allow_cancelling_after,#_enr_allow_cancelling_before_due' ).closest( 'tr' ).hide() ;

		if ( this.checked ) {
			$( '#_enr_allow_cancelling_after,#_enr_allow_cancelling_before_due' ).closest( 'tr' ).show() ;
		}
	} ).change() ;

	$( '#_enr_apply_old_subscription_price_as' ).change( function() {
		$( '#_enr_notify_subscription_price_update_before' ).closest( 'tr' ).hide() ;

		if ( 'new-price' === this.value ) {
			$( '#_enr_notify_subscription_price_update_before' ).closest( 'tr' ).show() ;
		}
	} ).change() ;

	function getShippingPeriodOptions( selector, options ) {
		var selected = selector.val() ;

		selector.empty() ;
		$.each( options, function( key, value ) {
			if ( value === selected ) {
				selector.append( $( '<option></option>' )
						.attr( 'value', value ).attr( 'selected', 'selected' )
						.text( enr_admin_params.period[value] ).val( value ) ) ;
			} else {
				selector.append( $( '<option></option>' )
						.attr( 'value', value )
						.text( enr_admin_params.period[value] ) ) ;
			}
		} ) ;
	}

	// Productwide options
	var wc_metaboxes_product_data = {
		wrapper : $( '#woocommerce-product-data' ),
		variationsWrapper : $( '#variable_product_options' ),
		init : function() {
			this.wrapper.on( 'change', '#_enr_allow_cancelling_to', this.allowCancelling ) ;
			this.wrapper.on( 'change', '#_enr_enable_seperate_shipping_cycle', this.enableSeperateShippingCycle ) ;
			this.wrapper.on( 'change', '#_subscription_limit', this.applySubscriptionLimit ) ;
			this.wrapper.on( 'change', '#_enr_shipping_period', this.setMaxShippingPeriodInterval ) ;
			this.wrapper.on( 'change', '#_subscription_period_interval,#_subscription_period', this.setShippingPeriodOptions ) ;
			this.wrapper.on( 'woocommerce_variations_added woocommerce_variations_loaded', this.variationLoaded ) ;

			this.variationsWrapper.on( 'change', '._enr_allow_cancelling_to', this.allowCancellingForVariation ) ;
			this.variationsWrapper.on( 'change', '._enr_enable_seperate_shipping_cycle', this.enableSeperateShippingCycleForVariation ) ;
			this.variationsWrapper.on( 'change', '._enr_shipping_period', this.setMaxShippingPeriodIntervalForVariation ) ;
			this.variationsWrapper.on( 'change', '.wc_input_subscription_period_interval,.wc_input_subscription_period', this.setShippingPeriodOptionsForVariation ) ;

			$( '#_enr_allow_cancelling_to,#_enr_enable_seperate_shipping_cycle,#_subscription_limit' ).change() ;

			this._setShippingPeriodOptions( this.wrapper ) ;
		},
		variationLoaded : function() {
			$( '._enr_allow_cancelling_to,._enr_enable_seperate_shipping_cycle' ).change() ;

			$( '.wc_input_subscription_period_interval, .wc_input_subscription_period' ).each( function() {
				wc_metaboxes_product_data._setShippingPeriodOptions( $( this ).closest( '.woocommerce_variation' ), true ) ;
			} ) ;
		},
		allowCancelling : function( e ) {
			$( e.currentTarget ).closest( '#general_product_data' ).find( '._enr_allow_cancelling_after_field,._enr_allow_cancelling_before_due_field' ).hide() ;

			if ( 'override-storewide' === $( e.currentTarget ).val() ) {
				$( e.currentTarget ).closest( '#general_product_data' ).find( '._enr_allow_cancelling_after_field,._enr_allow_cancelling_before_due_field' ).show() ;
			}
		},
		allowCancellingForVariation : function( e ) {
			$( e.currentTarget ).closest( '.woocommerce_variation' ).find( '._enr_allow_cancelling_after_field,._enr_allow_cancelling_before_due_field' ).hide() ;

			if ( 'override-storewide' === $( e.currentTarget ).val() ) {
				$( e.currentTarget ).closest( '.woocommerce_variation' ).find( '._enr_allow_cancelling_after_field,._enr_allow_cancelling_before_due_field' ).show() ;
			}
		},
		enableSeperateShippingCycle : function( e ) {
			$( e.currentTarget ).closest( '#general_product_data' ).find( '._enr_shipping_frequency_field' ).hide() ;

			if ( $( e.currentTarget ).is( ':checked' ) ) {
				$( e.currentTarget ).closest( '#general_product_data' ).find( '._enr_shipping_frequency_field' ).show() ;
			}
		},
		enableSeperateShippingCycleForVariation : function( e ) {
			$( e.currentTarget ).closest( '.woocommerce_variation' ).find( '._enr_shipping_frequency_field' ).hide() ;

			if ( $( e.currentTarget ).is( ':checked' ) ) {
				$( e.currentTarget ).closest( '.woocommerce_variation' ).find( '._enr_shipping_frequency_field' ).show() ;
			}
		},
		applySubscriptionLimit : function( e ) {
			if ( 'no' === $( e.currentTarget ).val() ) {
				$( e.currentTarget )
						.closest( '#advanced_product_data' ).find( '._enr_limit_trial_to_one_field' ).show()
						.closest( '#advanced_product_data' ).find( '._enr_variable_subscription_limit_level_field' ).hide() ;
			} else {
				$( e.currentTarget )
						.closest( '#advanced_product_data' ).find( '._enr_limit_trial_to_one_field' ).hide()
						.closest( '#advanced_product_data' ).find( '._enr_variable_subscription_limit_level_field' ).show() ;
			}
		},
		setMaxShippingPeriodInterval : function() {
			wc_metaboxes_product_data._setMaxShippingPeriodInterval( wc_metaboxes_product_data.wrapper ) ;
		},
		setMaxShippingPeriodIntervalForVariation : function( e ) {
			wc_metaboxes_product_data._setMaxShippingPeriodInterval( $( e.currentTarget ).closest( '.woocommerce_variation' ), true ) ;
		},
		setShippingPeriodOptions : function() {
			wc_metaboxes_product_data._setShippingPeriodOptions( wc_metaboxes_product_data.wrapper ) ;
		},
		setShippingPeriodOptionsForVariation : function( e ) {
			wc_metaboxes_product_data._setShippingPeriodOptions( $( e.currentTarget ).closest( '.woocommerce_variation' ), true ) ;
		},
		_setShippingPeriodOptions : function( wrapper, isVariation ) {
			var period, fPeriod, periodInterval ;
			isVariation = isVariation || false ;

			if ( isVariation ) {
				period = wrapper.find( '.wc_input_subscription_period' ) ;
				fPeriod = wrapper.find( '._enr_shipping_period' ) ;
				periodInterval = wrapper.find( '.wc_input_subscription_period_interval' ) ;
			} else {
				period = wrapper.find( '#_subscription_period' ) ;
				fPeriod = wrapper.find( '#_enr_shipping_period' ) ;
				periodInterval = wrapper.find( '#_subscription_period_interval' ) ;
			}

			wrapper.find( '._enr_enable_seperate_shipping_cycle_field' ).show() ;

			if ( isVariation ) {
				wrapper.find( '._enr_enable_seperate_shipping_cycle' ).change() ;
			} else {
				wrapper.find( '#_enr_enable_seperate_shipping_cycle' ).change() ;
			}

			switch ( periodInterval.val() ) {
				case '1':
					switch ( period.val() ) {
						case 'day':
							wrapper.find( '._enr_enable_seperate_shipping_cycle_field,._enr_shipping_frequency_field' ).hide() ;
							break ;
						case 'week':
							getShippingPeriodOptions( fPeriod, [ "day" ] ) ;
							break ;
						case 'month':
							getShippingPeriodOptions( fPeriod, [ "day", "week" ] ) ;
							break ;
						case 'year':
							getShippingPeriodOptions( fPeriod, [ "day", "week", "month" ] ) ;
							break ;
					}
					break ;
				case '2':
				case '3':
				case '4':
				case '5':
				case '6':
					switch ( period.val() ) {
						case 'day':
							getShippingPeriodOptions( fPeriod, [ "day" ] ) ;
							break ;
						case 'week':
							getShippingPeriodOptions( fPeriod, [ "day", "week" ] ) ;
							break ;
						case 'month':
							getShippingPeriodOptions( fPeriod, [ "day", "week", "month" ] ) ;
							break ;
						case 'year':
							getShippingPeriodOptions( fPeriod, [ "day", "week", "month", "year" ] ) ;
							break ;
					}
					break ;
			}

			fPeriod.change() ;
		},
		_setMaxShippingPeriodInterval : function( wrapper, isVariation ) {
			var period, fPeriod, periodInterval, fPeriodInterval ;
			isVariation = isVariation || false ;

			if ( isVariation ) {
				period = wrapper.find( '.wc_input_subscription_period' ) ;
				fPeriod = wrapper.find( '._enr_shipping_period' ) ;
				periodInterval = wrapper.find( '.wc_input_subscription_period_interval' ) ;
				fPeriodInterval = wrapper.find( '._enr_shipping_period_interval' ) ;
			} else {
				period = wrapper.find( '#_subscription_period' ) ;
				fPeriod = wrapper.find( '#_enr_shipping_period' ) ;
				periodInterval = wrapper.find( '#_subscription_period_interval' ) ;
				fPeriodInterval = wrapper.find( '#_enr_shipping_period_interval' ) ;
			}

			switch ( fPeriod.val() ) {
				case 'day':
					switch ( periodInterval.val() ) {
						case '1':
							switch ( period.val() ) {
								case 'day':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'week':
									fPeriodInterval.attr( 'max', '6' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '27' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '364' ) ;
									break ;
							}
							break ;
						case '2':
							switch ( period.val() ) {
								case 'day':
									fPeriodInterval.attr( 'max', '1' ) ;
									break ;
								case 'week':
									fPeriodInterval.attr( 'max', '13' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '55' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '729' ) ;
									break ;
							}
							break ;
						case '3':
							switch ( period.val() ) {
								case 'day':
									fPeriodInterval.attr( 'max', '2' ) ;
									break ;
								case 'week':
									fPeriodInterval.attr( 'max', '20' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '83' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '1,094' ) ;
									break ;
							}
							break ;
						case '4':
							switch ( period.val() ) {
								case 'day':
									fPeriodInterval.attr( 'max', '3' ) ;
									break ;
								case 'week':
									fPeriodInterval.attr( 'max', '27' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '111' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '1,459' ) ;
									break ;
							}
							break ;
						case '5':
							switch ( period.val() ) {
								case 'day':
									fPeriodInterval.attr( 'max', '4' ) ;
									break ;
								case 'week':
									fPeriodInterval.attr( 'max', '34' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '139' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '1,824' ) ;
									break ;
							}
							break ;
						case '6':
							switch ( period.val() ) {
								case 'day':
									fPeriodInterval.attr( 'max', '5' ) ;
									break ;
								case 'week':
									fPeriodInterval.attr( 'max', '41' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '167' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '2,189' ) ;
									break ;
							}
							break ;
					}
					break ;
				case 'week':
					switch ( periodInterval.val() ) {
						case '1':
							switch ( period.val() ) {
								case 'day':
								case 'week':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '3' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '51' ) ;
									break ;
							}
							break ;
						case '2':
							switch ( period.val() ) {
								case 'day':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'week':
									fPeriodInterval.attr( 'max', '1' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '7' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '103' ) ;
									break ;
							}
							break ;
						case '3':
							switch ( period.val() ) {
								case 'day':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'week':
									fPeriodInterval.attr( 'max', '2' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '11' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '155' ) ;
									break ;
							}
							break ;
						case '4':
							switch ( period.val() ) {
								case 'day':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'week':
									fPeriodInterval.attr( 'max', '3' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '15' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '207' ) ;
									break ;
							}
							break ;
						case '5':
							switch ( period.val() ) {
								case 'day':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'week':
									fPeriodInterval.attr( 'max', '4' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '19' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '259' ) ;
									break ;
							}
							break ;
						case '6':
							switch ( period.val() ) {
								case 'day':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'week':
									fPeriodInterval.attr( 'max', '5' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '23' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '311' ) ;
									break ;
							}
							break ;
					}
					break ;
				case 'month':
					switch ( periodInterval.val() ) {
						case '1':
							switch ( period.val() ) {
								case 'day':
								case 'week':
								case 'month':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '11' ) ;
									break ;
							}
							break ;
						case '2':
							switch ( period.val() ) {
								case 'day':
								case 'week':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '1' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '23' ) ;
									break ;
							}
							break ;
						case '3':
							switch ( period.val() ) {
								case 'day':
								case 'week':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '2' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '35' ) ;
									break ;
							}
							break ;
						case '4':
							switch ( period.val() ) {
								case 'day':
								case 'week':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '3' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '47' ) ;
									break ;
							}
							break ;
						case '5':
							switch ( period.val() ) {
								case 'day':
								case 'week':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '4' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '59' ) ;
									break ;
							}
							break ;
						case '6':
							switch ( period.val() ) {
								case 'day':
								case 'week':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'month':
									fPeriodInterval.attr( 'max', '5' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '71' ) ;
									break ;
							}
							break ;
					}
					break ;
				case 'year':
					switch ( periodInterval.val() ) {
						case '1':
							switch ( period.val() ) {
								case 'day':
								case 'week':
								case 'month':
								case 'year':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
							}
							break ;
						case '2':
							switch ( period.val() ) {
								case 'day':
								case 'week':
								case 'month':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '1' ) ;
									break ;
							}
							break ;
						case '3':
							switch ( period.val() ) {
								case 'day':
								case 'week':
								case 'month':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '2' ) ;
									break ;
							}
							break ;
						case '4':
							switch ( period.val() ) {
								case 'day':
								case 'week':
								case 'month':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '3' ) ;
									break ;
							}
							break ;
						case '5':
							switch ( period.val() ) {
								case 'day':
								case 'week':
								case 'month':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '4' ) ;
									break ;
							}
							break ;
						case '6':
							switch ( period.val() ) {
								case 'day':
								case 'week':
								case 'month':
									fPeriodInterval.attr( 'max', '0' ) ;
									break ;
								case 'year':
									fPeriodInterval.attr( 'max', '5' ) ;
									break ;
							}
							break ;
					}
					break ;
			}
		}
	} ;

	wc_metaboxes_product_data.init() ;

	// Remove the regular order relationship showing via WCS
	if ( $( '.wp-list-table .type-shop_order ._enr_shipping_fulfilment_order_relationship' ).length ) {
		$( '.wp-list-table .type-shop_order' ).find( '._enr_shipping_fulfilment_order_relationship' ).closest( 'td' ).find( 'span.normal_order' ).remove() ;
	}

	// Our emails and Core emails preview
	var emails_preview = {
		init : function() {
			$( document ).on( 'click', '.enr-email-preview', this.collectInputs ) ;
			$( document.body )
					.on( 'wc_backbone_modal_loaded', function( e, target ) {
						if ( 'enr-modal-preview-email-inputs' === target ) {

						}
					} )
					.on( 'wc_backbone_modal_response', function( e, target, data ) {
						if ( 'enr-modal-preview-email-inputs' === target ) {
							emails_preview.preview() ;
						}
					} ) ;
		},
		collectInputs : function( e ) {
			e.preventDefault() ;

			var $previewButton = $( this ) ;
			block( $previewButton ) ;

			$.ajax( {
				type : 'GET',
				url : ajaxurl,
				dataType : 'json',
				data : {
					action : '_enr_collect_preview_email_inputs',
					security : enr_admin_params.preview_email_inputs_nonce,
					email_id : $previewButton.data( 'email-id' )
				},
				success : function( response ) {
					if ( response && response.success ) {
						$( this ).WCBackboneModal( {
							template : 'enr-modal-preview-email-inputs',
							variable : response.data
						} ) ;
					}
				},
				complete : function() {
					unblock( $previewButton ) ;
				}
			} ) ;
			return false ;
		},
		preview : function() {
			$.ajax( {
				type : 'GET',
				url : ajaxurl,
				dataType : 'json',
				data : {
					action : '_enr_preview_email',
					security : enr_admin_params.preview_email_nonce,
					data : $( '.enr_email_inputs_wrapper :input[name]' ).serialize()
				},
				success : function( response ) {
					if ( response.success ) {
						$( this ).WCBackboneModal( {
							template : 'enr-modal-preview-email',
							variable : response.data
						} ) ;
					} else {
						window.alert( response.data.error )
					}
				}
			} ) ;
			return false ;
		},
	} ;

	emails_preview.init() ;
} ) ;
