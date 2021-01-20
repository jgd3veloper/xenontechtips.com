<?php
define('WP_CACHE', true); // Added by WP Rocket
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'u693517278_xenontechtips');

/** MySQL database username */
define('DB_USER', 'u693517278_xenontechtips');

/** MySQL database password */
define('DB_PASSWORD', 'vHMealZB');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'FZP,xRRc^Df)RmV%?bG7oXyb@EBuiJCq]Ke0jbBl/Vi8N8VtmnefXc{_[0ODs^#?');
define('SECURE_AUTH_KEY',  'oxOxPUgN[*v:o?T<<Fe.@s_~FW]%p()-rN&p+).P^q!0RVw[#1/`|Va{4FVpK#Q-');
define('LOGGED_IN_KEY',    'M9|]0)g_rr.E6`5%e^2tc*m{kQ:O9?M[9<Vy];M/pAU*U5Grs2Kl|y Dl UaU2lO');
define('NONCE_KEY',        'g:RHuV:=D]-1Pn:TB]D^]-zH!Q>}S:/^7]x[9X|*Ya,*f)2,DdBK6}S<~ibJD<4M');
define('AUTH_SALT',        't`iABEcGZtTD fNE,RBVOta_eq9Ywp$r:I:ea+b*NIv2IZ+Nq{?ZOM&jVN$tCGP%');
define('SECURE_AUTH_SALT', '0b,JR%6mtt(9J_F._ro[3~AnpZ>U=u}og#=Qag_fc_UQAn%n=$ZIt`l[L,n)=Mw.');
define('LOGGED_IN_SALT',   'N0WX$!mkYf*ZGwdW;;_..LHA:@I)s|91bj06(B73Ua/=> aLe R5WlUUJ#WBG(0R');
define('NONCE_SALT',       '8I%SYyZlWp4Q{DDBI^Y[;F?V11$R_bUp!35<H9K`q;FIle XRnI,mz)7ZCZlq*Fh');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
