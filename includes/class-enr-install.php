<?php

defined( 'ABSPATH' ) || exit ;

/**
 * Installation related functions and actions.
 * 
 * @class ENR_Install
 * @package Class
 */
class ENR_Install {

	/**
	 * Init Install.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 9 ) ;
	}

	/**
	 * Check ENR version and run updater
	 */
	public static function check_version() {
		if ( get_option( ENR_PREFIX . 'version' ) !== _enr()->get_version() ) {
			self::install() ;
			do_action( 'enr_updated' ) ;
		}
	}

	/**
	 * Install ENR.
	 */
	public static function install() {
		if ( ! defined( 'ENR_INSTALLING' ) ) {
			define( 'ENR_INSTALLING', true ) ;
		}

		self::update_enr_version() ;

		do_action( 'enr_installed' ) ;
	}

	/**
	 * Is this a brand new ENR install?
	 * A brand new install has no version yet.
	 *
	 * @return bool
	 */
	private static function is_new_install() {
		return is_null( get_option( ENR_PREFIX . 'version', null ) ) ;
	}

	/**
	 * Update ENR version to current.
	 */
	private static function update_enr_version() {
		delete_option( ENR_PREFIX . 'version' ) ;
		add_option( ENR_PREFIX . 'version', _enr()->get_version() ) ;
	}

}

ENR_Install::init() ;
