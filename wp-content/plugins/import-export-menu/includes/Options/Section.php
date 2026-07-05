<?php
/**
 * Value object for an options framework section.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable description of a section grouped under a tab.
 *
 * @since 2.1.0
 */
final class Section {

	use HasConditions;

	/**
	 * Section identifier (slug, must be globally unique).
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Identifier of the parent tab.
	 *
	 * @var string
	 */
	private $tab_id;

	/**
	 * Section title.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Optional descriptive copy rendered above the fields.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Sort priority within the tab (lower runs first).
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
	 * Capture the section metadata.
	 *
	 * @param string                               $id                 Section slug.
	 * @param string                               $tab_id             Parent tab slug.
	 * @param string                               $title              Display title.
	 * @param string                               $description        Optional intro copy.
	 * @param int                                  $priority           Sort priority.
	 * @param array<string,mixed>|array<int,mixed> $condition          Raw visibility condition (single rule or list of rules).
	 * @param string                               $condition_relation Combinator for multiple rules: `AND` (default) or `OR`.
	 */
	public function __construct( string $id, string $tab_id, string $title, string $description = '', int $priority = 10, array $condition = array(), string $condition_relation = 'AND' ) {
		$this->id          = $id;
		$this->tab_id      = $tab_id;
		$this->title       = $title;
		$this->description = $description;
		$this->priority    = $priority;
		$this->condition   = $this->normalise_condition( $condition, $condition_relation );
	}

	/**
	 * Section slug used as the registry key.
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Slug of the parent tab.
	 *
	 * @return string
	 */
	public function tab_id(): string {
		return $this->tab_id;
	}

	/**
	 * Title rendered above the fields.
	 *
	 * @return string
	 */
	public function title(): string {
		return $this->title;
	}

	/**
	 * Optional descriptive copy rendered under the title.
	 *
	 * @return string
	 */
	public function description(): string {
		return $this->description;
	}

	/**
	 * Sort priority within the tab; lower numbers render first.
	 *
	 * @return int
	 */
	public function priority(): int {
		return $this->priority;
	}

	/**
	 * Normalised visibility condition, or an empty array when the section is
	 * unconditional. Same shape as {@see Fields\AbstractField::condition()}.
	 *
	 * @return array{rules:array<int,array<string,mixed>>,relation:string}|array{}
	 */
	public function condition(): array {
		return $this->condition;
	}
}
