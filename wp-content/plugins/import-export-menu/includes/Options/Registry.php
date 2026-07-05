<?php
/**
 * In-memory registry of options framework groups, tabs, sections, and fields.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options;

use ImportExportMenu\Options\Fields\AbstractField;

defined( 'ABSPATH' ) || exit;

/**
 * Holds the registered structure and exposes lookups for the renderer.
 *
 * Hierarchy: Group → Tab → Section → Field. Tabs added without an explicit
 * group fall into a lazily-created `default` group; the renderer hides the
 * sidebar entirely when only the default group exists.
 *
 * Stable iteration order: priority asc, then registration order.
 *
 * @since 2.1.0
 */
final class Registry {

	public const DEFAULT_GROUP = 'default';

	/**
	 * Map of group_id => Group.
	 *
	 * @var array<string,Group>
	 */
	private $groups = array();

	/**
	 * Map of tab_id => Tab.
	 *
	 * @var array<string,Tab>
	 */
	private $tabs = array();

	/**
	 * Map of section_id => Section.
	 *
	 * @var array<string,Section>
	 */
	private $sections = array();

	/**
	 * Map of field_id => AbstractField.
	 *
	 * @var array<string,AbstractField>
	 */
	private $fields = array();

	/**
	 * Map of group_id => list of tab_ids in registration order.
	 *
	 * @var array<string,array<int,string>>
	 */
	private $group_tabs = array();

	/**
	 * Reverse lookup: tab_id => group_id.
	 *
	 * @var array<string,string>
	 */
	private $tab_groups = array();

	/**
	 * Map of tab_id => list of section_ids in registration order.
	 *
	 * @var array<string,array<int,string>>
	 */
	private $tab_sections = array();

	/**
	 * Map of section_id => list of field_ids in registration order.
	 *
	 * @var array<string,array<int,string>>
	 */
	private $section_fields = array();

	/**
	 * Whether any non-default group has been explicitly registered.
	 *
	 * @var bool
	 */
	private $has_explicit_group = false;

	/**
	 * Register a group.
	 *
	 * @param Group $group Group to register.
	 */
	public function add_group( Group $group ): void {
		$this->groups[ $group->id() ]     = $group;
		$this->group_tabs[ $group->id() ] = $this->group_tabs[ $group->id() ] ?? array();
		if ( self::DEFAULT_GROUP !== $group->id() ) {
			$this->has_explicit_group = true;
		}
	}

