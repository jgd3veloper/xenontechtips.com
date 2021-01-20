<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

define( 'WP_ROCKET_ADVANCED_CACHE', true );
$rocket_cache_path  = '/home/u693517278/domains/xenontechtips.com/public_html/wp-content/cache/wp-rocket/';
$rocket_config_path = '/home/u693517278/domains/xenontechtips.com/public_html/wp-content/wp-rocket-config/';

if ( file_exists( '/home/u693517278/domains/xenontechtips.com/public_html/wp-content/plugins/wp-rocket/inc/front/process.php' ) && file_exists( '/home/u693517278/domains/xenontechtips.com/public_html/wp-content/plugins/wp-rocket/vendor/autoload.php' ) && version_compare( phpversion(), '5.4' ) >= 0 ) {
	include '/home/u693517278/domains/xenontechtips.com/public_html/wp-content/plugins/wp-rocket/vendor/autoload.php';
	include '/home/u693517278/domains/xenontechtips.com/public_html/wp-content/plugins/wp-rocket/inc/front/process.php';
} else {
	define( 'WP_ROCKET_ADVANCED_CACHE_PROBLEM', true );
}
