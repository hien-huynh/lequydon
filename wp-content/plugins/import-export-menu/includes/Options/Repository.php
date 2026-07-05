<?php
/**
 * Reads/writes the single grouped option used by the options framework.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Thin wrapper around `get_option` / `update_option` that always merges
 * stored values on top of the defaults declared by the registry.
 *
 * Storage shape: a single autoloaded option named `import_export_menu_settings` whose
 * value is `array<string, mixed>` keyed by field id.
 *
 * @since 2.1.0
 */
final class Repository {

	public const OPTION_NAME = 'import_export_menu_settings';

	/**
	 * Field registry consulted for default values.
	 *
	 * @var Registry
	 */
	private $registry;

	/**
	 * Wire the registry the repository should resolve defaults against.
	 *
	 * @param Registry $registry Field registry.
	 */
	public function __construct( Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Returns the merged options array (defaults + persisted overrides).
	 *
	 * @return array<string,mixed>
	 */
	public function all(): array {
		$stored = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array_merge( $this->registry->defaults(), $stored );
	}

	/**
	 * Read a single field's value, falling back to its registered default.
	 *
	 * @param string $field_id Field identifier.
	 * @param mixed  $fallback Override fallback when the field is unknown.
	 * @return mixed
	 */
	public function get( string $field_id, $fallback = null ) {
		$all = $this->all();
		if ( array_key_exists( $field_id, $all ) ) {
			return $all[ $field_id ];
		}
		return $fallback;
	}

	/**
	 * Replace the entire stored array.
	 *
	 * @param array<string,mixed> $values Pre-sanitized values.
	 */
	public function replace( array $values ): bool {
		return (bool) update_option( self::OPTION_NAME, $values );
	}

	/**
	 * Update one field, persisting the rest unchanged.
	 *
	 * @param string $field_id Field identifier.
	 * @param mixed  $value    Pre-sanitized value.
	 */
	public function set( string $field_id, $value ): bool {
		$all              = $this->all();
		$all[ $field_id ] = $value;
		return $this->replace( $all );
	}

	/**
	 * Delete the stored option entirely (defaults will resurface on read).
	 */
	public function delete(): bool {
		return (bool) delete_option( self::OPTION_NAME );
	}
}
