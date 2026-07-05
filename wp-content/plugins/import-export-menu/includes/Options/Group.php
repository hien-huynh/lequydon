<?php
/**
 * Value object for an options framework group (sidebar item).
 *
 * @package Import_Export_Menu
 * @since   2.2.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Top-level navigation entry rendered in the dashboard sidebar.
 *
 * Groups contain tabs; tabs contain sections; sections contain fields. A
 * group with no tabs renders as an empty sidebar entry — useful as a roadmap
 * placeholder in starter projects.
 *
 * @since 2.2.0
 */
final class Group {

	use HasConditions;

	/**
	 * Group identifier (slug).
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Display label.
	 *
	 * @var string
	 */
	private $label;

	/**
	 * Optional dashicon name (without the `dashicons-` prefix).
	 *
	 * @var string
	 */
	private $icon;

	/**
	 * Sort priority; lower numbers render first.
	 *
	 * @var int
	 */
	private $priority;

	/**
	 * Normalised visibility condition, or an empty array when unconditional.
	 *
	 * @var array{rules:array<int,array<string,mixed>>,relation:string}|array{}
	 */
	private $condition;

	/**
	 * Whether this group renders its own action panel instead of settings tabs.
	 *
	 * @var bool
	 */
	private $is_custom;

	/**
	 * Capture the group metadata.
	 *
	 * @param string                               $id                 Slug.
	 * @param string                               $label              Display label.
	 * @param string                               $icon               Dashicon name without the `dashicons-` prefix.
	 * @param int                                  $priority           Sort priority.
	 * @param array<string,mixed>|array<int,mixed> $condition          Raw visibility condition (single rule or list of rules).
	 * @param string                               $condition_relation Combinator for multiple rules: `AND` (default) or `OR`.
	 * @param bool                                 $is_custom          Whether the group renders its own action panel.
	 */
	public function __construct( string $id, string $label, string $icon = '', int $priority = 10, array $condition = array(), string $condition_relation = 'AND', bool $is_custom = false ) {
		$this->id        = $id;
		$this->label     = $label;
		$this->icon      = $icon;
		$this->priority  = $priority;
		$this->condition = $this->normalise_condition( $condition, $condition_relation );
		$this->is_custom = $is_custom;
	}

	/**
	 * Group slug used as the registry key.
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Localized label rendered in the sidebar.
	 *
	 * @return string
	 */
	public function label(): string {
		return $this->label;
	}

	/**
	 * Dashicon name without the `dashicons-` prefix.
	 *
	 * @return string
	 */
	public function icon(): string {
		return $this->icon;
	}

	/**
	 * Sort priority; lower numbers render first.
	 *
	 * @return int
	 */
	public function priority(): int {
		return $this->priority;
	}

	/**
	 * Normalised visibility condition, or an empty array when the group is
	 * unconditional. Same shape as {@see Fields\AbstractField::condition()}.
	 *
	 * @return array{rules:array<int,array<string,mixed>>,relation:string}|array{}
	 */
	public function condition(): array {
		return $this->condition;
	}

	/**
	 * Whether this is a custom action group.
	 *
	 * Custom groups own their content: the renderer skips the settings form and
	 * fires `import_export_menu_options_render_group_{id}` so the owning module
	 * can echo its own panel (e.g. an export/import form posting to admin-post).
	 *
	 * @return bool
	 */
	public function is_custom(): bool {
		return $this->is_custom;
	}
}
