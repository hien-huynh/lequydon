<?php
/**
 * Numeric field with optional bounds.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * `<input type="number">` control. Returns int or float.
 *
 * Config extras:
 *   - min   (int|float)
 *   - max   (int|float)
 *   - step  (int|float|string) — pass `'any'` for unconstrained decimals.
 *   - width (string)           — width variant: `xs`, `sm`, `md`, `lg`, `xl`, `full`. Defaults to `md`.
 *
 * Values outside [min, max] are clamped during sanitization.
 *
 * @since 2.1.0
 */
class NumberField extends AbstractField {

	protected const TYPE = 'number';

	/**
	 * Render a numeric input with optional min/max/step.
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current stored value.
	 */
	public function render( string $name_attr, $value ): void {
		$extras = array();
		if ( isset( $this->config['min'] ) && is_numeric( $this->config['min'] ) ) {
			$extras['min'] = $this->config['min'];
		}
		if ( isset( $this->config['max'] ) && is_numeric( $this->config['max'] ) ) {
			$extras['max'] = $this->config['max'];
		}
		if ( isset( $this->config['step'] ) ) {
			$extras['step'] = $this->config['step'];
		}

		$attrs = $this->attributes_html( $extras );

		printf(
			'<input type="number" id="%1$s" name="%2$s" value="%3$s" class="import-export-menu-input import-export-menu-input--%4$s" %5$s />',
			esc_attr( $this->id ),
			esc_attr( $name_attr ),
			esc_attr( (string) ( null === $value || '' === $value ? '' : $value ) ),
			esc_attr( $this->width() ),
			$attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes_html() escapes each piece.
		);
		$this->render_description();
	}

	/**
	 * Sanitize numeric input, clamping to min/max when configured.
	 *
	 * @param mixed $raw Raw POST value.
	 * @return int|float
	 */
	public function sanitize( $raw ) {
		if ( null === $raw || '' === $raw ) {
			return is_numeric( $this->default ) ? $this->default + 0 : 0;
		}
		if ( ! is_numeric( $raw ) ) {
			return is_numeric( $this->default ) ? $this->default + 0 : 0;
		}

		$is_float = $this->is_float_step() || $this->numeric_has_decimal( (string) $raw );
		$value    = $is_float ? (float) $raw : (int) $raw;

		if ( isset( $this->config['min'] ) && is_numeric( $this->config['min'] ) && $value < $this->config['min'] ) {
			$value = $is_float ? (float) $this->config['min'] : (int) $this->config['min'];
		}
		if ( isset( $this->config['max'] ) && is_numeric( $this->config['max'] ) && $value > $this->config['max'] ) {
			$value = $is_float ? (float) $this->config['max'] : (int) $this->config['max'];
		}

		return $value;
	}

	/**
	 * Whether the configured step implies a float result.
	 */
	private function is_float_step(): bool {
		if ( ! isset( $this->config['step'] ) ) {
			return false;
		}
		if ( 'any' === $this->config['step'] ) {
			return true;
		}
		return is_numeric( $this->config['step'] ) && ( (float) (int) $this->config['step'] !== (float) $this->config['step'] );
	}

	/**
	 * Whether the raw numeric string contains a decimal separator.
	 *
	 * @param string $raw Raw value.
	 */
	private function numeric_has_decimal( string $raw ): bool {
		return false !== strpos( $raw, '.' );
	}
}
