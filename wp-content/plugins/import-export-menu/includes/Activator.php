<?php
/**
 * Fired during plugin activation.
 *
 * @link       https://yukyhendiawan.com
 * @since      2.1.0
 *
 * @package    Import_Export_Menu
 */

declare(strict_types=1);

namespace ImportExportMenu;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin activation entry point.
 *
 * Fires the `import_export_menu_activate` action so downstream forks can hook setup
 * code (table creation, default options, capability assignment, etc.).
 *
 * @since 2.1.0
 */
final class Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		do_action( 'import_export_menu_activate' );
	}
}
