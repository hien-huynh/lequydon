<?php
/**
 * Internationalization handler.
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
 * Loads the plugin text-domain on the right WP hook.
 *
 * Required for translations bundled with the plugin (e.g. private/off-repo
 * builds). For wp.org distribution, WordPress auto-loads `.mo` files served
 * from translate.wordpress.org and this call is effectively a no-op.
 *
 * @since 2.1.0
 */
final class I18n {

	/**
	 * Register WP hooks for this component.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Load the plugin text-domain for translations.
	 */
	public function load_plugin_textdomain(): void {
		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Needed for bundled translations outside wp.org; WP auto-loads on wp.org so this is a safe no-op there.
		load_plugin_textdomain(
			'import-export-menu',
			false,
			IMPORT_EXPORT_MENU_SLUG . '/languages/'
		);
	}
}
