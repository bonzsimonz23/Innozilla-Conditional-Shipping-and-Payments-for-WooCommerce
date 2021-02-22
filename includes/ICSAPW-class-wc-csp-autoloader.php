<?php
/**
 * Innozilla Conditional Shipping and Payments for WooCommerce Autoloader.
 *
 * @author   Innozilla
 * @package  Innozilla Conditional Shipping and Payments for WooCommerce
 * @since    1.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * CSP Autoloader class.
 */
class WC_CSP_Autoloader {

	/**
	 * Path to the includes directory.
	 *
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor.
	 */
	public function __construct() {

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = ICSAPW_WC_()->plugin_path() . '/includes/';
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class Class name.
	 * @return string
	 */
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}

	/**
	 * Include a class file.
	 *
	 * @param  string $path File path.
	 * @return bool Successful or not.
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once $path;
			return true;
		}
		return false;
	}

	/**
	 * Auto-load WC CSP classes on demand to reduce memory consumption.
	 *
	 * @param string $class Class name.
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );

		if ( 0 !== strpos( $class, 'wc_csp' ) ) {
			return;
		}

		$file = $this->get_file_name_from_class( $class );
		$path = '';


		if ( 0 === strpos( $class, 'wc_csp_condition' ) ) {
			$path = $this->include_path . 'conditions/';
		} elseif ( 0 === strpos( $class, 'wc_csp_restrict' ) ) {
			$path = $this->include_path . 'restrictions/';
		}

		if ( empty( $path ) || ! $this->load_file( $path . $file ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new WC_CSP_Autoloader();
