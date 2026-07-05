<?php
/**
 * Main plugin bootstrap class.
 *
 * @link       https://yukyhendiawan.com
 * @since      2.1.0
 *
 * @package    Import_Export_Menu
 */

declare(strict_types=1);

namespace ImportExportMenu;

use ImportExportMenu\Admin\Controller as AdminController;
use ImportExportMenu\Frontend\Controller as FrontendController;
use ImportExportMenu\Modules\Conditional\ConditionalModule;
use ImportExportMenu\Modules\Export\ExportModule;
use ImportExportMenu\Modules\Import\ImportModule;
use ImportExportMenu\Modules\Menus\MenusModule;
use ImportExportMenu\Modules\ModuleInterface;
use ImportExportMenu\Modules\ModuleLoader;
use ImportExportMenu\Options\Framework as OptionsFramework;
use ImportExportMenu\Options\Settings as OptionsSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's components together and lets each one register its own hooks.
 *
 * @since 2.1.0
 */
final class Plugin {

	/**
	 * Plugin handle used as i18n text-domain and asset handle prefix.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Plugin version, propagated to assets for cache busting.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Capture plugin metadata used by all components.
	 *
	 * @param string $name    Plugin handle.
	 * @param string $version Plugin version.
	 */
	public function __construct( string $name, string $version ) {
		$this->name    = $name;
		$this->version = $version;
	}

	/**
	 * Instantiate components and let each one register its own hooks.
	 */
	public function run(): void {
		( new I18n() )->register_hooks();

		( new AdminController() )->register_hooks();
		( new FrontendController( $this->name, $this->version ) )->register_hooks();

		OptionsFramework::create( $this->name, $this->version )->register_hooks();
		( new OptionsSettings() )->register_hooks();

		add_filter( 'import_export_menu_modules_register', array( $this, 'register_free_modules' ) );
		( new ModuleLoader() )->register_hooks();
	}

	/**
	 * Contribute the bundled Free feature modules to the loader.
	 *
	 * @param ModuleInterface[] $modules Modules collected so far.
	 * @return ModuleInterface[]
	 */
	public function register_free_modules( array $modules ): array {
		$modules[] = new MenusModule();
		$modules[] = new ExportModule();
		$modules[] = new ImportModule();
		$modules[] = new ConditionalModule();
		return $modules;
	}
}
