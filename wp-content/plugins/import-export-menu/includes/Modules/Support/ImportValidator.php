<?php
/**
 * Validates a decoded export payload before it is imported.
 *
 * @package Import_Export_Menu
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Guards the importer against malformed, foreign, or incompatible files.
 *
 * Returns a stable error code (not a translated string) so callers and tests
 * stay locale-independent; {@see self::message()} maps a code to user copy.
 *
 * @since 1.1.0
 */
final class ImportValidator {

	public const OK             = '';
	public const ERR_EMPTY      = 'empty';
	public const ERR_MALFORMED  = 'malformed';
	public const ERR_NOT_OBJECT = 'not_object';
	public const ERR_FORMAT     = 'bad_format';
	public const ERR_VERSION    = 'bad_version';
	public const ERR_NO_MENUS   = 'no_menus';

	/**
	 * Validate a decoded payload.
	 *
	 * @param mixed $payload Decoded JSON (any type).
	 * @return string {@see self::OK} when valid, otherwise an `ERR_*` code.
	 */
	public function validate( $payload ): string {
		if ( ! is_array( $payload ) ) {
			return self::ERR_NOT_OBJECT;
		}
		if ( ! isset( $payload['format'] ) || MenuSchema::FORMAT !== $payload['format'] ) {
			return self::ERR_FORMAT;
		}
		$version = isset( $payload['schema_version'] ) ? (string) $payload['schema_version'] : '';
		if ( '' === $version || $this->major( $version ) !== $this->major( MenuSchema::VERSION ) ) {
			return self::ERR_VERSION;
		}
		if ( empty( $payload['menus'] ) || ! is_array( $payload['menus'] ) ) {
			return self::ERR_NO_MENUS;
		}
		return self::OK;
	}

	/**
	 * Human-readable message for a validation code.
	 *
	 * @param string $code An `ERR_*` code.
	 * @return string
	 */
	public static function message( string $code ): string {
		switch ( $code ) {
			case self::ERR_EMPTY:
				return __( 'Choose an export file first.', 'import-export-menu' );
			case self::ERR_MALFORMED:
				return __( 'The file could not be read. Make sure it is a .json export from this plugin.', 'import-export-menu' );
			case self::ERR_FORMAT:
				return __( 'This file was not produced by Import Export Menu.', 'import-export-menu' );
			case self::ERR_VERSION:
				return __( 'This export was made with an incompatible version and cannot be imported.', 'import-export-menu' );
			case self::ERR_NO_MENUS:
				return __( 'The export does not contain any menus.', 'import-export-menu' );
			case self::ERR_NOT_OBJECT:
			default:
				return __( 'The file is not a valid menu export. Upload a JSON file exported by this plugin.', 'import-export-menu' );
		}
	}

	/**
	 * Major component of a dotted version string.
	 *
	 * @param string $version Version like `1.0`.
	 * @return string
	 */
	private function major( string $version ): string {
		$parts = explode( '.', $version );
		return $parts[0];
	}
}
