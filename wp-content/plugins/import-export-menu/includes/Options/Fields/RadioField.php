<?php
/**
 * Single-choice radio field.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Group of `<input type="radio">` controls. Stores a single string.
 *
 * Config extras:
 *   - choices (array<string,string>) — value => label.
 *
 * @since 2.1.0
 */
class RadioField extends AbstractField {

	protected const TYPE = 'radio';

	/**
	 * Render the radio group.
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current stored value.
	 */
	public function render( string $name_attr, $value ): void {
		$choices = isset( $this->config['choices'] ) && is_array( $this->config['choices'] )
			? $this->config['choices']
			: array();
		$current = is_scalar( $value ) ? (string) $value : '';

		echo '<fieldset>';
		foreach ( $choices as $choice_value => $choice_label ) {
			$choice_value = (string) $choice_value;
			?>
			<label style="display:block;margin-bottom:8px;">
				<input
					type="radio"
					name="<?php echo esc_attr( $name_attr ); ?>"
					value="<?php echo esc_attr( $choice_value ); ?>"
					<?php checked( $choice_value, $current ); ?>
				/>
				<?php echo esc_html( (string) $choice_label ); ?>
			</label>
			<?php
		}
		echo '</fieldset>';
		$this->render_description();
	}

	/**
	 * Sanitize the choice, falling back to the default for unknown values.
	 *
	 * @param mixed $raw Raw POST value.
	 */
	public function sanitize( $raw ): string {
		if ( ! is_scalar( $raw ) ) {
			return is_scalar( $this->default ) ? (string) $this->default : '';
		}
		$value   = sanitize_text_field( (string) $raw );
		$choices = isset( $this->config['choices'] ) && is_array( $this->config['choices'] )
			? array_map( 'strval', array_keys( $this->config['choices'] ) )
			: null;

		if ( null !== $choices && ! in_array( $value, $choices, true ) ) {
			return is_scalar( $this->default ) ? (string) $this->default : '';
		}
		return $value;
	}
}
