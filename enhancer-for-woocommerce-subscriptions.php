<?php

/**
 * Plugin Name: Enhancer for WooCommerce Subscriptions
 * Description: Additional features for WooCommerce Subscriptions such as price updation for existing users, separate shipping cycle, cancel delay, auto-renewal reminder, etc.
 * Version: 1.6
 * Author: FantasticPlugins
 * Author URI: http://fantasticplugins.com
 * Text Domain: enhancer-for-woocommerce-subscriptions
 * Domain Path: /languages
 * Woo: 5834751:b0f115cc74f785a3e38e8aa056cebc4f
 * Tested up to: 5.5
 * WC tested up to: 4.5.1
 * WC requires at least: 3.5
 * WCS tested up to: 3.0.7
 * WCS requires at least: 3.0.1
 * Copyright: © 2020 FantasticPlugins
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
defined( 'ABSPATH' ) || exit ;


/**
 * Define our plugin constants.
 */
define( 'ENR_FILE', __FILE__ ) ;
define( 'ENR_DIR', plugin_dir_path( ENR_FILE ) ) ;
define( 'ENR_URL', untrailingslashit( plugins_url( '/', ENR_FILE ) ) ) ;
define( 'ENR_PREFIX', '_enr_' ) ;

/**
 * Initiate Plugin Core class.
 * 
 * @class ENR_For_WC_Subscriptions
 * @package Class
 */
final class ENR_For_WC_Subscriptions {

	/**
	 * Plugin version.
	 */
	const VERSION = '1.6' ;

	/**
	 * Required WC version.
	 */
	const REQ_WC_VERSION = '3.5' ;

	/**
	 * Required WC Subscriptions version.
	 */
	const REQ_WCS_VERSION = '3.0.1' ;

	/**
	 * The single instance of the class.
	 */
	protected static $instance = null ;

	/**
	 * ENR_For_WC_Subscriptions constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) ) ;
		add_action( 'admin_notices', array( $this, 'plugin_dependencies_notice' ) ) ;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is forbidden.', 'enhancer-for-woocommerce-subscriptions' ), '1.0' ) ;
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'enhancer-for-woocommerce-subscriptions' ), '1.0' ) ;
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'mailer' ), true ) ) {
			return $this->$key() ;
		}
	}

	/**
	 * Main ENR_For_WC_Subscriptions Instance.
	 * Ensures only one instance of ENR_For_WC_Subscriptions is loaded or can be loaded.
	 * 
	 * @return ENR_For_WC_Subscriptions - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self() ;
		}
		return self::$instance ;
	}

	/**
	 * Get plugin version.
	 * 
	 * @return string
	 */
	public function get_version() {
		return self::VERSION ;
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		return ENR_DIR . 'templates/' ;
	}

	/**
	 * Check whether the plugin dependencies met.
	 * 
	 * @return bool|string True on Success
	 */
	private function plugin_dependencies_met( $return_dep_notice = false ) {
		$return = false ;

		// WC Subscriptions check.
		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			if ( $return_dep_notice ) {
				// translators: 1$-2$: opening and closing <strong> tags, 3$-4$: link tags, takes to woocommerce subscriptions plugin on woocommerce.com
				$return = sprintf( esc_html__( '%1$sEnhancer for WooCommerce Subscriptions is inactive.%2$s The %3$sWooCommerce Subscriptions plugin%4$s must be active for Enhancer for WooCommerce Subscriptions to work. Please install & activate WooCommerce Subscriptions.', 'enhancer-for-woocommerce-subscriptions' ), '<strong>', '</strong>', '<a href="http://woocommerce.com/products/woocommerce-subscriptions/">', '</a>' ) ;
			}

			return $return ;
		}

