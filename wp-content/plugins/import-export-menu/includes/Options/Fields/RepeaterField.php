<?php
/**
 * Repeater field — array of grouped sub-fields.
 *
 * @package Import_Export_Menu
 * @since   2.4.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

use ImportExportMenu\Options\FieldFactory;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a list of "rows" where each row contains a fixed set of sub-fields,
 * with controls to add and remove rows on the client. Stored as a list of
 * dictionaries (`array<int, array<string, mixed>>`).
 *
 * Required config:
 *   - fields (array<int, array<string, mixed>>) — sub-field configs that
 *     would otherwise be passed to {@see Framework::add_field()}.
 *
 * Optional config:
 *   - row_label (string)        — label rendered on each row's header (default "Row").
 *   - add_label (string)        — text on the "Add" button (default "Add row").
 *   - min_rows  (int)           — minimum number of rows; remove buttons are
 *                                  hidden once the count drops to this value.
 *   - max_rows  (int)           — hard cap; the add button is hidden once reached.
 *
 * @since 2.4.0
 */
class RepeaterField extends AbstractField {

	protected const TYPE = 'repeater';

	/**
	 * Resolves sub-field configs to instances.
	 *
	 * @var FieldFactory
	 */
	private $factory;

	/**
	 * Sub-field instances, keyed by sub-field id and built lazily.
	 *
	 * @var array<string,AbstractField>
	 */
	private $sub_fields = array();

