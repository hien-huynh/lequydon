<?php
/**
 * Select / multi-select dropdown.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * `<select>` control. Pass `'multiple' => true` for multi-select.
 *
 * Config extras:
 *   - choices  (array<string,string>) — value => label.
 *   - multiple (bool)                  — multi-select mode.
 *
 * @since 2.1.0
 */
class SelectField extends AbstractField {

	protected const TYPE = 'select';

	/**
	 * Whether this select is configured as a multi-select.
	 */
	private function is_multiple(): bool {
		return ! empty( $this->config['multiple'] );
	}

	/**
	 * Returns the default value coerced to the right shape (string or list).
	 *
	 * @return mixed
	 */
	public function default_value() {
		if ( $this->is_multiple() ) {
			return is_array( $this->default ) ? array_values( array_map( 'strval', $this->default ) ) : array();
		}
		return is_scalar( $this->default ) ? (string) $this->default : '';
	}

	/**
	 * Render the select element.
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current stored value.
	 */
	public function render( string $name_attr, $value ): void {
		$choices  = isset( $this->config['choices'] ) && is_array( $this->config['choices'] )
			? $this->config['choices']
			: array();
		$multiple = $this->is_multiple();

		if ( $multiple ) {
			$current = is_array( $value ) ? array_map( 'strval', $value ) : array();
			$attrs   = $this->attributes_html( array( 'multiple' => true ) );
			$name    = $name_attr . '[]';
		} else {
			$current = is_scalar( $value ) ? (string) $value : '';
			$attrs   = $this->attributes_html();
			$name    = $name_attr;
		}

		printf(
			'<select id="%1$s" name="%2$s" %3$s>',
			esc_attr( $this->id ),
			esc_attr( $name ),
			$attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes_html() escapes each piece.
		);
		foreach ( $choices as $choice_value => $choice_label ) {
			$choice_value = (string) $choice_value;
			$is_selected  = $multiple
				? in_array( $choice_value, $current, true )
				: $choice_value === $current;
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $choice_value ),
				$is_selected ? ' selected' : '',
				esc_html( (string) $choice_label )
			);
		}
		echo '</select>';
		$this->render_description();
	}

	/**
	 * Sanitize the selection, dropping unknown choices.
	 *
	 * @param mixed $raw Raw POST value.
	 * @return string|array<int,string>
	 */
	public function sanitize( $raw ) {
		$choices = isset( $this->config['choices'] ) && is_array( $this->config['choices'] )
			? array_map( 'strval', array_keys( $this->config['choices'] ) )
			: null;

		if ( $this->is_multiple() ) {
			if ( ! is_array( $raw ) ) {
				return array();
			}
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

		if ( ! is_scalar( $raw ) ) {
			return is_scalar( $this->default ) ? (string) $this->default : '';
		}
		$value = sanitize_text_field( (string) $raw );
		if ( null !== $choices && ! in_array( $value, $choices, true ) ) {
			return is_scalar( $this->default ) ? (string) $this->default : '';
		}
		return $value;
	}
}
