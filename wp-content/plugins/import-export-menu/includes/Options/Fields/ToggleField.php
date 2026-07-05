<?php
/**
 * Switch-style boolean field.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a styled checkbox that visually behaves like an iOS-style toggle.
 *
 * The actual control is a real `<input type="checkbox">` for accessibility
 * and progressive enhancement; the look is provided by SCSS.
 *
 * @since 2.1.0
 */
class ToggleField extends AbstractField {

	protected const TYPE = 'toggle';

	/**
	 * Returns the default value coerced to a strict boolean.
	 *
	 * @return bool
	 */
	public function default_value() {
		return (bool) $this->default;
	}

	/**
	 * Render the toggle (real checkbox + a hidden "0" so unchecked values still post).
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current stored value.
	 */
	public function render( string $name_attr, $value ): void {
		$checked   = $this->to_bool( $value ) ? 'checked' : '';
		$on_label  = isset( $this->config['on_label'] ) ? (string) $this->config['on_label'] : __( 'On', 'import-export-menu' );
		$off_label = isset( $this->config['off_label'] ) ? (string) $this->config['off_label'] : __( 'Off', 'import-export-menu' );
		$attrs     = $this->attributes_html();
		?>
		<label class="import-export-menu-toggle">
			<input type="hidden" name="<?php echo esc_attr( $name_attr ); ?>" value="0" />
			<input
				type="checkbox"
				id="<?php echo esc_attr( $this->id ); ?>"
				name="<?php echo esc_attr( $name_attr ); ?>"
				value="1"
				<?php echo esc_attr( $checked ); ?>
				<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes_html() escapes each piece. ?>
			/>
			<span class="import-export-menu-toggle__track" aria-hidden="true"></span>
			<span class="import-export-menu-toggle__label">
				<span class="import-export-menu-toggle__on"><?php echo esc_html( $on_label ); ?></span>
				<span class="import-export-menu-toggle__off"><?php echo esc_html( $off_label ); ?></span>
			</span>
		</label>
		<?php
		$this->render_description();
	}

	/**
	 * Sanitize input to a strict boolean.
	 *
	 * @param mixed $raw Raw POST value.
	 */
	public function sanitize( $raw ): bool {
		return $this->to_bool( $raw );
	}

	/**
	 * Coerce assorted truthy representations to a strict boolean.
	 *
	 * @param mixed $raw Raw value.
	 */
	private function to_bool( $raw ): bool {
		if ( is_bool( $raw ) ) {
			return $raw;
		}
		if ( is_numeric( $raw ) ) {
			return 1 === (int) $raw;
		}
		if ( is_string( $raw ) ) {
			return in_array( strtolower( $raw ), array( '1', 'true', 'on', 'yes' ), true );
		}
		return false;
	}
}