	/**
	 * Capture the repeater configuration and validate that sub-fields exist.
	 *
	 * @param array<string,mixed> $config Field configuration.
	 * @throws \InvalidArgumentException When the `fields` array is missing or empty.
	 */
	public function __construct( array $config ) {
		parent::__construct( $config );
		if ( empty( $config['fields'] ) || ! is_array( $config['fields'] ) ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- exception message, not output.
			throw new \InvalidArgumentException(
				sprintf( 'Repeater field "%s" requires a non-empty "fields" array.', $this->id )
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		$this->factory = new FieldFactory();
	}

	/**
	 * Default value coerced to a list of rows.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function default_value(): array {
		if ( ! is_array( $this->default ) ) {
			return array();
		}
		$rows = array();
		foreach ( $this->default as $row ) {
			if ( is_array( $row ) ) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Render the rows + add button + hidden template.
	 *
	 * @param string $name_attr HTML name prefix.
	 * @param mixed  $value     Current rows.
	 */
	public function render( string $name_attr, $value ): void {
		$rows      = is_array( $value ) ? array_values( $value ) : array();
		$row_label = isset( $this->config['row_label'] ) ? (string) $this->config['row_label'] : __( 'Row', 'import-export-menu' );
		$add_label = isset( $this->config['add_label'] ) ? (string) $this->config['add_label'] : __( 'Add row', 'import-export-menu' );
		$min_rows  = isset( $this->config['min_rows'] ) ? max( 0, (int) $this->config['min_rows'] ) : 0;
		$max_rows  = isset( $this->config['max_rows'] ) ? max( 0, (int) $this->config['max_rows'] ) : 0;
		?>
		<div
			class="import-export-menu-repeater"
			data-name-prefix="<?php echo esc_attr( $name_attr ); ?>"
			data-row-label="<?php echo esc_attr( $row_label ); ?>"
			data-min-rows="<?php echo esc_attr( (string) $min_rows ); ?>"
			data-max-rows="<?php echo esc_attr( (string) $max_rows ); ?>"
		>
			<div class="import-export-menu-repeater__rows">
				<?php foreach ( $rows as $index => $row ) : ?>
					<?php $this->render_row( (string) $index, is_array( $row ) ? $row : array(), $name_attr, $row_label ); ?>
				<?php endforeach; ?>
			</div>
			<template class="import-export-menu-repeater__template">
				<?php $this->render_row( '__INDEX__', array(), $name_attr, $row_label ); ?>
			</template>
			<button type="button" class="import-export-menu-repeater__add">
				<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
				<?php echo esc_html( $add_label ); ?>
			</button>
		</div>
		<?php
		$this->render_description();
	}

	/**
	 * Recursively sanitize each row by delegating to sub-field sanitizers.
	 *
	 * @param mixed $raw Raw POST value.
	 * @return array<int,array<string,mixed>>
	 */
	public function sanitize( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$min_rows = isset( $this->config['min_rows'] ) ? max( 0, (int) $this->config['min_rows'] ) : 0;
		$max_rows = isset( $this->config['max_rows'] ) ? max( 0, (int) $this->config['max_rows'] ) : 0;

		$clean = array();
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$clean_row = array();
			foreach ( $this->sub_fields() as $sub_id => $sub_field ) {
				$clean_row[ $sub_id ] = $sub_field->sanitize( $row[ $sub_id ] ?? null );
			}
			$clean[] = $clean_row;
			if ( $max_rows > 0 && count( $clean ) >= $max_rows ) {
				break;
			}
		}

		// Pad up to min_rows with default sub-field values so persisted state
		// always satisfies the schema.
		$missing = $min_rows - count( $clean );
		for ( $i = 0; $i < $missing; $i++ ) {
			$clean[] = $this->blank_row();
		}

		return $clean;
	}

	/**
	 * Render a single row's markup, including all its sub-fields.
	 *
	 * @param string              $index     Row index (`__INDEX__` for the template).
	 * @param array<string,mixed> $row       Stored values keyed by sub-field id.
	 * @param string              $prefix    Repeater HTML name prefix.
	 * @param string              $row_label Row header label.
	 */
	private function render_row( string $index, array $row, string $prefix, string $row_label ): void {
		?>
		<div class="import-export-menu-repeater__row" data-row-index="<?php echo esc_attr( $index ); ?>">
			<header class="import-export-menu-repeater__row-header">
				<button
					type="button"
					class="import-export-menu-repeater__drag"
					aria-label="<?php echo esc_attr__( 'Drag to reorder', 'import-export-menu' ); ?>"
				>
					<span class="dashicons dashicons-menu" aria-hidden="true"></span>
				</button>
				<span class="import-export-menu-repeater__row-label">
					<?php echo esc_html( $row_label ); ?>
					<span class="import-export-menu-repeater__row-number"></span>
				</span>
				<button type="button" class="import-export-menu-repeater__remove">
					<span class="dashicons dashicons-trash" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php echo esc_html__( 'Remove row', 'import-export-menu' ); ?></span>
				</button>
			</header>
			<div class="import-export-menu-repeater__row-body">
				<?php foreach ( $this->sub_fields() as $sub_id => $sub_field ) : ?>
					<?php
					$sub_name = $prefix . '[' . $index . '][' . $sub_id . ']';
					$sub_val  = array_key_exists( $sub_id, $row ) ? $row[ $sub_id ] : $sub_field->default_value();
					?>
					<div class="import-export-menu-repeater__field import-export-menu-field import-export-menu-field--<?php echo esc_attr( $sub_field->type() ); ?>">
						<div class="import-export-menu-field__label">
							<label><?php echo esc_html( $sub_field->label() ); ?></label>
						</div>
						<div class="import-export-menu-field__control">
							<?php $sub_field->render( $sub_name, $sub_val ); ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Lazily build sub-field instances from configs.
	 *
	 * @return array<string,AbstractField>
	 */
	private function sub_fields(): array {
		if ( ! empty( $this->sub_fields ) ) {
			return $this->sub_fields;
		}
		foreach ( $this->config['fields'] as $sub_config ) {
			if ( ! is_array( $sub_config ) ) {
				continue;
			}
			$sub_field                            = $this->factory->make( $sub_config );
			$this->sub_fields[ $sub_field->id() ] = $sub_field;
		}
		return $this->sub_fields;
	}

	/**
	 * Build a row pre-filled with each sub-field's default value.
	 *
	 * @return array<string,mixed>
	 */
	private function blank_row(): array {
		$row = array();
		foreach ( $this->sub_fields() as $sub_id => $sub_field ) {
			$row[ $sub_id ] = $sub_field->default_value();
		}
		return $row;
	}
}
