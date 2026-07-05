<?php
/**
 * Clones a navigation menu through the export/import engine.
 *
 * @package Import_Export_Menu
 * @since   3.0.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Duplicates a menu by serializing it and re-importing it as a new menu.
 *
 * Reusing {@see MenuSerializer} + {@see MenuImporter} means the copy inherits the
 * migration-safe object references, the rebuilt-by-id hierarchy, and the per-item
 * visibility rules for free. The copy is named "<name> (copy)" (the importer
 * auto-suffixes any further clash) and is deliberately stripped of the source's
 * theme-location and auto-add-pages settings — only one menu may own a location,
 * so a clone must not silently hijack it.
 *
 * @since 3.0.0
 */
final class MenuDuplicator {

	/**
	 * Menu serializer.
	 *
	 * @var MenuSerializer
	 */
	private $serializer;

	/**
	 * Menu import engine.
	 *
	 * @var MenuImporter
	 */
	private $importer;

	/**
	 * Wire the collaborators.
	 *
	 * @param MenuSerializer $serializer Menu serializer.
	 * @param MenuImporter   $importer   Menu import engine.
	 */
	public function __construct( MenuSerializer $serializer, MenuImporter $importer ) {
		$this->serializer = $serializer;
		$this->importer   = $importer;
	}

	/**
	 * Duplicate one menu, returning the import report (empty when there is nothing to copy).
	 *
	 * @param int $source_menu_id Menu term id to clone.
	 * @return array{menus:array<int,array<string,mixed>>,totals:array<string,int>}|array{}
	 */
	public function duplicate( int $source_menu_id ): array {
		$payload = $this->serializer->serialize( array( $source_menu_id ) );
		if ( empty( $payload['menus'] ) || ! is_array( $payload['menus'] ) ) {
			return array();
		}

		$payload['menus'][0]['name']           = $this->copy_name( (string) ( $payload['menus'][0]['name'] ?? '' ) );
		$payload['menus'][0]['locations']      = array();
		$payload['menus'][0]['auto_add_pages'] = false;

		return $this->importer->import( $payload, MenuImporter::MODE_CREATE );
	}

	/**
	 * Derive the copy's display name.
	 *
	 * @param string $name Source menu name.
	 * @return string
	 */
	private function copy_name( string $name ): string {
		/* translators: %s: original menu name. */
		return sprintf( _x( '%s (copy)', 'duplicated menu name', 'import-export-menu' ), $name );
	}
}
