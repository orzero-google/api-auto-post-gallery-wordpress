<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'lolita');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

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
define('AUTH_KEY',         'F;[UxroB`eG|>1 G/]V!q{Rupsl*<11A*ZRsX&p.+qsfT|vA;1]q=,Oo@{|}Rh-9');
define('SECURE_AUTH_KEY',  'hAU]8Pz;IdwBhK@wU^|ey6j4=|M x)k~w_;pD#Gnl[i~=@4YswKszS|J![nOhVBX');
define('LOGGED_IN_KEY',    'a%_+Y},+q@d+$2Od8*u]Kgp~Yw5%5dT5_2[s#!$`J?8h[@]RJHVQ|yUJA?E[$CR#');
define('NONCE_KEY',        'GrKdFqA3w95Ya-8 [[<lSQ.;fmogaB<U=@=|2cl]XF.v]~2JxX`UbiAX|jkhu-4`');
define('AUTH_SALT',        'o]7~ $4^U(#orL8;1J]]6xoT >6_?()ihyA|a ?>oAB5[7B#JOU{Hq+H46g+FYQ{');
define('SECURE_AUTH_SALT', 'z/<e,D}3CUMzX,2z@&U,+nBI-S(q7]6;`gZ:WI>{@%l5f@k EK|6(pUWFH]4R*.-');
define('LOGGED_IN_SALT',   'J:MM5+CU-89rf{^nJg&cNH0x>U8G~M0-GP`]~|$c5|Wn;vr?I3=t]/8^|+4N%[1g');
define('NONCE_SALT',       'Lgork|LI%3% ^<i2kg=kD?MGOB8njm]}vOd:X!B]/Ai_VS!+#{gY&4{bE`U@c{f=');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */

//Added By xami
define('WP_DEBUG', false);
//define('WP_DEBUG_LOG', true);
//define('WP_DEBUG_DISPLAY', false);
//@ini_set('display_errors',0);
define('AUTOSAVE_INTERVAL', 36000);
define('WP_POST_REVISIONS', false);
define('WP_SITEURL', $_SERVER['HTTP_HOST']);
define('WP_HOME', $_SERVER['HTTP_HOST']);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