		// WC check.
		if ( ! function_exists( 'WC' ) ) {
			if ( $return_dep_notice ) {
				$install_url = wp_nonce_url( add_query_arg( array( 'action' => 'install-plugin', 'plugin' => 'woocommerce' ), admin_url( 'update.php' ) ), 'install-plugin_woocommerce' ) ;
				// translators: 1$-2$: opening and closing <strong> tags, 3$-4$: link tags, takes to woocommerce plugin on wp.org, 5$-6$: opening and closing link tags, leads to plugins.php in admin
				$return      = sprintf( esc_html__( '%1$sEnhancer for WooCommerce Subscriptions is inactive.%2$s The %3$sWooCommerce plugin%4$s must be active for Enhancer for WooCommerce Subscriptions to work. Please %5$sinstall & activate WooCommerce &raquo;%6$s', 'enhancer-for-woocommerce-subscriptions' ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . esc_url( $install_url ) . '">', '</a>' ) ;
			}

			return $return ;
		}

		return true ;
	}

	/**
	 * When WP has loaded all plugins, check whether the plugin is compatible with the present environment and load our files.
	 */
	public function plugins_loaded() {
		if ( true !== $this->plugin_dependencies_met() ) {
			return ;
		}

		$this->include_files() ;
		$this->init_hooks() ;
		$this->load_plugin_textdomain() ;

		do_action( 'enr_loaded' ) ;
	}

	/**
	 * Output a admin notice when plugin dependencies not met.
	 */
	public function plugin_dependencies_notice() {
		$return = $this->plugin_dependencies_met( true ) ;

		if ( true !== $return && current_user_can( 'activate_plugins' ) ) {
			$dependency_notice = $return ;
			printf( '<div class="error"><p>%s</p></div>', wp_kses_post( $dependency_notice ) ) ;
		}
	}

	/**
	 * Is frontend request ?
	 *
	 * @return bool
	 */
	private function is_frontend() {
		return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) ;
	}

	/**
	 * Include required core files.
	 */
	private function include_files() {
		include_once('includes/class-enr-autoload.php') ;
		include_once('includes/enr-core-functions.php') ;
		include_once('includes/class-enr-install.php') ;
		include_once('includes/class-enr-ajax.php') ;
		include_once('includes/privacy/class-enr-privacy.php') ;
		include_once('includes/class-enr-action-scheduler.php') ;
		include_once('includes/class-enr-shipping-cycle.php') ;
		include_once('includes/class-enr-subscriptions-limiter.php') ;
		include_once('includes/class-enr-subscriptions-manager.php') ;

		if ( is_admin() ) {
			include_once('includes/admin/class-enr-admin.php') ;
		}

		if ( $this->is_frontend() ) {
			include_once('includes/enr-template-hooks.php') ;
		}
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		register_activation_hook( ENR_FILE, array( 'ENR_Install', 'install' ) ) ;
		add_action( 'init', array( $this, 'init' ), 5 ) ;
	}

	/**
	 * Load Localization files.
	 */
	public function load_plugin_textdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale() ;
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale() ;
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'enhancer-for-woocommerce-subscriptions' ) ;

		unload_textdomain( 'enhancer-for-woocommerce-subscriptions' ) ;
		load_textdomain( 'enhancer-for-woocommerce-subscriptions', WP_LANG_DIR . '/enhancer-for-woocommerce-subscriptions/enhancer-for-woocommerce-subscriptions-' . $locale . '.mo' ) ;
		load_plugin_textdomain( 'enhancer-for-woocommerce-subscriptions', false, dirname( plugin_basename( ENR_FILE ) ) . '/languages' ) ;
	}

	/**
	 * Init ENR_For_WC_Subscriptions when WordPress Initializes. 
	 */
	public function init() {
		do_action( 'before_enr_init' ) ;

		$this->mailer->init() ; //Load mailer

		do_action( 'enr_init' ) ;
	}

	/**
	 * Email Class.
	 *
	 * @return ENR_Emails
	 */
	public function mailer() {
		return ENR_Emails::instance() ;
	}

}

/**
 * Main instance of ENR_For_WC_Subscriptions.
 * Returns the main instance of ENR_For_WC_Subscriptions.
 *
 * @return ENR_For_WC_Subscriptions
 */
function _enr() {
	return ENR_For_WC_Subscriptions::instance() ;
}

/**
 * Run Enhancer for WooCommerce Subscriptions
 */
_enr() ;
