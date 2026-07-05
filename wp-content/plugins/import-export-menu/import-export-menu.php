<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. It defines plugin-wide constants, registers activation/deactivation
 * hooks, loads the Composer autoloader, and kicks off the ImportExportMenu\Plugin runtime.
 *
 * @link              https://yukyhendiawan.com
 * @since             1.0.0
 * @package           Import_Export_Menu
 *
 * @wordpress-plugin
 * Plugin Name:       Import Export Menu
 * Plugin URI:        https://wordpress.org/plugins/import-export-menu/
 * Description:       Import, export, and manage WordPress navigation menus. Safely migrate menu structures between sites with hierarchy, locations, and item settings intact.
 * Version:           3.0.0
 * Author:            Yuky Hendiawan
 * Author URI:        https://yukyhendiawan.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       import-export-menu
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'IMPORT_EXPORT_MENU_VERSION', '3.0.0' );
define( 'IMPORT_EXPORT_MENU_NAME', 'Import Export Menu' );
/** Plugin root URL (with trailing slash, provided by plugin_dir_url()). Not a filesystem path. */
define( 'IMPORT_EXPORT_MENU_PLUGIN_URL', (string) plugin_dir_url( __FILE__ ) );
define( 'IMPORT_EXPORT_MENU_PATH', (string) plugin_dir_path( __FILE__ ) );
define( 'IMPORT_EXPORT_MENU_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets/' );
define( 'IMPORT_EXPORT_MENU_SLUG', (string) dirname( plugin_basename( __FILE__ ) ) );

// Hide the bundled field-type showcase: this is a real product, not the starter.
// Define this to a falsy value before load (e.g. in the test bootstrap) to keep
// the reference gallery available.
if ( ! defined( 'IMPORT_EXPORT_MENU_DISABLE_DEMO' ) ) {
	define( 'IMPORT_EXPORT_MENU_DISABLE_DEMO', true );
}

/**
 * Composer autoloader — exposes the ImportExportMenu\* PSR-4 namespace.
 */
require __DIR__ . '/vendor/autoload.php';

register_activation_hook( __FILE__, array( \ImportExportMenu\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \ImportExportMenu\Deactivator::class, 'deactivate' ) );

/**
 * Begins execution of the plugin.
 *
 * Everything within the plugin is registered via hooks, so kicking off here
 * does not affect the page life cycle.
 *
 * @since 1.0.0
 */
( new \ImportExportMenu\Plugin( 'import-export-menu', IMPORT_EXPORT_MENU_VERSION ) )->run();
