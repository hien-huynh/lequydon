<?php
/**
 * Contract every feature module must implement.
 *
 * @package Import_Export_Menu
 * @since   2.2.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules;

defined( 'ABSPATH' ) || exit;

/**
 * Standard shape of a feature module.
 *
 * Each maintenance/feature tool lives in `includes/Modules/<Name>/` and
 * implements this interface so the loader can boot any module uniformly and
 * gate it by tier. Modules are collected through the
 * `import_export_menu_modules_register` filter, letting a separate Pro plugin register
 * its own modules without touching this plugin's code.
 *
 * @since 2.2.0
 */
interface ModuleInterface {

	/**
	 * Free tier — always loaded.
	 */
	public const TIER_FREE = 'free';

	/**
	 * Pro tier — only loaded once the Pro edition is unlocked.
	 */
	public const TIER_PRO = 'pro';

	/**
	 * Distribution tier of this module.
	 *
	 * @return string Either {@see self::TIER_FREE} or {@see self::TIER_PRO}.
	 */
	public function tier(): string;

	/**
	 * Register every WP hook this module needs.
	 *
	 * The single place a module is allowed to call `add_action()` /
	 * `add_filter()`. Must have no side effects beyond hook registration.
	 */
	public function register_hooks(): void;
}