	/**
	 * Register a tab inside a group.
	 *
	 * If `$group_id` is empty (or omitted) the tab is parented to the
	 * lazily-created default group, which lets simple plugins ignore the
	 * group concept entirely.
	 *
	 * @param Tab    $tab      Tab to register.
	 * @param string $group_id Parent group slug.
	 * @throws \InvalidArgumentException When the parent group is unknown (and is not the default).
	 */
	public function add_tab( Tab $tab, string $group_id = '' ): void {
		if ( '' === $group_id ) {
			$group_id = self::DEFAULT_GROUP;
		}
		if ( ! isset( $this->groups[ $group_id ] ) ) {
			if ( self::DEFAULT_GROUP === $group_id ) {
				$this->add_group( new Group( self::DEFAULT_GROUP, '', '', 0 ) );
			} else {
				// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- exception messages are not browser output.
				throw new \InvalidArgumentException(
					sprintf( 'Cannot add tab "%s": group "%s" is not registered.', $tab->id(), $group_id )
				);
				// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}
		}
		$this->tabs[ $tab->id() ]         = $tab;
		$this->group_tabs[ $group_id ][]  = $tab->id();
		$this->tab_groups[ $tab->id() ]   = $group_id;
		$this->tab_sections[ $tab->id() ] = $this->tab_sections[ $tab->id() ] ?? array();
	}

	/**
	 * Register a section under a tab.
	 *
	 * @param Section $section Section to register.
	 * @throws \InvalidArgumentException When the parent tab is unknown.
	 */
	public function add_section( Section $section ): void {
		if ( ! isset( $this->tabs[ $section->tab_id() ] ) ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- exception messages are not browser output.
			throw new \InvalidArgumentException(
				sprintf( 'Cannot add section "%s": tab "%s" is not registered.', $section->id(), $section->tab_id() )
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		$this->sections[ $section->id() ]           = $section;
		$this->tab_sections[ $section->tab_id() ][] = $section->id();
		$this->section_fields[ $section->id() ]     = $this->section_fields[ $section->id() ] ?? array();
	}

	/**
	 * Register a field under a section.
	 *
	 * @param string        $section_id Parent section slug.
	 * @param AbstractField $field      Field to register.
	 * @throws \InvalidArgumentException When the parent section is unknown or the field id collides.
	 */
	public function add_field( string $section_id, AbstractField $field ): void {
		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- exception messages are not browser output.
		if ( ! isset( $this->sections[ $section_id ] ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Cannot add field "%s": section "%s" is not registered.', $field->id(), $section_id )
			);
		}
		if ( isset( $this->fields[ $field->id() ] ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Field id "%s" is already registered.', $field->id() )
			);
		}
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		$this->fields[ $field->id() ]          = $field;
		$this->section_fields[ $section_id ][] = $field->id();
	}

	/**
	 * Groups intended for the sidebar, ordered by priority then registration order.
	 *
	 * Returns an empty list when no explicit group has been registered — the
	 * lazily-created default group is never surfaced as a sidebar item.
	 *
	 * @return array<int,Group>
	 */
	public function groups(): array {
		if ( ! $this->has_explicit_group ) {
			return array();
		}
		$groups = array();
		foreach ( $this->groups as $group_id => $group ) {
			if ( self::DEFAULT_GROUP === $group_id ) {
				continue;
			}
			$groups[] = $group;
		}
		usort(
			$groups,
			static function ( Group $a, Group $b ): int {
				return $a->priority() <=> $b->priority();
			}
		);
		return $groups;
	}

	/**
	 * Whether any non-default group has been explicitly registered.
	 *
	 * The renderer uses this to decide whether to show the sidebar.
	 */
	public function has_groups(): bool {
		return $this->has_explicit_group;
	}

	/**
	 * Tabs for a given group, ordered by priority then insertion.
	 *
	 * @param string $group_id Group slug.
	 * @return array<int,Tab>
	 */
	public function tabs_for( string $group_id ): array {
		if ( ! isset( $this->group_tabs[ $group_id ] ) ) {
			return array();
		}
		$tabs = array();
		foreach ( $this->group_tabs[ $group_id ] as $tab_id ) {
			if ( isset( $this->tabs[ $tab_id ] ) ) {
				$tabs[] = $this->tabs[ $tab_id ];
			}
		}
		usort(
			$tabs,
			static function ( Tab $a, Tab $b ): int {
				return $a->priority() <=> $b->priority();
			}
		);
		return $tabs;
	}

	/**
	 * All registered tabs across every group, ordered by priority.
	 *
	 * @return array<int,Tab>
	 */
	public function tabs(): array {
		$tabs = array_values( $this->tabs );
		usort(
			$tabs,
			static function ( Tab $a, Tab $b ): int {
				return $a->priority() <=> $b->priority();
			}
		);
		return $tabs;
	}

	/**
	 * Group slug containing a given tab, or null when the tab is unknown.
	 *
	 * @param string $tab_id Tab slug.
	 */
	public function group_for( string $tab_id ): ?string {
		return $this->tab_groups[ $tab_id ] ?? null;
	}

	/**
	 * Sections under a tab, ordered by priority then insertion.
	 *
	 * @param string $tab_id Parent tab slug.
	 * @return array<int,Section>
	 */
	public function sections_for( string $tab_id ): array {
		if ( ! isset( $this->tab_sections[ $tab_id ] ) ) {
			return array();
		}
		$sections = array();
		foreach ( $this->tab_sections[ $tab_id ] as $section_id ) {
			if ( isset( $this->sections[ $section_id ] ) ) {
				$sections[] = $this->sections[ $section_id ];
			}
		}
		usort(
			$sections,
			static function ( Section $a, Section $b ): int {
				return $a->priority() <=> $b->priority();
			}
		);
		return $sections;
	}

	/**
	 * Fields registered under a section in insertion order.
	 *
	 * @param string $section_id Parent section slug.
	 * @return array<int,AbstractField>
	 */
	public function fields_for( string $section_id ): array {
		if ( ! isset( $this->section_fields[ $section_id ] ) ) {
			return array();
		}
		$fields = array();
		foreach ( $this->section_fields[ $section_id ] as $field_id ) {
			if ( isset( $this->fields[ $field_id ] ) ) {
				$fields[] = $this->fields[ $field_id ];
			}
		}
		return $fields;
	}

	/**
	 * Lookup a single field by id.
	 *
	 * @param string $field_id Field identifier.
	 */
	public function field( string $field_id ): ?AbstractField {
		return $this->fields[ $field_id ] ?? null;
	}

	/**
	 * Field ids registered anywhere under a group's tabs, in registration order.
	 *
	 * Used to scope a form submission to the navigation group that was on the
	 * page, so other groups' values are left untouched on save.
	 *
	 * @param string $group_id Group slug.
	 * @return array<int,string>
	 */
	public function field_ids_for_group( string $group_id ): array {
		$ids = array();
		foreach ( $this->group_tabs[ $group_id ] ?? array() as $tab_id ) {
			foreach ( $this->tab_sections[ $tab_id ] ?? array() as $section_id ) {
				foreach ( $this->section_fields[ $section_id ] ?? array() as $field_id ) {
					if ( isset( $this->fields[ $field_id ] ) ) {
						$ids[] = $field_id;
					}
				}
			}
		}
		return $ids;
	}

	/**
	 * All registered fields, flat.
	 *
	 * @return array<string,AbstractField>
	 */
	public function all_fields(): array {
		return $this->fields;
	}

	/**
	 * Map of field_id => default value for every registered field.
	 *
	 * @return array<string,mixed>
	 */
	public function defaults(): array {
		$defaults = array();
		foreach ( $this->fields as $field_id => $field ) {
			$defaults[ $field_id ] = $field->default_value();
		}
		return $defaults;
	}

	/**
	 * Whether anything has been registered.
	 *
	 * A custom action group carries no tabs (it renders its own panel), so an
	 * explicit group counts as content even when no tab exists.
	 */
	public function is_empty(): bool {
		return empty( $this->tabs ) && ! $this->has_explicit_group;
	}
}
