<?php
/**
 * Serializes navigation menus into the portable schema-v1 payload.
 *
 * @package Import_Export_Menu
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the export payload: menus, their items, locations, and provenance.
 *
 * Reads through {@see MenuRepository} and encodes each item's content reference
 * with {@see ReferenceEncoder}, producing a plain array the Export module
 * (Sprint 3) hands to `wp_json_encode()`. Each item keeps its original id and
 * parent id so the importer can rebuild the hierarchy by id remapping instead of
 * the legacy plugin's fragile title matching.
 *
 * @since 1.1.0
 */
final class MenuSerializer {

	/**
	 * Menu read layer.
	 *
	 * @var MenuRepository
	 */
	private $menus;

	/**
	 * Portable object-reference encoder.
	 *
	 * @var ReferenceEncoder
	 */
	private $references;

	/**
	 * Wire the collaborators.
	 *
	 * @param MenuRepository   $menus      Menu read layer.
	 * @param ReferenceEncoder $references Object-reference encoder.
	 */
	public function __construct( MenuRepository $menus, ReferenceEncoder $references ) {
		$this->menus      = $menus;
		$this->references = $references;
	}

	/**
	 * Build the full export envelope.
	 *
	 * @param int[] $menu_ids Menu ids to export; empty exports every menu.
	 * @return array<string,mixed> Schema-v1 payload.
	 */
	public function serialize( array $menu_ids = array() ): array {
		$menus = array();
		foreach ( $this->select_menus( $menu_ids ) as $menu ) {
			$menus[] = $this->serialize_menu( $menu );
		}

		return array(
			'format'         => MenuSchema::FORMAT,
			'schema_version' => MenuSchema::VERSION,
			'generator'      => IMPORT_EXPORT_MENU_NAME,
			'plugin_version' => IMPORT_EXPORT_MENU_VERSION,
			'exported_at'    => gmdate( 'c' ),
			'site_url'       => home_url(),
			'menus'          => $menus,
		);
	}

	/**
	 * Serialize a single menu node (name, slug, locations, items).
	 *
	 * @param \WP_Term $menu Menu term.
	 * @return array<string,mixed>
	 */
	public function serialize_menu( \WP_Term $menu ): array {
		$items = array();
		foreach ( $this->menus->items( (int) $menu->term_id ) as $item ) {
			$items[] = $this->serialize_item( $item );
		}

		return array(
			'name'           => (string) $menu->name,
			'slug'           => (string) $menu->slug,
			'locations'      => $this->menus->locations_for_menu( (int) $menu->term_id ),
			'auto_add_pages' => $this->menus->auto_adds_pages( (int) $menu->term_id ),
			'items'          => $items,
		);
	}

	/**
	 * Serialize a single menu item, including its portable reference.
	 *
	 * @param \WP_Post $item Fully set-up menu item.
	 * @return array<string,mixed>
	 */
	public function serialize_item( \WP_Post $item ): array {
		$fields = $item->to_array();

		return array(
			'id'          => (int) $item->ID,
			'parent'      => $this->int_field( $fields, 'menu_item_parent' ),
			'menu_order'  => (int) $item->menu_order,
			'title'       => $this->string_field( $fields, 'title' ),
			'url'         => $this->string_field( $fields, 'url' ),
			'target'      => $this->string_field( $fields, 'target' ),
			'attr_title'  => $this->string_field( $fields, 'attr_title' ),
			'description' => $this->string_field( $fields, 'description' ),
			'xfn'         => $this->string_field( $fields, 'xfn' ),
			'classes'     => $this->classes_field( $fields ),
			'visibility'  => MenuVisibility::for_item( (int) $item->ID ),
			'reference'   => $this->references->encode( $item ),
		);
	}

	/**
	 * Resolve the menus to export.
	 *
	 * @param int[] $menu_ids Requested menu ids; empty means all.
	 * @return \WP_Term[]
	 */
	private function select_menus( array $menu_ids ): array {
		if ( empty( $menu_ids ) ) {
			return $this->menus->all_menus();
		}

		$selected = array();
		foreach ( $menu_ids as $menu_id ) {
			$menu = $this->menus->menu( (int) $menu_id );
			if ( $menu instanceof \WP_Term ) {
				$selected[] = $menu;
			}
		}

		return $selected;
	}

	/**
	 * Read a menu item field as a string.
	 *
	 * @param array<string,mixed> $fields Menu item fields.
	 * @param string              $key    Field key.
	 * @return string
	 */
	private function string_field( array $fields, string $key ): string {
		return isset( $fields[ $key ] ) ? (string) $fields[ $key ] : '';
	}

	/**
	 * Read a menu item field as an integer.
	 *
	 * @param array<string,mixed> $fields Menu item fields.
	 * @param string              $key    Field key.
	 * @return int
	 */
	private function int_field( array $fields, string $key ): int {
		return isset( $fields[ $key ] ) ? (int) $fields[ $key ] : 0;
	}

	/**
	 * Read the CSS classes field as a clean list of non-empty strings.
	 *
	 * @param array<string,mixed> $fields Menu item fields.
	 * @return string[]
	 */
	private function classes_field( array $fields ): array {
		$classes = $fields['classes'] ?? array();
		if ( ! is_array( $classes ) ) {
			return array();
		}

		$clean = array();
		foreach ( $classes as $class ) {
			$class = (string) $class;
			if ( '' !== $class ) {
				$clean[] = $class;
			}
		}

		return $clean;
	}
}
