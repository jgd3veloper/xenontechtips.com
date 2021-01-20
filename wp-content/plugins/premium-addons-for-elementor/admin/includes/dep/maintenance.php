<?php
/**
 * PA Rollback Helper
 */

use PremiumAddons\Admin\Includes\PA_Rollback;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 *
 *  Fire the rollback function.
 */
function post_premium_addons_rollback() {

	check_admin_referer( 'premium_addons_rollback' );

	$plugin_slug = basename( PREMIUM_ADDONS_FILE, '.php' );

	$pa_rollback = new PA_Rollback(
		array(
			'version'     => PREMIUM_ADDONS_STABLE_VERSION,
			'plugin_name' => PREMIUM_ADDONS_BASENAME,
			'plugin_slug' => $plugin_slug,
			'package_url' => sprintf( 'https://downloads.wordpress.org/plugin/%s.%s.zip', $plugin_slug, PREMIUM_ADDONS_STABLE_VERSION ),
		)
	);

	$pa_rollback->run();

	wp_die(
		'',
		esc_html( __( 'Rollback to Previous Version', 'premium-addons-for-elementor' ) ),
		array(
			'response' => 200,
		)
	);
}

