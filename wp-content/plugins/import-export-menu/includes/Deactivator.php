<?php
/**
 * Fired during plugin deactivation.
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
 * Plugin deactivation entry point.
 *
 * Fires the `import_export_menu_deactivate` action so downstream forks can hook
 * teardown code (cron unscheduling, transient cleanup, etc.).
 *
 * @since 2.1.0
 */
final class Deactivator {

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		do_action( 'import_export_menu_deactivate' );
	}
}
