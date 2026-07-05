<?php
/**
 * Versioned contract for the menu export/import file format.
 *
 * @package Import_Export_Menu
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Identifies and versions the JSON payload the plugin reads and writes.
 *
 * The serializer stamps every export with {@see self::FORMAT} and
 * {@see self::VERSION}; the importer (Sprint 4) rejects anything that does not
 * carry a recognised format and refuses a schema version it cannot read. The
 * schema version is independent of the plugin version so the file format can
 * evolve on its own cadence.
 *
 * @since 1.1.0
 */
final class MenuSchema {

	/**
	 * Marker proving a file came from this plugin.
	 *
	 * Replaces the legacy plugin's free-text `plugin_name` check.
	 */
	public const FORMAT = 'import-export-menu';

	/**
	 * Current export schema version (independent of the plugin version).
	 *
	 * 1.1 adds the per-item `visibility` field. The change is additive and the
	 * importer gates on the major component only, so 1.0 and 1.1 files stay
	 * mutually readable.
	 */
	public const VERSION = '1.1';
}
