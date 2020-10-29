<?php
defined( 'ABSPATH' ) || exit ;

/**
 * Privacy/GDPR related functionality which ties into WordPress functionality.
 * 
 * @class ENR_Privacy
 * @package Class
 */
class ENR_Privacy {

	/**
	 * Init ENR_Privacy.
	 */
	public static function init() {
		add_action( 'admin_init', __CLASS__ . '::add_privacy_message' ) ;
	}

	/**
	 * Get plugin name
	 * 
	 * @return string
	 */
	public static function get_plugin_name() {
		$plugin = get_plugin_data( ENR_FILE ) ;
		return $plugin[ 'Name' ] ;
	}

	/**
	 * Adds the privacy message on ENR privacy page.
	 */
	public static function add_privacy_message() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			$content = self::get_privacy_message() ;

			if ( $content ) {
				wp_add_privacy_policy_content( self::get_plugin_name(), $content ) ;
			}
		}
	}

	/**
	 * Add privacy policy content for the privacy policy page.
	 */
	public static function get_privacy_message() {
		ob_start() ;
		?>
		<p><?php esc_html_e( 'This includes the basics of what personal data your store may be collecting, storing and sharing. Depending on what settings are enabled and which additional plugins are used, the specific information shared by your store will vary.', 'enhancer-for-woocommerce-subscriptions' ) ; ?></p>
		<h2><?php esc_html_e( 'What the Plugin does', 'enhancer-for-woocommerce-subscriptions' ) ; ?></h2>
		<p><?php esc_html_e( 'Using this plugin, your can use additional features for your WooCommerce Subscriptions plugin using which you can use new features which are not available in the existing WooCommerce Subscriptions plugin.', 'enhancer-for-woocommerce-subscriptions' ) ; ?></p>
		<h2><?php esc_html_e( 'What we collect and share', 'enhancer-for-woocommerce-subscriptions' ) ; ?></h2>        
		<p><?php esc_html_e( 'This plugin does not collect or store any personal information about the users.', 'enhancer-for-woocommerce-subscriptions' ) ; ?></p>
		<?php
		return apply_filters( 'enr_privacy_policy_content', ob_get_clean() ) ;
	}

}

ENR_Privacy::init() ;
