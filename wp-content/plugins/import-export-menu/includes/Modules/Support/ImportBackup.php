<?php
/**
 * Snapshots menus before an import so the last import can be undone.
 *
 * @package Import_Export_Menu
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Support;

defined( 'ABSPATH' ) || exit;

/**
 * A single-slot, full-state safety net for imports.
 *
 * Before an import runs, {@see self::capture()} serializes every existing menu
 * into a non-autoloaded option. {@see self::restore()} reverts the whole menu
 * state to that snapshot — deleting current menus and rebuilding the captured
 * ones — so a bad import is one click away from being undone. Only the most
 * recent snapshot is kept; richer version history is a Pro concern.
 *
 * @since 1.1.0
 */
final class ImportBackup {

	public const OPTION = 'import_export_menu_import_backup';

	/**
	 * Menu read layer.
	 *
	 * @var MenuRepository
	 */
	private $repository;

	/**
	 * Menu write layer.
	 *
	 * @var MenuWriter
	 */
	private $writer;

	/**
	 * Serializer used to snapshot the current menus.
	 *
	 * @var MenuSerializer
	 */
	private $serializer;

	/**
	 * Importer used to rebuild menus from a snapshot.
	 *
	 * @var MenuImporter
	 */
	private $importer;

	/**
	 * Wire the collaborators.
	 *
	 * @param MenuRepository $repository Menu read layer.
	 * @param MenuWriter     $writer     Menu write layer.
	 * @param MenuSerializer $serializer Snapshot serializer.
	 * @param MenuImporter   $importer   Snapshot importer.
	 */
	public function __construct( MenuRepository $repository, MenuWriter $writer, MenuSerializer $serializer, MenuImporter $importer ) {
		$this->repository = $repository;
		$this->writer     = $writer;
		$this->serializer = $serializer;
		$this->importer   = $importer;
	}

	/**
	 * Snapshot all current menus, replacing any previous snapshot.
	 */
	public function capture(): void {
		$payload = $this->serializer->serialize();
		update_option(
			self::OPTION,
			array(
				'captured_at' => gmdate( 'c' ),
				'menu_count'  => count( $payload['menus'] ),
				'payload'     => $payload,
			),
			false
		);
	}

	/**
	 * Whether a snapshot is available to restore.
	 *
	 * @return bool
	 */
	public function has(): bool {
		return is_array( get_option( self::OPTION ) );
	}

	/**
	 * Snapshot metadata for the UI, or null when none exists.
	 *
	 * @return array{captured_at:string,menu_count:int}|null
	 */
	public function info(): ?array {
		$stored = get_option( self::OPTION );
		if ( ! is_array( $stored ) ) {
			return null;
		}
		return array(
			'captured_at' => (string) ( $stored['captured_at'] ?? '' ),
			'menu_count'  => (int) ( $stored['menu_count'] ?? 0 ),
		);
	}

	/**
	 * Revert to the snapshot: delete current menus, rebuild the captured ones.
	 *
	 * @return array{menus:array<int,array<string,mixed>>,totals:array<string,int>}|array{} Import report, or empty when no snapshot.
	 */
	public function restore(): array {
		$stored = get_option( self::OPTION );
		if ( ! is_array( $stored ) || ! isset( $stored['payload'] ) || ! is_array( $stored['payload'] ) ) {
			return array();
		}

		foreach ( $this->repository->all_menus() as $menu ) {
			$this->writer->delete_menu( (int) $menu->term_id );
		}

		$report = $this->importer->import( $stored['payload'], MenuImporter::MODE_CREATE );
		$this->clear();

		return $report;
	}

	/**
	 * Discard the snapshot.
	 */
	public function clear(): void {
		delete_option( self::OPTION );
	}
}
