<?php
/**
 * Color picker field (hex value).
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a `<input type="text" class="import-export-menu-color-picker">`. The
 * matching JS upgrades it to the WordPress `wp-color-picker` widget when
 * available.
 *
 * Sanitized via {@see sanitize_hex_color()}; falls back to the default when
 * the input is not a valid hex color.
 *
 * @since 2.1.0
 */
class ColorField extends AbstractField {

	protected const TYPE = 'color';

	/**
	 * Render the color picker input.
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current stored value.
	 */
	public function render( string $name_attr, $value ): void {
		$current = is_scalar( $value ) ? (string) $value : '';
		$attrs   = $this->attributes_html(
			array(
				'data-default-color' => is_scalar( $this->default ) ? (string) $this->default : '',
			)
		);

		printf(
			'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="import-export-menu-color-picker" %4$s />',
			esc_attr( $this->id ),
			esc_attr( $name_attr ),
			esc_attr( $current ),
			$attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes_html() escapes each piece.
		);
		$this->render_description();
	}

	/**
	 * Sanitize the hex color, falling back to the default for invalid input.
	 *
	 * @param mixed $raw Raw POST value.
	 */
	public function sanitize( $raw ): string {
		if ( null === $raw || '' === $raw ) {
			return '';
		}
		if ( ! is_scalar( $raw ) ) {
			return is_scalar( $this->default ) ? (string) $this->default : '';
		}
		$clean = sanitize_hex_color( (string) $raw );
		if ( null === $clean || '' === $clean ) {
			return is_scalar( $this->default ) ? (string) $this->default : '';
		}
		return $clean;
	}
}
