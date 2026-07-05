<?php
/**
 * Multi-checkbox field.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Group of `<input type="checkbox">` controls. Stores `array<int,string>`.
 *
 * Config extras:
 *   - choices (array<string,string>) — value => label.
 *
 * @since 2.1.0
 */
class CheckboxField extends AbstractField {

	protected const TYPE = 'checkbox';

	/**
	 * Returns the default value coerced to a list of strings.
	 *
	 * @return array<int,string>
	 */
	public function default_value(): array {
		if ( is_array( $this->default ) ) {
			return array_values( array_map( 'strval', $this->default ) );
		}
		return array();
	}

	/**
	 * Render the checkbox group.
	 *
	 * @param string $name_attr HTML name attribute (without the trailing `[]`).
	 * @param mixed  $value     Current stored value.
	 */
	public function render( string $name_attr, $value ): void {
		$choices  = isset( $this->config['choices'] ) && is_array( $this->config['choices'] )
			? $this->config['choices']
			: array();
		$selected = is_array( $value ) ? array_map( 'strval', $value ) : array();

		echo '<fieldset>';
		foreach ( $choices as $choice_value => $choice_label ) {
			$choice_value = (string) $choice_value;
			$is_checked   = in_array( $choice_value, $selected, true );
			?>
			<label style="display:block;margin-bottom:8px;">
				<input
					type="checkbox"
					name="<?php echo esc_attr( $name_attr ); ?>[]"
					value="<?php echo esc_attr( $choice_value ); ?>"
					<?php checked( $is_checked ); ?>
				/>
				<?php echo esc_html( (string) $choice_label ); ?>
			</label>
			<?php
		}
		echo '</fieldset>';
		$this->render_description();
	}

	/**
	 * Sanitize the submitted choices, dropping anything outside `choices`.
	 *
	 * @param mixed $raw Raw POST value.
	 * @return array<int,string>
	 */
	public function sanitize( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$choices = isset( $this->config['choices'] ) && is_array( $this->config['choices'] )
			? array_map( 'strval', array_keys( $this->config['choices'] ) )
			: null;

		$clean = array();
		foreach ( $raw as $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$value = sanitize_text_field( (string) $value );
			if ( null === $choices || in_array( $value, $choices, true ) ) {
				$clean[] = $value;
			}
		}
		return array_values( array_unique( $clean ) );
	}
}
