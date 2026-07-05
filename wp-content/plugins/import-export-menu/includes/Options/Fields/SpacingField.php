<?php
/**
 * Box-spacing field — top / right / bottom / left numeric inputs with optional
 * uniform mode that locks all four sides to the same value.
 *
 * @package Import_Export_Menu
 * @since   2.4.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Stores `array{top:float, right:float, bottom:float, left:float, unit:string, linked:bool}`.
 *
 * Optional config:
 *   - units      (array<int,string>) — allowed unit suffixes. Default `[ 'px', 'rem', 'em', '%' ]`.
 *   - min        (int|float)         — minimum value per side. Default 0.
 *   - max        (int|float)         — maximum value per side. Default 999.
 *   - step       (int|float)         — step for the number inputs. Default 1.
 *   - allow_link (bool)              — whether to render the link/unlink toggle. Default true.
 *
 * @since 2.4.0
 */
class SpacingField extends AbstractField {

	protected const TYPE = 'spacing';

	private const DEFAULT_UNITS = array( 'px', 'rem', 'em', '%' );
	private const SIDES         = array( 'top', 'right', 'bottom', 'left' );

	/**
	 * Default composite value.
	 *
	 * @return array<string,mixed>
	 */
	public function default_value(): array {
		$base = array(
			'top'    => 0,
			'right'  => 0,
			'bottom' => 0,
			'left'   => 0,
			'unit'   => 'px',
			'linked' => false,
		);
		if ( is_array( $this->default ) ) {
			$base = array_merge( $base, $this->default );
		}
		return $this->coerce( $base );
	}

	/**
	 * Render the four side inputs + unit + link toggle.
	 *
	 * @param string $name_attr HTML name prefix.
	 * @param mixed  $value     Current composite value.
	 */
	public function render( string $name_attr, $value ): void {
		$current    = $this->coerce( is_array( $value ) ? array_merge( $this->default_value(), $value ) : $this->default_value() );
		$units      = isset( $this->config['units'] ) && is_array( $this->config['units'] )
			? array_values( array_map( 'strval', $this->config['units'] ) )
			: self::DEFAULT_UNITS;
		$min        = isset( $this->config['min'] ) && is_numeric( $this->config['min'] ) ? $this->config['min'] : 0;
		$max        = isset( $this->config['max'] ) && is_numeric( $this->config['max'] ) ? $this->config['max'] : 999;
		$step       = isset( $this->config['step'] ) && is_numeric( $this->config['step'] ) ? $this->config['step'] : 1;
		$allow_link = isset( $this->config['allow_link'] ) ? (bool) $this->config['allow_link'] : true;
		?>
		<div
			class="import-export-menu-spacing<?php echo $current['linked'] ? ' is-linked' : ''; ?>"
			data-allow-link="<?php echo esc_attr( $allow_link ? '1' : '0' ); ?>"
		>
			<div class="import-export-menu-spacing__sides">
				<?php foreach ( self::SIDES as $side ) : ?>
					<label class="import-export-menu-spacing__side">
						<span class="import-export-menu-spacing__side-label"><?php echo esc_html( $this->side_label( $side ) ); ?></span>
						<input
							type="number"
							name="<?php echo esc_attr( $name_attr . '[' . $side . ']' ); ?>"
							value="<?php echo esc_attr( (string) $current[ $side ] ); ?>"
							min="<?php echo esc_attr( (string) $min ); ?>"
							max="<?php echo esc_attr( (string) $max ); ?>"
							step="<?php echo esc_attr( (string) $step ); ?>"
							class="import-export-menu-spacing__input"
							data-side="<?php echo esc_attr( $side ); ?>"
						/>
					</label>
				<?php endforeach; ?>
			</div>
			<div class="import-export-menu-spacing__controls">
				<label class="import-export-menu-spacing__unit-wrap">
					<span class="screen-reader-text"><?php echo esc_html__( 'Unit', 'import-export-menu' ); ?></span>
					<select name="<?php echo esc_attr( $name_attr . '[unit]' ); ?>" class="import-export-menu-spacing__unit">
						<?php foreach ( $units as $unit_value ) : ?>
							<option
								value="<?php echo esc_attr( $unit_value ); ?>"
								<?php selected( $unit_value, $current['unit'] ); ?>
							><?php echo esc_html( $unit_value ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<?php if ( $allow_link ) : ?>
					<label class="import-export-menu-spacing__link">
						<input type="hidden" name="<?php echo esc_attr( $name_attr . '[linked]' ); ?>" value="0" />
						<input
							type="checkbox"
							name="<?php echo esc_attr( $name_attr . '[linked]' ); ?>"
							value="1"
							<?php checked( true, $current['linked'] ); ?>
						/>
						<span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
						<span class="screen-reader-text"><?php echo esc_html__( 'Link sides', 'import-export-menu' ); ?></span>
					</label>
				<?php endif; ?>
			</div>
		</div>
		<?php
		$this->render_description();
	}

	/**
	 * Sanitize the submitted composite, clamping numerics and validating unit.
	 *
	 * @param mixed $raw Raw POST value.
	 * @return array<string,mixed>
	 */
	public function sanitize( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return $this->default_value();
		}
		$units = isset( $this->config['units'] ) && is_array( $this->config['units'] )
			? array_values( array_map( 'strval', $this->config['units'] ) )
			: self::DEFAULT_UNITS;

		$merged = array_merge( $this->default_value(), $raw );
		if ( ! in_array( (string) $merged['unit'], $units, true ) ) {
			$merged['unit'] = $units[0];
		}
		return $this->coerce( $merged );
	}

	/**
	 * Coerce values into their canonical types and clamp to min/max bounds.
	 *
	 * @param array<string,mixed> $raw Loosely-typed values.
	 * @return array<string,mixed>
	 */
	private function coerce( array $raw ): array {
		$min = isset( $this->config['min'] ) && is_numeric( $this->config['min'] ) ? (float) $this->config['min'] : 0.0;
		$max = isset( $this->config['max'] ) && is_numeric( $this->config['max'] ) ? (float) $this->config['max'] : 999.0;
		$out = array();
		foreach ( self::SIDES as $side ) {
			$value        = isset( $raw[ $side ] ) && is_numeric( $raw[ $side ] ) ? (float) $raw[ $side ] : 0.0;
			$value        = max( $min, min( $max, $value ) );
			$out[ $side ] = ( (float) (int) $value === $value ) ? (int) $value : $value;
		}
		$out['unit'] = isset( $raw['unit'] ) && is_scalar( $raw['unit'] ) ? sanitize_text_field( (string) $raw['unit'] ) : 'px';
		// `! empty()` already filters out '0', '', 0, false, and null in PHP,
		// so any value reaching here represents an explicit "linked" state.
		$out['linked'] = ! empty( $raw['linked'] );
		return $out;
	}

	/**
	 * Localised label for a given side.
	 *
	 * @param string $side Side identifier.
	 */
	private function side_label( string $side ): string {
		switch ( $side ) {
			case 'top':
				return __( 'Top', 'import-export-menu' );
			case 'right':
				return __( 'Right', 'import-export-menu' );
			case 'bottom':
				return __( 'Bottom', 'import-export-menu' );
			case 'left':
				return __( 'Left', 'import-export-menu' );
			default:
				return ucfirst( $side );
		}
	}
}
