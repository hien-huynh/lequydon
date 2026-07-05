<?php
/**
 * Single-line text field.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Plain `<input type="text">` control.
 *
 * Config extras:
 *   - input_type (string) — e.g. `email`, `url`, `password`. Defaults to `text`.
 *   - width      (string) — width variant: `xs`, `sm`, `md`, `lg`, `xl`, `full`. Defaults to `md`.
 *
 * @since 2.1.0
 */
class TextField extends AbstractField {

	protected const TYPE = 'text';

	/**
	 * Render a single-line text input.
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current stored value.
	 */
	public function render( string $name_attr, $value ): void {
		$input_type = isset( $this->config['input_type'] ) ? (string) $this->config['input_type'] : 'text';
		$attrs      = $this->attributes_html();

		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="import-export-menu-input import-export-menu-input--%5$s" %6$s />',
			esc_attr( $input_type ),
			esc_attr( $this->id ),
			esc_attr( $name_attr ),
			esc_attr( (string) ( null === $value ? '' : $value ) ),
			esc_attr( $this->width() ),
			$attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes_html() escapes each piece.
		);
		$this->render_description();
	}

	/**
	 * Sanitize the raw input according to the configured `input_type`.
	 *
	 * @param mixed $raw Raw POST value.
	 * @return string
	 */
	public function sanitize( $raw ) {
		if ( null === $raw ) {
			return '';
		}
		$input_type = isset( $this->config['input_type'] ) ? (string) $this->config['input_type'] : 'text';
		$raw        = is_scalar( $raw ) ? (string) $raw : '';

		switch ( $input_type ) {
			case 'email':
				$clean = sanitize_email( $raw );
				return is_email( $clean ) ? $clean : '';
			case 'url':
				return esc_url_raw( $raw );
			case 'password':
				// Preserve raw-ish, but strip tags; never trim, passwords may use whitespace intentionally.
				return wp_strip_all_tags( $raw );
			default:
				return sanitize_text_field( $raw );
		}
	}
}
