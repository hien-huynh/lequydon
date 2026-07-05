<?php
/**
 * Base class for every options framework field type.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

use ImportExportMenu\Options\HasConditions;

defined( 'ABSPATH' ) || exit;

/**
 * Common field surface area. Concrete fields override `render` and `sanitize`.
 *
 * Subclasses MAY also override:
 *   - {@see static::TYPE}      — the type slug used in `add_field()` configs.
 *   - {@see static::default_value()} — to customise the default fallback.
 *
 * Config keys read by this base class:
 *   - id          (string, required) — unique field id, used as POST key + storage key.
 *   - label       (string)           — display label.
 *   - description (string)           — secondary description rendered under the control.
 *   - default     (mixed)            — default value when nothing is stored.
 *   - attributes  (array)            — extra HTML attributes for the control element.
 *   - placeholder (string)           — convenience for `attributes['placeholder']`.
 *   - class       (string)           — extra CSS class for the wrapping row.
 *   - width       (string)           — control width variant for inputs that
 *                                       support it: `xs`, `sm`, `md`, `lg`,
 *                                       `xl`, `full`. Defaults to `md`.
 *   - visible     (bool)             — when explicitly `false`, the field row is
 *                                       skipped during rendering. Defaults to `true`.
 *   - tier        (string)           — optional plan badge rendered next to the
 *                                       label. Accepts `free` or `pro`. Empty
 *                                       string (default) renders no badge.
 *
 * @since 2.1.0
 */
abstract class AbstractField {

	use HasConditions;

	/**
	 * Type slug; subclasses override.
	 */
	protected const TYPE = '';

	/**
	 * Field identifier (POST key, storage key).
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Human-readable label.
	 *
	 * @var string
	 */
	protected $label;

	/**
	 * Description rendered under the control.
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Default value if nothing is stored.
	 *
	 * @var mixed
	 */
	protected $default;

	/**
	 * Whole config kept around for subclasses to read extras.
	 *
	 * @var array<string,mixed>
	 */
	protected $config;

	/**
	 * Extra HTML attributes for the control. `null` and `false` skip the
	 * attribute entirely; `true` renders a boolean attribute.
	 *
	 * @var array<string,scalar|null>
	 */
	protected $attributes;

	/**
	 * Capture and normalise the field configuration.
	 *
	 * @param array<string,mixed> $config Field configuration.
	 * @throws \InvalidArgumentException When `id` is missing.
	 */
	public function __construct( array $config ) {
		if ( empty( $config['id'] ) || ! is_string( $config['id'] ) ) {
			throw new \InvalidArgumentException( 'Field config requires a non-empty "id" string.' );
		}

		$this->id          = $config['id'];
		$this->label       = isset( $config['label'] ) ? (string) $config['label'] : '';
		$this->description = isset( $config['description'] ) ? (string) $config['description'] : '';
		$this->default     = array_key_exists( 'default', $config ) ? $config['default'] : null;
		$this->config      = $config;

		$attributes = isset( $config['attributes'] ) && is_array( $config['attributes'] )
			? $config['attributes']
			: array();
		if ( ! empty( $config['placeholder'] ) ) {
			$attributes['placeholder'] = (string) $config['placeholder'];
		}
		$this->attributes = $attributes;
	}

	/**
	 * Field identifier (POST key + storage key).
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Human-readable label.
	 *
	 * @return string
	 */
	public function label(): string {
		return $this->label;
	}

	/**
	 * Description rendered under the control.
	 *
	 * @return string
	 */
	public function description(): string {
		return $this->description;
	}

	/**
	 * Default value used when nothing is stored.
	 *
	 * @return mixed
	 */
	public function default_value() {
		return $this->default;
	}

	/**
	 * Type slug used in `add_field()` configs.
	 *
	 * @return string
	 */
	public function type(): string {
		return static::TYPE;
	}

	/**
	 * Whether the renderer should output this field at all. Useful for fields
	 * that need to live in the registry (so their values keep sanitizing) but
	 * should not be presented in the UI right now.
	 *
	 * @return bool
	 */
	public function is_visible(): bool {
		if ( ! array_key_exists( 'visible', $this->config ) ) {
			return true;
		}
		return (bool) $this->config['visible'];
	}

