<?php
/**
 * Single-choice segmented button group.
 *
 * @package Import_Export_Menu
 * @since   2.5.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Visually segmented one-of-N selector. Backed by hidden `<input type="radio">`
 * controls so the value survives a regular form submit without any JS.
 *
 * Required config:
 *   - choices (array<string,string|array{label:string,icon?:string}>) —
 *       value => label, or value => `[ 'label' => ..., 'icon' => 'dashicons-x' ]`
 *       to render a Dashicon alongside the label.
 *
 * @since 2.5.0
 */
class ButtonGroupField extends AbstractField {

	protected const TYPE = 'button_group';

	/**
	 * Render the segmented control.
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current stored value.
	 */
	public function render( string $name_attr, $value ): void {
		$choices = isset( $this->config['choices'] ) && is_array( $this->config['choices'] )
			? $this->config['choices']
			: array();
		$current = is_scalar( $value ) ? (string) $value : '';
		?>
		<div
			class="import-export-menu-button-group"
			role="radiogroup"
			aria-labelledby="<?php echo esc_attr( $this->id . '-label' ); ?>"
		>
			<?php foreach ( $choices as $choice_value => $choice_data ) : ?>
				<?php
				$choice_value = (string) $choice_value;
				$label        = is_array( $choice_data ) ? (string) ( $choice_data['label'] ?? '' ) : (string) $choice_data;
				$icon         = is_array( $choice_data ) && isset( $choice_data['icon'] ) ? (string) $choice_data['icon'] : '';
				$is_selected  = $choice_value === $current;
				?>
				<label class="import-export-menu-button-group__option<?php echo $is_selected ? ' is-selected' : ''; ?>">
					<input
						type="radio"
						name="<?php echo esc_attr( $name_attr ); ?>"
						value="<?php echo esc_attr( $choice_value ); ?>"
						<?php checked( true, $is_selected ); ?>
					/>
					<?php if ( '' !== $icon ) : ?>
						<span
							class="import-export-menu-button-group__icon dashicons <?php echo esc_attr( $icon ); ?>"
							aria-hidden="true"
						></span>
					<?php endif; ?>
					<span class="import-export-menu-button-group__label"><?php echo esc_html( $label ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
		$this->render_description();
	}

	/**
	 * Sanitize the choice, falling back to the default when unknown.
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
