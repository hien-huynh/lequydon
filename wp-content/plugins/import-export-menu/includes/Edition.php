<?php
/**
 * Single source of truth for the plugin's Free/Pro edition status.
 *
 * @package Import_Export_Menu
 * @since   2.2.0
 */

declare(strict_types=1);

namespace ImportExportMenu;

defined( 'ABSPATH' ) || exit;

/**
 * Answers whether the Pro edition is present, unlocked, and currently licensed.
 *
 * Keeps every edition check in one place so the Free plugin never contains
 * license logic (a wp.org requirement). A separate Pro plugin supplies the real
 * answers by hooking the filters below; without it, every method returns false
 * and the plugin runs as Free.
 *
 * The three methods serve distinct purposes — do not collapse them:
 *
 * - {@see self::is_pro_active()}   presence only (is the Pro plugin loaded?).
 * - {@see self::is_pro_unlocked()} runtime feature gate. Persistent: stays true
 *   after a license has ever activated, even once it expires.
 * - {@see self::is_pro_licensed()} validity right now. For the UI badge and the
 *   updater check — NOT a feature gate.
 *
 * @since 2.2.0
 */
final class Edition {

	/**
	 * Whether the Pro plugin is loaded.
	 *
	 * Checks the constant only — tells presence, not entitlement.
	 *
	 * @return bool
	 */
	public static function is_pro_active(): bool {
		return defined( 'IMPORT_EXPORT_MENU_PRO_LOADED' );
	}

	/**
	 * Whether a license has ever successfully activated.
	 *
	 * Persistent gate for Pro features: stays true even after the license
	 * expires, so an expired license never removes access to already-unlocked
	 * features. The Pro plugin supplies the answer via the filter.
	 *
	 * @return bool
	 */
	public static function is_pro_unlocked(): bool {
		return self::is_pro_active()
			&& (bool) apply_filters( 'import_export_menu_pro_unlocked', false );
	}

	/**
	 * Whether the license is valid right now.
	 *
	 * Drives the UI badge and the updater check only — never use it to gate a
	 * feature (use {@see self::is_pro_unlocked()} for that). The Pro plugin
	 * supplies the answer via the filter.
	 *
	 * @return bool
	 */
	public static function is_pro_licensed(): bool {
		return self::is_pro_active()
			&& (bool) apply_filters( 'import_export_menu_pro_license_valid', false );
	}
}
