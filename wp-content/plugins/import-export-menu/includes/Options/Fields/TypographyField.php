<?php
/**
 * Typography composite control (family + size + weight + line-height).
 *
 * @package Import_Export_Menu
 * @since   2.4.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Stores `array{family:string, size:int, weight:string, line_height:float, unit:string}`.
 *
 * Optional config:
 *   - families (array<string,string>) — value => label map for the font select.
 *   - weights  (array<string,string>) — value => label map for the weight select.
 *   - units    (array<int,string>)    — list of unit strings; defaults to `[ 'px', 'rem', 'em' ]`.
 *   - default  (array)                — initial values; missing keys fall back to internal defaults.
 *
 * @since 2.4.0
 */
class TypographyField extends AbstractField {

	protected const TYPE = 'typography';

	private const DEFAULT_FAMILIES = array(
		''            => 'System default',
		'Inter'       => 'Inter',
		'Roboto'      => 'Roboto',
		'Open Sans'   => 'Open Sans',
		'Source Sans' => 'Source Sans 3',
		'Georgia'     => 'Georgia',
		'monospace'   => 'Monospace',
	);

	private const DEFAULT_WEIGHTS = array(
		'300' => 'Light (300)',
		'400' => 'Regular (400)',
		'500' => 'Medium (500)',
		'600' => 'Semi Bold (600)',
		'700' => 'Bold (700)',
	);

	private const DEFAULT_UNITS = array( 'px', 'rem', 'em' );

	/**
	 * Default composite value.
	 *
	 * @return array{family:string,size:float,weight:string,unit:string,line_height:float}
	 */
	public function default_value(): array {
		$base = array(
			'family'      => '',
			'size'        => 16,
			'weight'      => '400',
			'unit'        => 'px',
			'line_height' => 1.5,
		);
		if ( is_array( $this->default ) ) {
			$base = array_merge( $base, $this->default );
		}
		return $this->coerce( $base );
	}

	/**
	 * Render the four sub-controls inline.
	 *
	 * @param string $name_attr HTML name prefix.
	 * @param mixed  $value     Current composite value.
	 */
	public function render( string $name_attr, $value ): void {
		$current  = $this->coerce( is_array( $value ) ? array_merge( $this->default_value(), $value ) : $this->default_value() );
		$families = isset( $this->config['families'] ) && is_array( $this->config['families'] )
			? $this->config['families']
			: self::DEFAULT_FAMILIES;
		$weights  = isset( $this->config['weights'] ) && is_array( $this->config['weights'] )
			? $this->config['weights']
			: self::DEFAULT_WEIGHTS;
		$units    = isset( $this->config['units'] ) && is_array( $this->config['units'] )
			? array_values( array_map( 'strval', $this->config['units'] ) )
			: self::DEFAULT_UNITS;
		?>
		<div class="import-export-menu-typography">
			<label class="import-export-menu-typography__group">
				<span class="import-export-menu-typography__label"><?php echo esc_html__( 'Family', 'import-export-menu' ); ?></span>
				<select name="<?php echo esc_attr( $name_attr . '[family]' ); ?>">
					<?php foreach ( $families as $family_value => $family_label ) : ?>
						<option
							value="<?php echo esc_attr( (string) $family_value ); ?>"
							<?php selected( (string) $family_value, $current['family'] ); ?>
						><?php echo esc_html( (string) $family_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="import-export-menu-typography__group">
				<span class="import-export-menu-typography__label"><?php echo esc_html__( 'Weight', 'import-export-menu' ); ?></span>
				<select name="<?php echo esc_attr( $name_attr . '[weight]' ); ?>">
					<?php foreach ( $weights as $weight_value => $weight_label ) : ?>
						<option
							value="<?php echo esc_attr( (string) $weight_value ); ?>"
							<?php selected( (string) $weight_value, $current['weight'] ); ?>
						><?php echo esc_html( (string) $weight_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="import-export-menu-typography__group import-export-menu-typography__group--size">
				<span class="import-export-menu-typography__label"><?php echo esc_html__( 'Size', 'import-export-menu' ); ?></span>
				<span class="import-export-menu-typography__size">
					<input
						type="number"
						name="<?php echo esc_attr( $name_attr . '[size]' ); ?>"
						value="<?php echo esc_attr( (string) $current['size'] ); ?>"
						min="0"
						step="0.5"
					/>
					<select name="<?php echo esc_attr( $name_attr . '[unit]' ); ?>">
						<?php foreach ( $units as $unit_value ) : ?>
							<option
								value="<?php echo esc_attr( $unit_value ); ?>"
								<?php selected( $unit_value, $current['unit'] ); ?>
							><?php echo esc_html( $unit_value ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
			</label>
			<label class="import-export-menu-typography__group">
				<span class="import-export-menu-typography__label"><?php echo esc_html__( 'Line height', 'import-export-menu' ); ?></span>
				<input
					type="number"
					name="<?php echo esc_attr( $name_attr . '[line_height]' ); ?>"
					value="<?php echo esc_attr( (string) $current['line_height'] ); ?>"
					min="0"
					step="0.05"
				/>
			</label>
		</div>
		<?php
		$this->render_description();
	}

	/**
	 * Sanitize the submitted composite, clamping numerics and validating choices.
	 *
	 * @param mixed $raw Raw POST value.
	 * @return array<string,mixed>
	 */
	public function sanitize( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return $this->default_value();
		}
		$merged = array_merge( $this->default_value(), $raw );

		$weights = isset( $this->config['weights'] ) && is_array( $this->config['weights'] )
			? array_map( 'strval', array_keys( $this->config['weights'] ) )
			: array_keys( self::DEFAULT_WEIGHTS );
		$units   = isset( $this->config['units'] ) && is_array( $this->config['units'] )
			? array_values( array_map( 'strval', $this->config['units'] ) )
			: self::DEFAULT_UNITS;

		if ( ! in_array( (string) $merged['weight'], $weights, true ) ) {
			$merged['weight'] = '400';
		}
		if ( ! in_array( (string) $merged['unit'], $units, true ) ) {
			$merged['unit'] = $units[0];
		}
		return $this->coerce( $merged );
	}

	/**
	 * Coerce values into their canonical types (and clamp to safe ranges).
	 *
	 * @param array<string,mixed> $raw Loosely-typed values.
	 * @return array{family:string,size:float,weight:string,unit:string,line_height:float}
	 */
	private function coerce( array $raw ): array {
		$family = isset( $raw['family'] ) && is_scalar( $raw['family'] )
			? sanitize_text_field( (string) $raw['family'] )
			: '';
		$size   = isset( $raw['size'] ) && is_numeric( $raw['size'] )
			? max( 0, (float) $raw['size'] )
			: 16.0;
		// Drop the trailing `.0` for integer-looking sizes so the input shows "16" not "16.0".
		if ( (float) (int) $size === $size ) {
			$size = (int) $size;
		}
		$weight      = isset( $raw['weight'] ) && is_scalar( $raw['weight'] )
			? sanitize_text_field( (string) $raw['weight'] )
			: '400';
		$unit        = isset( $raw['unit'] ) && is_scalar( $raw['unit'] )
			? sanitize_text_field( (string) $raw['unit'] )
			: 'px';
		$line_height = isset( $raw['line_height'] ) && is_numeric( $raw['line_height'] )
			? max( 0, (float) $raw['line_height'] )
			: 1.5;
		return array(
			'family'      => $family,
			'size'        => $size,
			'weight'      => $weight,
			'unit'        => $unit,
			'line_height' => $line_height,
		);
	}
}
