<?php
/**
 * Numeric range slider field.
 *
 * @package Import_Export_Menu
 * @since   2.2.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * `<input type="range">` control with a live-updating value display.
 *
 * Config extras (all optional):
 *   - min          (int|float)        — minimum value (default 0).
 *   - max          (int|float)        — maximum value (default 100).
 *   - step         (int|float|string) — step value (default 1).
 *   - unit         (string)           — short suffix appended to the value display, e.g. "%", "MB", "Hours".
 *   - left_label   (string)           — small label rendered under the left end of the track.
 *   - right_label  (string)           — small label rendered under the right end of the track.
 *
 * Sanitization mirrors {@see NumberField}: clamps to [min, max] and respects
 * float/int based on the configured `step`.
 *
 * @since 2.2.0
 */
class SliderField extends AbstractField {

	protected const TYPE = 'slider';

	/**
	 * Render the slider control with a value bubble and optional labels.
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current stored value.
	 */
	public function render( string $name_attr, $value ): void {
		$min     = $this->get_numeric_config( 'min', 0 );
		$max     = $this->get_numeric_config( 'max', 100 );
		$step    = isset( $this->config['step'] ) ? $this->config['step'] : 1;
		$unit    = isset( $this->config['unit'] ) ? (string) $this->config['unit'] : '';
		$current = is_numeric( $value ) ? $value : ( is_numeric( $this->default ) ? $this->default : $min );

		$left_label  = isset( $this->config['left_label'] ) ? (string) $this->config['left_label'] : '';
		$right_label = isset( $this->config['right_label'] ) ? (string) $this->config['right_label'] : '';
		$attrs       = $this->attributes_html(
			array(
				'min'  => $min,
				'max'  => $max,
				'step' => $step,
			)
		);

		?>
		<div class="import-export-menu-slider" data-unit="<?php echo esc_attr( $unit ); ?>">
			<div class="import-export-menu-slider__track-row">
				<input
					type="range"
					id="<?php echo esc_attr( $this->id ); ?>"
					name="<?php echo esc_attr( $name_attr ); ?>"
					value="<?php echo esc_attr( (string) $current ); ?>"
					class="import-export-menu-slider__input"
					<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes_html() escapes each piece. ?>
				/>
				<output class="import-export-menu-slider__value" for="<?php echo esc_attr( $this->id ); ?>">
					<?php echo esc_html( (string) $current . $unit ); ?>
				</output>
			</div>
			<?php if ( '' !== $left_label || '' !== $right_label ) : ?>
				<div class="import-export-menu-slider__labels">
					<span class="import-export-menu-slider__label-left"><?php echo esc_html( $left_label ); ?></span>
					<span class="import-export-menu-slider__label-right"><?php echo esc_html( $right_label ); ?></span>
				</div>
			<?php endif; ?>
		</div>
		<?php
		$this->render_description();
	}

	/**
	 * Sanitize the slider value, clamping to [min, max].
	 *
	 * @param mixed $raw Raw POST value.
	 * @return int|float
	 */
	public function sanitize( $raw ) {
		$min      = $this->get_numeric_config( 'min', 0 );
		$max      = $this->get_numeric_config( 'max', 100 );
		$is_float = $this->is_float_step();
		$default  = is_numeric( $this->default ) ? $this->default + 0 : $min;

		if ( null === $raw || '' === $raw || ! is_numeric( $raw ) ) {
			$value = $default;
		} else {
			$value = $is_float ? (float) $raw : (int) $raw;
		}

		if ( $value < $min ) {
			$value = $is_float ? (float) $min : (int) $min;
		}
		if ( $value > $max ) {
			$value = $is_float ? (float) $max : (int) $max;
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
		return is_numeric( $this->config['step'] )
			&& ( (float) (int) $this->config['step'] !== (float) $this->config['step'] );
	}

	/**
	 * Read a numeric config key, falling back when the key is missing or invalid.
	 *
	 * @param string    $key      Config key.
	 * @param int|float $fallback Fallback value.
	 * @return int|float
	 */
	private function get_numeric_config( string $key, $fallback ) {
		if ( isset( $this->config[ $key ] ) && is_numeric( $this->config[ $key ] ) ) {
			return $this->config[ $key ] + 0;
		}
		return $fallback;
	}
}
