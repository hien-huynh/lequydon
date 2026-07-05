<?php
/**
 * Maps a field type string to its concrete Field class.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options;

use ImportExportMenu\Options\Fields\AbstractField;
use ImportExportMenu\Options\Fields\ButtonGroupField;
use ImportExportMenu\Options\Fields\CheckboxField;
use ImportExportMenu\Options\Fields\CodeField;
use ImportExportMenu\Options\Fields\ColorField;
use ImportExportMenu\Options\Fields\DateField;
use ImportExportMenu\Options\Fields\ImageField;
use ImportExportMenu\Options\Fields\MediaField;
use ImportExportMenu\Options\Fields\NoticeField;
use ImportExportMenu\Options\Fields\NumberField;
use ImportExportMenu\Options\Fields\RadioField;
use ImportExportMenu\Options\Fields\RepeaterField;
use ImportExportMenu\Options\Fields\SelectField;
use ImportExportMenu\Options\Fields\SliderField;
use ImportExportMenu\Options\Fields\SpacingField;
use ImportExportMenu\Options\Fields\TextField;
use ImportExportMenu\Options\Fields\TextareaField;
use ImportExportMenu\Options\Fields\ToggleField;
use ImportExportMenu\Options\Fields\TypographyField;
use ImportExportMenu\Options\Fields\WysiwygField;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves a `'type' => '...'` config into an instantiated Field.
 *
 * Custom field types can be registered through the
 * `import_export_menu_options_field_types` filter, which receives a map of
 * `type-string => fully-qualified-class-name`. The class must extend
 * {@see AbstractField}.
 *
 * @since 2.1.0
 */
final class FieldFactory {

	/**
	 * Built-in type => class map shipped with the framework.
	 *
	 * @return array<string,class-string<AbstractField>>
	 */
	public static function default_map(): array {
		return array(
			'text'         => TextField::class,
			'textarea'     => TextareaField::class,
			'number'       => NumberField::class,
			'date'         => DateField::class,
			'toggle'       => ToggleField::class,
			'checkbox'     => CheckboxField::class,
			'radio'        => RadioField::class,
			'button_group' => ButtonGroupField::class,
			'select'       => SelectField::class,
			'color'        => ColorField::class,
			'slider'       => SliderField::class,
			'range'        => SliderField::class,
			'media'        => MediaField::class,
			'image'        => ImageField::class,
			'code'         => CodeField::class,
			'wysiwyg'      => WysiwygField::class,
			'repeater'     => RepeaterField::class,
			'typography'   => TypographyField::class,
			'spacing'      => SpacingField::class,
			'notice'       => NoticeField::class,
		);
	}

	/**
	 * Build a Field from a config array.
	 *
	 * @param array<string,mixed> $config Field config; must include `id` and `type`.
	 * @throws \InvalidArgumentException When required keys are missing or the type is unknown.
	 */
	public function make( array $config ): AbstractField {
		if ( empty( $config['id'] ) || ! is_string( $config['id'] ) ) {
			throw new \InvalidArgumentException( 'Field config requires a non-empty "id" string.' );
		}
		if ( empty( $config['type'] ) || ! is_string( $config['type'] ) ) {
			throw new \InvalidArgumentException( 'Field config requires a non-empty "type" string.' );
		}

		$map = apply_filters( 'import_export_menu_options_field_types', self::default_map() );

		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- exception messages are not browser output.
		if ( ! isset( $map[ $config['type'] ] ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Unknown field type "%s". Register it via the "import_export_menu_options_field_types" filter.', $config['type'] )
			);
		}

		$class = $map[ $config['type'] ];
		if ( ! is_subclass_of( $class, AbstractField::class ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Field class "%s" must extend %s.', $class, AbstractField::class )
			);
		}
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		return new $class( $config );
	}
}
