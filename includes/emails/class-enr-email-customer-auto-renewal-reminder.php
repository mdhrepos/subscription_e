<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

/**
 * Auto Renewal Reminder Email.
 *
 * An email will be sent to the customer before the subscription price is to be charged automatically from the customer account.
 *
 * @class ENR_Email_Customer_Auto_Renewal_Reminder
 * @extends WC_Email
 */
class ENR_Email_Customer_Auto_Renewal_Reminder extends WC_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = ENR_PREFIX . 'customer_auto_renewal_reminder' ;
		$this->customer_email = true ;
		$this->title          = __( 'Auto Renewal Reminder', 'enhancer-for-woocommerce-subscriptions' ) ;
		$this->description    = __( 'Auto renewal reminder emails are sent to the customers(subscribers) when their automatically renewing subscriptions are due for renewal.', 'enhancer-for-woocommerce-subscriptions' ) ;
		$this->heading        = __( 'Your subscription is going to auto renew', 'enhancer-for-woocommerce-subscriptions' ) ;
		$this->subject        = __( 'Your {blogname} subscription is going to auto renewed', 'enhancer-for-woocommerce-subscriptions' ) ;
		$this->template_html  = 'emails/customer-auto-renewal-reminder.php' ;
		$this->template_plain = 'emails/plain/customer-auto-renewal-reminder.php' ;
		$this->template_base  = _enr()->template_path() ;

		add_action( 'enr_wc_subscriptions_remind_before_auto_renewal_notification', array( $this, 'trigger' ) ) ;

		// Call parent constructor
		parent::__construct() ;
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'Thanks for shopping with us.', 'enhancer-for-woocommerce-subscriptions' ) ;
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param WC_Subscription|false $subscription Subscription object.
	 */
	public function trigger( $subscription ) {
		if ( ! is_a( $subscription, 'WC_Subscription' ) ) {
			$subscription = wcs_get_subscription( $subscription ) ;
		}

		if ( is_a( $subscription, 'WC_Subscription' ) ) {
			$this->object    = $subscription ;
			$this->recipient = $this->object->get_billing_email() ;
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return ;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() ) ;
	}

	/**
	 * Get content html.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'subscription'       => $this->object,
			'email_heading'      => $this->get_heading(),
			'additional_content' => is_callable( array( $this, 'get_additional_content' ) ) ? $this->get_additional_content() : '',
			'sent_to_admin'      => false,
			'plain_text'         => false,
			'email'              => $this,
				), '', $this->template_base ) ;
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'subscription'       => $this->object,
			'email_heading'      => $this->get_heading(),
			'additional_content' => is_callable( array( $this, 'get_additional_content' ) ) ? $this->get_additional_content() : '',
			'sent_to_admin'      => false,
			'plain_text'         => true,
			'email'              => $this,
				), '', $this->template_base ) ;
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		/* translators: %s: list of placeholders */
		$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'enhancer-for-woocommerce-subscriptions' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' ) ;
		$this->form_fields = array(
			'enabled'            => array(
				'title'   => __( 'Enable/Disable', 'enhancer-for-woocommerce-subscriptions' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'enhancer-for-woocommerce-subscriptions' ),
				'default' => 'yes',
			),
			'subject'            => array(
				'title'       => __( 'Subject', 'enhancer-for-woocommerce-subscriptions' ),
				'type'        => 'text',
				/* translators: %s: email subject */
				'description' => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: %s.', 'enhancer-for-woocommerce-subscriptions' ), '<code>' . $this->subject . '</code>' ),
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading'            => array(
				'title'       => __( 'Email Heading', 'enhancer-for-woocommerce-subscriptions' ),
				'type'        => 'text',
				/* translators: %s: email heading */
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'enhancer-for-woocommerce-subscriptions' ), $this->heading ),
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'additional_content' => array(
				'title'       => __( 'Additional content', 'enhancer-for-woocommerce-subscriptions' ),
				'description' => __( 'Text to appear below the main email content.', 'enhancer-for-woocommerce-subscriptions' ) . ' ' . $placeholder_text,
				'css'         => 'width:400px; height: 75px;',
				'placeholder' => __( 'N/A', 'enhancer-for-woocommerce-subscriptions' ),
				'type'        => 'textarea',
				'default'     => $this->get_default_additional_content(),
				'desc_tip'    => true,
			),
			'email_type'         => array(
				'title'       => __( 'Email type', 'enhancer-for-woocommerce-subscriptions' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'enhancer-for-woocommerce-subscriptions' ),
				'default'     => 'html',
				'class'       => 'email_type',
				'options'     => array(
					'plain'     => _x( 'Plain text', 'email type', 'enhancer-for-woocommerce-subscriptions' ),
					'html'      => _x( 'HTML', 'email type', 'enhancer-for-woocommerce-subscriptions' ),
					'multipart' => _x( 'Multipart', 'email type', 'enhancer-for-woocommerce-subscriptions' ),
				),
			) ) ;
	}

}

return new ENR_Email_Customer_Auto_Renewal_Reminder() ;
