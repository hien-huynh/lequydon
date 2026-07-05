<?php
/**
 * Admin-side hook controller.
 *
 * @link       https://yukyhendiawan.com
 * @since      2.1.0
 *
 * @package    Import_Export_Menu
 */

declare(strict_types=1);

namespace ImportExportMenu\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the plugin's top-level admin menu and admin-area side effects.
 *
 * The top-level entry exists purely as a parent for the Settings submenu added
 * by {@see \ImportExportMenu\Options\Framework}; it has no page of its own.
 *
 * @since 2.1.0
 */
final class Controller {

	private const PLUGIN_PAGE_SLUGS = array( 'import-export-menu', 'import-export-menu-settings' );

	/**
	 * Register WP hooks for this component.
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_admin_menu_page' ) );
		add_action( 'admin_menu', array( $this, 'remove_default_submenu' ), 999 );
		add_action( 'admin_init', array( $this, 'disable_admin_notices_on_specific_pages' ), 999999 );
	}

	/**
	 * Register the top-level "Import Export Menu" menu.
	 *
	 * The top-level page itself has no callback — Settings (registered by the
	 * Options Framework) is the first real page under it.
	 */
	public function register_admin_menu_page(): void {
		add_menu_page(
			__( 'Import Export Menu', 'import-export-menu' ),
			__( 'Import Export Menu', 'import-export-menu' ),
			'manage_options',
			'import-export-menu',
			'',
			'dashicons-admin-generic',
			30
		);
	}

	/**
	 * Hide the auto-generated submenu duplicate of the top-level entry.
	 *
	 * WordPress always inserts a submenu with the parent slug; we don't have a
	 * landing page so it would dead-end if left visible.
	 */
	public function remove_default_submenu(): void {
		remove_submenu_page( 'import-export-menu', 'import-export-menu' );
	}

	/**
	 * Strip default admin notices on the plugin's own pages.
	 *
	 * NOTE: this is intentionally aggressive — see the README about whitelisting
	 * core notices once the plugin grows beyond a boilerplate.
	 */
	public function disable_admin_notices_on_specific_pages(): void {
		global $plugin_page;
		if ( is_admin() && in_array( (string) $plugin_page, self::PLUGIN_PAGE_SLUGS, true ) ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'user_admin_notices' );
			remove_all_actions( 'network_admin_notices' );
		}
	}
}
