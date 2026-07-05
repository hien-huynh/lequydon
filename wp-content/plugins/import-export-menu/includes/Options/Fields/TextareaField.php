<?php
/**
 * Multi-line textarea field.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Plain `<textarea>` control.
 *
 * Config extras:
 *   - rows (int) — defaults to 5.
 *   - cols (int) — defaults to 50.
 *   - allow_html (bool) — when true, runs `wp_kses_post` instead of `sanitize_textarea_field`.
 *
 * @since 2.1.0
 */
class TextareaField extends AbstractField {

	protected const TYPE = 'textarea';

	/**
	 * Render a multi-line textarea input.
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current stored value.
	 */
	public function render( string $name_attr, $value ): void {
		$rows  = isset( $this->config['rows'] ) ? (int) $this->config['rows'] : 5;
		$cols  = isset( $this->config['cols'] ) ? (int) $this->config['cols'] : 50;
		$attrs = $this->attributes_html();

		printf(
			'<textarea id="%1$s" name="%2$s" rows="%3$s" cols="%4$s" class="large-text code" %5$s>%6$s</textarea>',
			esc_attr( $this->id ),
			esc_attr( $name_attr ),
			esc_attr( (string) $rows ),
			esc_attr( (string) $cols ),
			$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes_html() escapes each piece.
			esc_textarea( (string) ( null === $value ? '' : $value ) )
		);
		$this->render_description();
	}

	/**
	 * Sanitize textarea input. Falls back to wp_kses_post when `allow_html` is set.
	 *
	 * @param mixed $raw Raw POST value.
	 * @return string
	 */
	public function sanitize( $raw ) {
		if ( null === $raw ) {
			return '';
		}
		$raw = is_scalar( $raw ) ? (string) $raw : '';
		if ( ! empty( $this->config['allow_html'] ) ) {
			return wp_kses_post( $raw );
		}
		return sanitize_textarea_field( $raw );
	}
}
