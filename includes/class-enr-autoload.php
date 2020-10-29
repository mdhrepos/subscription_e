<?php

defined( 'ABSPATH' ) || exit ;

/**
 * Enhancer for WooCommerce Subscriptions Autoloader.
 */
class ENR_Autoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_path = '' ;

	/**
	 * Construct ENR_Autoloader
	 */
	public function __construct() {
		$this->include_path = ENR_DIR . 'includes/' ;

		spl_autoload_register( array( $this, 'autoload' ) ) ;
	}

	/**
	 * Auto-load our classes on demand to reduce memory consumption.
	 *
	 * @param string $class Class name.
	 */
	public function autoload( $class ) {
		$class = strtolower( $class ) ;

		//Make sure our classes are going to load
		if ( 0 !== strpos( $class, 'enr_' ) ) {
			return ;
		}

		$file = 'class-' . str_replace( '_', '-', $class ) . '.php' ; //Retrieve file name from class name
		$path = $this->include_path . $file ;

		//Include a class file.
		if ( $path && is_readable( $path ) ) {
			include_once $path ;
		}
	}

}

new ENR_Autoloader() ;
