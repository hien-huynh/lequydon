<?php
/**
 * Collects feature modules and boots each eligible one.
 *
 * @package Import_Export_Menu
 * @since   2.2.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules;

use ImportExportMenu\Edition;

defined( 'ABSPATH' ) || exit;

/**
 * Single entry point that turns every registered module on.
 *
 * Modules are gathered through the `import_export_menu_modules_register` filter so this
 * plugin and a separate Pro plugin can both contribute without touching each
 * other's code. Pro-tier modules stay dormant until the Pro edition is
 * unlocked, so a registered-but-locked module never runs.
 *
 * Collection is deferred to `plugins_loaded` so a Pro plugin loaded after this
 * one still gets a chance to register its modules first.
 *
 * @since 2.2.0
 */
final class ModuleLoader {

	/**
	 * Defer module collection until every plugin has registered its modules.
	 */
	public function register_hooks(): void {
		add_action( 'plugins_loaded', array( $this, 'boot' ) );
	}

	/**
	 * Register the hooks of every eligible module.
	 */
	public function boot(): void {
		/**
		 * Filters the feature modules to boot.
		 *
		 * A separate Pro plugin hooks this to register its own modules without
		 * modifying this plugin. Each entry must implement {@see ModuleInterface}.
		 *
		 * @since 2.2.0
		 *
		 * @param ModuleInterface[] $modules Module instances to register.
		 */
		$modules = apply_filters( 'import_export_menu_modules_register', array() );

		foreach ( $modules as $module ) {
			if ( ModuleInterface::TIER_PRO === $module->tier() && ! Edition::is_pro_unlocked() ) {
				continue;
			}
			$module->register_hooks();
		}
	}
}
