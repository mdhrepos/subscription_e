<?php
defined( 'ABSPATH' ) || exit ;

/**
 * Emails class.
 * 
 * @class ENR_Emails
 * @package Class
 */
class ENR_Emails {

	/**
	 * Email notification classes
	 *
	 * @var WC_Email[]
	 */
	protected $emails = array() ;

	/**
	 * Available email notification classes to load
	 * 
	 * @var WC_Email::id => WC_Email class
	 */
	protected $email_classes = array(
		'_enr_customer_processing_shipping_fulfilment_order' => 'ENR_Email_Customer_Processing_Shipping_Fulfilment_Order',
		'_enr_customer_subscription_price_updated'           => 'ENR_Email_Customer_Subscription_Price_Updated',
		'_enr_customer_auto_renewal_reminder'                => 'ENR_Email_Customer_Auto_Renewal_Reminder',
		'_enr_customer_manual_renewal_reminder'              => 'ENR_Email_Customer_Manual_Renewal_Reminder',
		'_enr_customer_expiry_reminder'                      => 'ENR_Email_Customer_Expiry_Reminder',
			) ;

	/**
	 * The single instance of the class
	 *
	 * @var ENR_Emails
	 */
	protected static $_instance = null ;

	/**
	 * Main ENR_Emails Instance.
	 * Ensures only one instance of ENR_Emails is loaded or can be loaded.
	 * 
	 * @return ENR_Emails Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self() ;
		}
		return self::$_instance ;
	}

	/**
	 * Init the email class hooks in all emails that can be sent.
	 */
	public function init() {
		add_filter( 'woocommerce_email_classes', array( $this, 'add_email_classes' ) ) ;
		add_filter( 'woocommerce_email_setting_columns', array( $this, 'add_email_preview_column' ) ) ;
		add_filter( 'woocommerce_email_setting_column_enr_preview', array( $this, 'add_email_preview_row' ) ) ;
		add_action( 'admin_footer', array( $this, 'email_inputs_preview_template' ) ) ;
		add_action( 'admin_footer', array( $this, 'email_preview_template' ) ) ;

		self::init_notifications() ;
	}

	/**
	 * Get the template name from email ID
	 */
	public function get_template_name( $id ) {
		return str_replace( '_', '-', $id ) ;
	}

	/**
	 * Are emails available ?
	 *
	 * @return WC_Email class
	 */
	public function available() {
		WC()->mailer() ;

		return ! empty( $this->emails ) ? true : false ;
	}

	/**
	 * Return the email class
	 *
	 * @param string $id
	 * @return null|WC_Email class name
	 */
	public function get_email_class( $id ) {
		$id = strtolower( $id ) ;

		if ( false !== stripos( $id, ENR_PREFIX ) ) {
			$id = ltrim( $id, ENR_PREFIX ) ;
		}

		return isset( $this->email_classes[ $id ] ) ? $this->email_classes[ $id ] : null ;
	}

	/**
	 * Return the emails
	 *
	 * @return WC_Email[]
	 */
	public function get_emails() {
		WC()->mailer() ;

		return $this->emails ;
	}

	/**
	 * Return the email
	 *
	 * @param string $id
	 * @return WC_Email
	 */
	public function get_email( $id ) {
		WC()->mailer() ;

		$class = $this->get_email_class( $id ) ;

		return isset( $this->emails[ $class ] ) ? $this->emails[ $class ] : null ;
	}

	/**
	 * Hook in all our emails to notify.
	 */
	public static function init_notifications() {
		$email_actions = apply_filters( 'enr_email_actions', array(
			'enr_wc_subscriptions_shipping_fulfilment_order_created',
			'enr_wc_subscriptions_remind_subscription_price_changed_before_renewal',
			'enr_wc_subscriptions_remind_before_auto_renewal',
			'enr_wc_subscriptions_remind_before_manual_renewal',
			'enr_wc_subscriptions_remind_before_expiry',
				) ) ;

		foreach ( $email_actions as $action ) {
			add_action( $action, array( __CLASS__, 'send_notification' ), 10, 10 ) ;
		}
	}

	/**
	 * Init the WC mailer instance and call the notifications for the current filter.
	 *
	 * @param array $args Email args (default: []).
	 */
	public static function send_notification( $args = array() ) {
		try {
			WC()->mailer() ;
			$args = func_get_args() ;
			do_action_ref_array( current_filter() . '_notification', $args ) ;
		} catch ( Exception $e ) {
			return ;
		}
	}

	/**
	 * Load our email classes.
	 * 
	 * @param array $emails
	 */
	public function add_email_classes( $emails ) {
		if ( ! empty( $this->emails ) ) {
			return $emails + $this->emails ;
		}

		// Include email classes.
		foreach ( $this->email_classes as $id => $class ) {
			$file_name = 'class-' . strtolower( str_replace( '_', '-', $class ) ) ;
			$path      = ENR_DIR . "includes/emails/{$file_name}.php" ;

			if ( is_readable( $path ) ) {
				$this->emails[ $class ] = include( $path ) ;
			}
		}

		return $emails + $this->emails ;
	}

	/**
	 * Add column for preview.
	 * 
	 * @param array $columns
	 * @return array
	 */
	public function add_email_preview_column( $columns ) {
		$position = 4 ;
		$columns  = array_slice( $columns, 0, $position ) + array( 'enr_preview' => __( 'Preview Subscription Emails', 'enhancer-for-woocommerce-subscriptions' ) ) + array_slice( $columns, $position, count( $columns ) - 1 ) ;

		return $columns ;
	}

	/**
	 * Add row for preview.
	 * 
	 * @param WC_Email $email
	 */
	public function add_email_preview_row( $email ) {
		$our_emails  = array_keys( $this->email_classes ) ;
		$core_emails = array(
			'customer_completed_renewal_order',
			'customer_processing_renewal_order',
			'customer_on_hold_renewal_order',
			'customer_completed_switch_order',
			'customer_renewal_invoice',
			'customer_payment_retry',
			'new_renewal_order',
			'new_switch_order',
			'payment_retry',
			'suspended_subscription',
			'cancelled_subscription',
			'expired_subscription'
				) ;

		$preview_emails = ( array ) apply_filters( 'enr_wc_subscriptions_preview_emails', array_merge( $core_emails, $our_emails ) ) ;

		if ( in_array( $email->id, $preview_emails ) ) {
			echo '<td class="wc-email-settings-table-enr_preview">
		<a class="button enr-email-preview" href="#" data-email-id="' . esc_attr( $email->id ) . '" title="' . esc_attr__( 'Preview', 'enhancer-for-woocommerce-subscriptions' ) . '"><span class="dashicons dashicons-visibility"></span></a>
            </td>' ;
		} else {
			echo '<td/>' ;
		}
	}

	/**
	 * Template for email inputs preview.
	 */
	public function email_inputs_preview_template() {
		?>
		<script type="text/template" id="tmpl-enr-modal-preview-email-inputs">
		<?php include 'admin/views/html-preview-email-inputs.php' ; ?>
		</script>
		<?php
	}

	/**
	 * Template for email preview.
	 */
	public function email_preview_template() {
		?>
		<script type="text/template" id="tmpl-enr-modal-preview-email">
			<?php include 'admin/views/html-preview-email.php' ; ?>
		</script>
		<?php
	}

}
