<?php
/**
 * Native HTML5 date picker field.
 *
 * @package Import_Export_Menu
 * @since   2.5.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps `<input type="date">`. Values are persisted as `YYYY-MM-DD` strings;
 * anything that does not match that format is dropped during sanitization so
 * callers can trust the stored value.
 *
 * Config extras:
 *   - min (string) — earliest selectable date in `YYYY-MM-DD` format.
 *   - max (string) — latest selectable date in `YYYY-MM-DD` format.
 *
 * @since 2.5.0
 */
class DateField extends AbstractField {

	protected const TYPE = 'date';

	/**
	 * Render the date input.
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current stored value.
	 */
	public function render( string $name_attr, $value ): void {
		$extras = array();
		if ( isset( $this->config['min'] ) && $this->is_iso_date( (string) $this->config['min'] ) ) {
			$extras['min'] = (string) $this->config['min'];
		}
		if ( isset( $this->config['max'] ) && $this->is_iso_date( (string) $this->config['max'] ) ) {
			$extras['max'] = (string) $this->config['max'];
		}
		$attrs = $this->attributes_html( $extras );

		$current = is_scalar( $value ) ? (string) $value : '';
		if ( '' !== $current && ! $this->is_iso_date( $current ) ) {
			$current = '';
		}

		printf(
			'<input type="date" id="%1$s" name="%2$s" value="%3$s" class="import-export-menu-date" %4$s />',
			esc_attr( $this->id ),
			esc_attr( $name_attr ),
			esc_attr( $current ),
			$attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes_html() escapes each piece.
		);
		$this->render_description();
	}

	/**
	 * Sanitize the date to `YYYY-MM-DD` or empty string.
	 *
	 * @param mixed $raw Raw POST value.
	 */
	public function sanitize( $raw ): string {
		if ( ! is_scalar( $raw ) ) {
			return '';
		}
		$value = sanitize_text_field( (string) $raw );
		if ( '' === $value ) {
			return '';
		}
		return $this->is_iso_date( $value ) ? $value : '';
	}

	/**
	 * Whether a string is a valid `YYYY-MM-DD` calendar date.
	 *
	 * @param string $value Candidate value.
	 */
	private function is_iso_date( string $value ): bool {
		if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) ) {
			return false;
		}
		return (bool) checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
	}
}
