<?php
namespace ElementorPro\Modules\WpCli;

use ElementorPro\License\Admin;
use ElementorPro\License\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Elementor Page Builder Pro cli tools.
 */
class License_Command extends \WP_CLI_Command {

	/**
	 * Activate Elementor Pro License key.
	 *
	 * ## EXAMPLES
	 *
	 *  1. wp elementor-pro license activate <license-key>
	 *      - This will try activate your license key.
	 */
	public function activate( $args, $assoc_args ) {
		$license_key = 'gplready';
		//$data = API::activate_license( $args[0] );
		Admin::set_license_key( $license_key );
		API::set_license_data( $data );

		\WP_CLI::success( 'The License has been activated successfully.' );
	}

	/**
	 * Deactivate Elementor Pro License key.
	 *
	 * ## EXAMPLES
	 *
	 *  1. wp elementor-pro license deactivate.
	 *      - This will deactivate your license key.
	 */
	public function deactivate() {
		API::deactivate_license();
		update_option( 'elementor_pro_license_key','gplready' );
		\WP_CLI::success( 'The License has been deactivated successfully.' );
	}
}
