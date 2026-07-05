<?php
/**
 * Write access to WordPress navigation menus, items, and their settings.
 *
 * @package Import_Export_Menu
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Support;

defined( 'ABSPATH' ) || exit;

/**
 * The write counterpart of {@see MenuRepository}.
 *
 * Wraps WordPress' nav-menu mutators so the importer has a single, testable
 * seam to create menus, append items, re-parent them, and apply menu-level
 * settings (theme locations, auto-add pages). Each method returns plain ids so
 * the importer can build the old-id → new-id map it rebuilds hierarchy from.
 *
 * @since 1.1.0
 */
final class MenuWriter {

	/**
	 * Create a navigation menu.
	 *
	 * @param string $name Menu name.
	 * @return int New menu term id, or 0 on failure (e.g. duplicate name).
	 */
	public function create_menu( string $name ): int {
		$menu_id = wp_create_nav_menu( $name );
		return $menu_id instanceof \WP_Error ? 0 : (int) $menu_id;
	}

	/**
	 * Delete a navigation menu and all its items.
	 *
	 * @param int $menu_id Menu term id.
	 */
	public function delete_menu( int $menu_id ): void {
		wp_delete_nav_menu( $menu_id );
	}

	/**
	 * Append an item to a menu.
	 *
	 * @param int                 $menu_id Menu term id.
	 * @param array<string,mixed> $args    `wp_update_nav_menu_item()` args (`menu-item-*`).
	 * @return int New menu item id, or 0 on failure.
	 */
	public function create_item( int $menu_id, array $args ): int {
		$item_id = wp_update_nav_menu_item( $menu_id, 0, $args );
		return $item_id instanceof \WP_Error ? 0 : (int) $item_id;
	}

	/**
	 * Set an item's parent after both items exist.
	 *
	 * Hierarchy is rebuilt in a second pass: every item is created first, then
	 * re-parented by mapping the export's original parent id to the newly
	 * created item id — robust where the legacy plugin's title matching was not.
	 *
	 * @param int $item_id        Menu item id to re-parent.
	 * @param int $parent_item_id New parent menu item id (0 for top level).
	 */
	public function set_item_parent( int $item_id, int $parent_item_id ): void {
		update_post_meta( $item_id, '_menu_item_menu_item_parent', (string) $parent_item_id );
	}

	/**
	 * Store an item's visibility rule (or clear it when the rule is the default).
	 *
	 * @param int    $item_id Menu item id.
	 * @param string $rule    One of the {@see MenuVisibility} rule constants.
	 */
	public function set_item_visibility( int $item_id, string $rule ): void {
		MenuVisibility::store( $item_id, $rule );
	}

	/**
	 * Assign a menu to theme locations the active theme registers.
	 *
	 * Unregistered locations are skipped — they cannot be displayed and would
	 * only leave stale theme-mod entries.
	 *
	 * @param int      $menu_id   Menu term id.
	 * @param string[] $locations Location slugs.
	 */
	public function assign_locations( int $menu_id, array $locations ): void {
		if ( empty( $locations ) ) {
			return;
		}
		$registered = get_registered_nav_menus();
		$map        = get_nav_menu_locations();

		foreach ( $locations as $location ) {
			$location = (string) $location;
			if ( isset( $registered[ $location ] ) ) {
				$map[ $location ] = $menu_id;
			}
		}
		set_theme_mod( 'nav_menu_locations', $map );
	}

	/**
	 * Toggle whether a menu auto-adds new top-level pages.
	 *
	 * @param int  $menu_id Menu term id.
	 * @param bool $enabled Whether auto-add is on.
	 */
	public function set_auto_add( int $menu_id, bool $enabled ): void {
		$options = get_option( 'nav_menu_options' );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$auto_add = ( isset( $options['auto_add'] ) && is_array( $options['auto_add'] ) )
			? array_map( 'intval', $options['auto_add'] )
			: array();

		$auto_add = array_values( array_diff( $auto_add, array( $menu_id ) ) );
		if ( $enabled ) {
			$auto_add[] = $menu_id;
		}

		$options['auto_add'] = $auto_add;
		update_option( 'nav_menu_options', $options );
	}
}