	/**
	 * Plan badge slug rendered next to the label. Only `free` and `pro` are
	 * recognised; anything else (including the absence of the config key)
	 * renders no badge.
	 *
	 * @return string Empty string when no badge should be rendered.
	 */
	public function tier(): string {
		if ( ! isset( $this->config['tier'] ) ) {
			return '';
		}
		$tier = strtolower( (string) $this->config['tier'] );
		return in_array( $tier, array( 'free', 'pro' ), true ) ? $tier : '';
	}

	/**
	 * Raw config array, exposed for tests and advanced extensions.
	 *
	 * @return array<string,mixed>
	 */
	public function config(): array {
		return $this->config;
	}

	/**
	 * Normalised visibility condition for this field, or an empty array when
	 * the field is unconditional.
	 *
	 * Two config shapes are accepted:
	 *
	 *   1. Single rule:
	 *      `'condition' => [ 'field' => 'mode', 'value' => 'advanced' ]`
	 *
	 *   2. Multiple rules:
	 *      `'condition' => [
	 *          [ 'field' => 'enabled', 'value' => true ],
	 *          [ 'field' => 'mode', 'operator' => '!=', 'value' => 'simple' ],
	 *      ]`
	 *      The combinator defaults to AND; pass `'condition_relation' => 'OR'`
	 *      to change it.
	 *
	 * Supported operators: `==` (default), `!=`, `in` (value as array), `not_in`.
	 *
	 * @return array{rules:array<int,array<string,mixed>>,relation:string}|array{}
	 */
	public function condition(): array {
		return $this->normalise_condition(
			$this->config['condition'] ?? null,
			$this->config['condition_relation'] ?? null
		);
	}

	/**
	 * Render the control. Implementations MUST escape every output.
	 *
	 * @param string $name_attr Pre-built HTML name attribute (e.g. `import_export_menu_settings[my_field]`).
	 * @param mixed  $value     Current stored value.
	 */
	abstract public function render( string $name_attr, $value ): void;

	/**
	 * Convert raw input into the canonical, persisted shape.
	 *
	 * @param mixed $raw Raw POST value (may be null when missing).
	 * @return mixed
	 */
	abstract public function sanitize( $raw );

	/**
	 * Helper to render the description paragraph.
	 */
	protected function render_description(): void {
		if ( '' === $this->description ) {
			return;
		}
		echo '<p class="description">' . esc_html( $this->description ) . '</p>';
	}

	/**
	 * Resolve the configured control-width variant into its modifier slug.
	 *
	 * Reads the optional `width` config key and validates it against the known
	 * variants. Inputs use this to opt into an explicit width class (e.g.
	 * `import-export-menu-input--lg`) instead of inheriting WP core's `.regular-text`
	 * 25em default.
	 *
	 * @return string One of `xs`, `sm`, `md`, `lg`, `xl`, `full`. Defaults to `md`.
	 */
	protected function width(): string {
		$width = isset( $this->config['width'] ) ? strtolower( (string) $this->config['width'] ) : 'md';
		return in_array( $width, array( 'xs', 'sm', 'md', 'lg', 'xl', 'full' ), true ) ? $width : 'md';
	}

	/**
	 * Build a string of extra HTML attributes, escaped.
	 *
	 * @param array<string,scalar|null> $extras Additional attributes merged on top of $this->attributes.
	 */
	protected function attributes_html( array $extras = array() ): string {
		$merged = array_merge( $this->attributes, $extras );
		$parts  = array();
		foreach ( $merged as $key => $value ) {
			if ( true === $value ) {
				$parts[] = esc_attr( (string) $key );
				continue;
			}
			if ( false === $value || null === $value ) {
				continue;
			}
			$parts[] = sprintf( '%s="%s"', esc_attr( (string) $key ), esc_attr( (string) $value ) );
		}
		return implode( ' ', $parts );
	}
}
