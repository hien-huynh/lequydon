<?php
/**
 * Conditional feature module: show or hide menu items by login state.
 *
 * @package Import_Export_Menu
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Conditional;

use ImportExportMenu\Modules\ModuleInterface;
use ImportExportMenu\Modules\Support\MenuVisibility;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a per-item visibility rule to the native menu editor and enforces it.
 *
 * In wp-admin it renders a "Visibility" select on each nav-menu item and saves
 * the chosen rule to item post-meta. On the frontend it filters menu items so
 * an item shows only when its rule matches the viewer's login state — and a
 * hidden item takes its whole subtree with it, so no orphaned child is promoted
 * to the top level. All rule logic lives in {@see MenuVisibility}.
 *
 * @since 1.1.0
 */
final class ConditionalModule implements ModuleInterface {

	/**
	 * Editor request field carrying each item's rule, keyed by item id.
	 */
	private const FIELD = 'menu-item-visibility';

	/**
	 * Free tier — always loaded.
	 */
	public function tier(): string {
		return ModuleInterface::TIER_FREE;
	}

	/**
	 * Register the editor field, its save handler, and the frontend filter.
	 */
	public function register_hooks(): void {
		add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'render_field' ) );
		add_action( 'wp_update_nav_menu_item', array( $this, 'save_field' ), 10, 2 );
		add_filter( 'wp_get_nav_menu_items', array( $this, 'filter_items' ) );
	}

	/**
	 * Render the "Visibility" select for one item in the menu editor.
	 *
	 * @param int $item_id Menu item id.
	 */
	public function render_field( int $item_id ): void {
		$current = MenuVisibility::for_item( $item_id );
		$choices = MenuVisibility::choices();
		$field   = self::FIELD;

		require IMPORT_EXPORT_MENU_PATH . 'admin/partials/menu-item-visibility-field.php';
	}

	/**
	 * Persist the visibility rule submitted for one item from the menu editor.
	 *
	 * Guarded by the editor's own nonce; a no-op for any other caller of
	 * `wp_update_nav_menu_item` (e.g. the importer), which carries no such field.
	 *
	 * @param int $menu_id Menu term id (unused; required by the hook signature).
	 * @param int $item_id Menu item id being saved.
	 */
	public function save_field( int $menu_id, int $item_id ): void {
		unset( $menu_id );

		$nonce = isset( $_POST['update-nav-menu-nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['update-nav-menu-nonce'] ) )
			: '';
		if ( ! wp_verify_nonce( $nonce, 'update-nav_menu' ) ) {
			return;
		}
		if ( ! isset( $_POST[ self::FIELD ] ) || ! is_array( $_POST[ self::FIELD ] ) || ! isset( $_POST[ self::FIELD ][ $item_id ] ) ) {
			return;
		}

		$rule = sanitize_key( (string) wp_unslash( $_POST[ self::FIELD ][ $item_id ] ) );
		MenuVisibility::store( $item_id, $rule );
	}

	/**
	 * Drop frontend menu items hidden by their rule, with their whole subtree.
	 *
	 * @param mixed $items Menu items (`WP_Post[]` once set up), as passed by the filter.
	 * @return mixed The kept items, or the input untouched in wp-admin.
	 */
	public function filter_items( $items ) {
		if ( is_admin() || ! is_array( $items ) || empty( $items ) ) {
			return $items;
		}

		$logged_in = is_user_logged_in();
		$by_id     = array();
		foreach ( $items as $item ) {
			$by_id[ (int) $item->ID ] = $item;
		}

		$cache = array();
		$kept  = array();
		foreach ( $items as $item ) {
			if ( $this->item_visible( (int) $item->ID, $by_id, $logged_in, $cache ) ) {
				$kept[] = $item;
			}
		}

		return $kept;
	}

	/**
	 * Whether an item is visible: its own rule passes and every ancestor's does.
	 *
	 * @param int                 $id        Menu item id.
	 * @param array<int,\WP_Post> $by_id     Items indexed by id.
	 * @param bool                $logged_in Whether the viewer is logged in.
	 * @param array<int,bool>     $cache     Memoized results (also guards cycles).
	 * @return bool
	 */
	private function item_visible( int $id, array $by_id, bool $logged_in, array &$cache ): bool {
		if ( isset( $cache[ $id ] ) ) {
			return $cache[ $id ];
		}
		$cache[ $id ] = false;

		if ( ! isset( $by_id[ $id ] ) ) {
			$cache[ $id ] = true;
			return true;
		}

		if ( ! MenuVisibility::is_visible( MenuVisibility::for_item( $id ), $logged_in ) ) {
			return false;
		}

		$parent  = (int) ( $by_id[ $id ]->menu_item_parent ?? 0 );
		$visible = $parent > 0 && isset( $by_id[ $parent ] )
			? $this->item_visible( $parent, $by_id, $logged_in, $cache )
			: true;

		$cache[ $id ] = $visible;
		return $visible;
	}
}
