<?php
/**
 * Value object for an options framework tab.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable description of a tab in the options page.
 *
 * @since 2.1.0
 */
final class Tab {

	use HasConditions;

	/**
	 * Tab identifier (slug).
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Human-readable label.
	 *
	 * @var string
	 */
	private $label;

	/**
	 * Sort priority (lower runs first).
	 *
	 * @var int
	 */
	private $priority;

	/**
	 * Optional dashicon name (without the `dashicons-` prefix).
	 *
	 * @var string
	 */
	private $icon;

	/**
	 * Normalised visibility condition, or an empty array when unconditional.
	 *
	 * @var array{rules:array<int,array<string,mixed>>,relation:string}|array{}
	 */
	private $condition;

	/**
	 * Capture the tab metadata.
	 *
	 * @param string                               $id                 Slug.
	 * @param string                               $label              Display label.
	 * @param int                                  $priority           Sort priority.
	 * @param string                               $icon               Optional icon name.
	 * @param array<string,mixed>|array<int,mixed> $condition          Raw visibility condition (single rule or list of rules).
	 * @param string                               $condition_relation Combinator for multiple rules: `AND` (default) or `OR`.
	 */
	public function __construct( string $id, string $label, int $priority = 10, string $icon = '', array $condition = array(), string $condition_relation = 'AND' ) {
		$this->id        = $id;
		$this->label     = $label;
		$this->priority  = $priority;
		$this->icon      = $icon;
		$this->condition = $this->normalise_condition( $condition, $condition_relation );
	}

	/**
	 * Tab slug used in the URL and as the registry key.
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Localized label rendered in the tab navigation.
	 *
	 * @return string
	 */
	public function label(): string {
		return $this->label;
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
	 * Optional dashicon name without the `dashicons-` prefix.
	 *
	 * @return string
	 */
	public function icon(): string {
		return $this->icon;
	}

	/**
	 * Normalised visibility condition, or an empty array when the tab is
	 * unconditional. Same shape as {@see Fields\AbstractField::condition()}.
	 *
	 * @return array{rules:array<int,array<string,mixed>>,relation:string}|array{}
	 */
	public function condition(): array {
		return $this->condition;
	}
}
