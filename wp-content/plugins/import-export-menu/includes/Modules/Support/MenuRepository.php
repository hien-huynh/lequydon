<?php
/**
 * Read access to WordPress navigation menus, their items, and location assignments.
 *
 * @package Import_Export_Menu
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Thin, testable wrapper over WordPress' nav-menu read functions.
 *
 * Gives the export serializer (and, from Sprint 4, the importer) a single seam
 * to read menus through instead of scattering `wp_get_nav_menu_*` calls across
 * modules. Write helpers are intentionally absent until the importer needs
 * them — added on demand, not speculatively.
 *
 * @since 1.1.0
 */
final class MenuRepository {

	/**
	 * Every navigation menu registered on the site.
	 *
	 * @return \WP_Term[] Menu terms, empty when none exist.
	 */
	public function all_menus(): array {
		return wp_get_nav_menus();
	}

	/**
	 * Resolve a single menu by its term id.
	 *
	 * @param int $menu_id Menu term id.
	 * @return \WP_Term|null The menu term, or null when it does not exist.
	 */
	public function menu( int $menu_id ): ?\WP_Term {
		$menu = wp_get_nav_menu_object( $menu_id );
		return $menu instanceof \WP_Term ? $menu : null;
	}

	/**
	 * Resolve a single menu by its name (used to detect import name clashes).
	 *
	 * @param string $name Menu name.
	 * @return \WP_Term|null The menu term, or null when no menu has that name.
	 */
	public function find_menu_by_name( string $name ): ?\WP_Term {
		$menu = wp_get_nav_menu_object( $name );
		return $menu instanceof \WP_Term ? $menu : null;
	}

	/**
	 * Menu items belonging to a menu, in menu order, fully set up.
	 *
	 * Items come through `wp_get_nav_menu_items()`, so each already carries the
	 * resolved `type`, `object`, `object_id`, `url`, `classes`, `target`, … that
	 * `wp_setup_nav_menu_item()` populates.
	 *
	 * @param int $menu_id Menu term id.
	 * @return \WP_Post[] Menu item posts, empty when the menu has none.
	 */
	public function items( int $menu_id ): array {
		$items = wp_get_nav_menu_items( $menu_id );
		return is_array( $items ) ? $items : array();
	}

	/**
	 * Most recent modification time across a menu's items, in GMT.
	 *
	 * Menus are taxonomy terms and carry no modified date of their own, so we
	 * derive one from the items: the newest `post_modified_gmt`. Returns null for
	 * an empty menu, letting the caller show a placeholder.
	 *
	 * @param int $menu_id Menu term id.
	 * @return string|null `Y-m-d H:i:s` GMT timestamp, or null when the menu is empty.
	 */
	public function last_modified( int $menu_id ): ?string {
		$latest = null;
		foreach ( $this->items( $menu_id ) as $item ) {
			$modified = (string) $item->post_modified_gmt;
			if ( '' !== $modified && ( null === $latest || $modified > $latest ) ) {
				$latest = $modified;
			}
		}
		return $latest;
	}

	/**
	 * Theme-location-slug → assigned-menu-id map.
	 *
	 * @return array<string,int> Location slug keyed to the menu id assigned there.
	 */
	public function location_map(): array {
		return array_map( 'intval', get_nav_menu_locations() );
	}

	/**
	 * Theme location slugs a given menu is assigned to.
	 *
	 * @param int $menu_id Menu term id.
	 * @return string[] Location slugs, empty when the menu is unassigned.
	 */
	public function locations_for_menu( int $menu_id ): array {
		$locations = array();
		foreach ( $this->location_map() as $location => $assigned_menu_id ) {
			if ( $assigned_menu_id === $menu_id ) {
				$locations[] = (string) $location;
			}
		}
		return $locations;
	}

	/**
	 * Theme locations the active theme registered, slug → human label.
	 *
	 * @return array<string,string> Location slug keyed to its label.
	 */
	public function registered_locations(): array {
		return get_registered_nav_menus();
	}

	/**
	 * Whether a menu auto-adds new top-level pages.
	 *
	 * Mirrors the "Automatically add new top-level pages to this menu" checkbox,
	 * stored as a list of menu ids under the `nav_menu_options` option.
	 *
	 * @param int $menu_id Menu term id.
	 * @return bool
	 */
	public function auto_adds_pages( int $menu_id ): bool {
		$options = get_option( 'nav_menu_options' );
		if ( ! is_array( $options ) || empty( $options['auto_add'] ) || ! is_array( $options['auto_add'] ) ) {
			return false;
		}
		return in_array( $menu_id, array_map( 'intval', $options['auto_add'] ), true );
	}
}
