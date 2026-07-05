<?php
/**
 * Reconstructs navigation menus from a schema-v1 export payload.
 *
 * @package Import_Export_Menu
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Support;

defined( 'ABSPATH' ) || exit;

/**
 * The import engine: turns a validated payload back into live menus.
 *
 * For each menu it applies the chosen mode (create a new menu, replace one of
 * the same name, or merge into it), rebuilds every item by resolving its
 * portable reference to a local object, then re-parents items by mapping the
 * export's original ids to the freshly created ones — so hierarchy survives a
 * migration. Items whose object cannot be resolved fall back to a custom link
 * (or are skipped). A dry run resolves and counts everything without writing,
 * which powers the import preview. Returns a report the UI renders.
 *
 * @since 1.1.0
 */
final class MenuImporter {

	public const MODE_CREATE  = 'create';
	public const MODE_REPLACE = 'replace';
	public const MODE_MERGE   = 'merge';

	public const FALLBACK_CUSTOM = 'custom';
	public const FALLBACK_SKIP   = 'skip';

	/**
	 * Menu read layer.
	 *
	 * @var MenuRepository
	 */
	private $repository;

	/**
	 * Menu write layer.
	 *
	 * @var MenuWriter
	 */
	private $writer;

	/**
	 * Portable-reference resolver.
	 *
	 * @var ReferenceResolver
	 */
	private $resolver;

	/**
	 * Wire the collaborators.
	 *
	 * @param MenuRepository    $repository Menu read layer.
	 * @param MenuWriter        $writer     Menu write layer.
	 * @param ReferenceResolver $resolver   Portable-reference resolver.
	 */
	public function __construct( MenuRepository $repository, MenuWriter $writer, ReferenceResolver $resolver ) {
		$this->repository = $repository;
		$this->writer     = $writer;
		$this->resolver   = $resolver;
	}

	/**
	 * Import every menu in a (pre-validated) payload.
	 *
	 * @param array<string,mixed> $payload         Schema-v1 payload.
	 * @param string              $mode            One of the `MODE_*` constants.
	 * @param bool                $dry_run         When true, resolve and count without writing.
	 * @param string              $fallback        One of the `FALLBACK_*` constants for unresolved items.
	 * @param string              $assign_location Optional theme-location slug to assign the first
	 *                                             imported menu to (overriding any current holder).
	 * @return array{menus:array<int,array<string,mixed>>,totals:array<string,int>}
	 */
	public function import( array $payload, string $mode = self::MODE_CREATE, bool $dry_run = false, string $fallback = self::FALLBACK_CUSTOM, string $assign_location = '' ): array {
		$menus  = ( isset( $payload['menus'] ) && is_array( $payload['menus'] ) ) ? $payload['menus'] : array();
		$result = array(
			'menus'  => array(),
			'totals' => array(
				'menus'            => 0,
				'items_created'    => 0,
				'items_remapped'   => 0,
				'items_unresolved' => 0,
				'items_skipped'    => 0,
			),
		);

		foreach ( $menus as $menu_data ) {
			if ( ! is_array( $menu_data ) ) {
				continue;
			}
			$summary           = $this->import_menu( $menu_data, $mode, $dry_run, $fallback );
			$result['menus'][] = $summary;
			++$result['totals']['menus'];
			$result['totals']['items_created']    += $summary['items_created'];
			$result['totals']['items_remapped']   += $summary['items_remapped'];
			$result['totals']['items_unresolved'] += $summary['items_unresolved'];
			$result['totals']['items_skipped']    += $summary['items_skipped'];
		}

		if ( ! $dry_run && '' !== $assign_location ) {
			$result['location'] = $this->assign_chosen_location( $result['menus'], $assign_location );
		}

		return $result;
	}

	/**
	 * Assign a theme location to the first imported menu, reporting any conflict.
	 *
	 * Only one menu may own a location, so assigning it here displaces whatever
	 * menu held it — the report names that menu so the change is never silent.
	 * An unregistered slug is reported as not assigned rather than failing.
	 *
	 * @param array<int,array<string,mixed>> $menus    Per-menu import summaries.
	 * @param string                         $location Theme-location slug.
	 * @return array{requested:string,label:string,assigned:bool,menu:string,displaced:?string}
	 */
	private function assign_chosen_location( array $menus, string $location ): array {
		$registered = $this->repository->registered_locations();
		$summary    = array(
			'requested' => $location,
			'label'     => isset( $registered[ $location ] ) ? (string) $registered[ $location ] : $location,
			'assigned'  => false,
			'menu'      => '',
			'displaced' => null,
		);

		if ( ! isset( $registered[ $location ] ) ) {
			return $summary;
		}

		$target_id   = 0;
		$target_name = '';
		foreach ( $menus as $menu_summary ) {
			if ( (int) ( $menu_summary['menu_id'] ?? 0 ) > 0 ) {
				$target_id   = (int) $menu_summary['menu_id'];
				$target_name = (string) ( $menu_summary['name'] ?? '' );
				break;
			}
		}

		if ( 0 === $target_id ) {
			return $summary;
		}

		$current  = $this->repository->location_map();
		$previous = isset( $current[ $location ] ) ? (int) $current[ $location ] : 0;
		if ( $previous > 0 && $previous !== $target_id ) {
			$previous_menu        = $this->repository->menu( $previous );
			$summary['displaced'] = $previous_menu instanceof \WP_Term ? (string) $previous_menu->name : '';
		}

		$this->writer->assign_locations( $target_id, array( $location ) );
		$summary['assigned'] = true;
		$summary['menu']     = $target_name;

		return $summary;
	}

	/**
	 * Import a single menu node.
	 *
	 * @param array<string,mixed> $menu_data Menu node from the payload.
	 * @param string              $mode      Import mode.
	 * @param bool                $dry_run   Whether to skip writes.
	 * @param string              $fallback  Unresolved-item fallback.
	 * @return array<string,mixed>
	 */
	private function import_menu( array $menu_data, string $mode, bool $dry_run, string $fallback ): array {
		$name     = (string) ( $menu_data['name'] ?? '' );
		$existing = $this->repository->find_menu_by_name( $name );
		$items    = ( isset( $menu_data['items'] ) && is_array( $menu_data['items'] ) ) ? $menu_data['items'] : array();

		$action  = self::MODE_CREATE;
		$menu_id = 0;

		if ( self::MODE_REPLACE === $mode && $existing instanceof \WP_Term ) {
			$action = 'replaced';
			if ( ! $dry_run ) {
				$this->writer->delete_menu( (int) $existing->term_id );
				$menu_id = $this->writer->create_menu( $name );
			}
		} elseif ( self::MODE_MERGE === $mode && $existing instanceof \WP_Term ) {
			$action  = 'merged';
			$menu_id = (int) $existing->term_id;
		} else {
			$action = 'created';
			$name   = $this->unique_name( $name );
			if ( ! $dry_run ) {
				$menu_id = $this->writer->create_menu( $name );
			}
		}

		$counts = $this->build_items( $items, $menu_id, $dry_run, $fallback );

		if ( ! $dry_run && $menu_id > 0 ) {
			$this->apply_menu_settings( $menu_id, $menu_data );
		}

		return array(
			'name'             => $name,
			'action'           => $action,
			'menu_id'          => $menu_id,
			'items_created'    => $counts['created'],
			'items_remapped'   => $counts['remapped'],
			'items_unresolved' => $counts['unresolved'],
			'items_skipped'    => $counts['skipped'],
		);
	}

	/**
	 * Create every item, then rebuild hierarchy by id remapping.
	 *
	 * @param array<int,mixed> $items    Item nodes.
	 * @param int              $menu_id  Target menu id (0 during a dry run).
	 * @param bool             $dry_run  Whether to skip writes.
	 * @param string           $fallback Unresolved-item fallback.
	 * @return array{created:int,remapped:int,unresolved:int,skipped:int}
	 */
	private function build_items( array $items, int $menu_id, bool $dry_run, string $fallback ): array {
		$counts = array(
			'created'    => 0,
			'remapped'   => 0,
			'unresolved' => 0,
			'skipped'    => 0,
		);
		$id_map = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			list( $args, $status ) = $this->build_item_args( $item, $fallback );

			if ( 'skip' === $status ) {
				++$counts['skipped'];
				continue;
			}

			++$counts['created'];
			if ( 'remapped' === $status ) {
				++$counts['remapped'];
			} elseif ( 'unresolved' === $status ) {
				++$counts['unresolved'];
			}

			if ( ! $dry_run && $menu_id > 0 ) {
				$new_id = $this->writer->create_item( $menu_id, $args );
				if ( $new_id > 0 ) {
					$id_map[ (int) ( $item['id'] ?? 0 ) ] = $new_id;
					$this->writer->set_item_visibility( $new_id, MenuVisibility::normalize( (string) ( $item['visibility'] ?? '' ) ) );
				}
			}
		}

		if ( ! $dry_run && $menu_id > 0 ) {
			$this->rebuild_hierarchy( $items, $id_map );
		}

		return $counts;
	}

	/**
	 * Second pass: re-parent items using the original→new id map.
	 *
	 * @param array<int,mixed> $items  Item nodes.
	 * @param array<int,int>   $id_map Original item id → new item id.
	 */
	private function rebuild_hierarchy( array $items, array $id_map ): void {
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$old_id     = (int) ( $item['id'] ?? 0 );
			$old_parent = (int) ( $item['parent'] ?? 0 );
			if ( $old_parent > 0 && isset( $id_map[ $old_id ], $id_map[ $old_parent ] ) ) {
				$this->writer->set_item_parent( $id_map[ $old_id ], $id_map[ $old_parent ] );
			}
		}
	}

	/**
	 * Apply menu-level settings (theme locations, auto-add pages).
	 *
	 * @param int                 $menu_id   Menu id.
	 * @param array<string,mixed> $menu_data Menu node.
	 */
	private function apply_menu_settings( int $menu_id, array $menu_data ): void {
		if ( isset( $menu_data['locations'] ) && is_array( $menu_data['locations'] ) && ! empty( $menu_data['locations'] ) ) {
			$this->writer->assign_locations( $menu_id, array_map( 'strval', $menu_data['locations'] ) );
		}
		if ( ! empty( $menu_data['auto_add_pages'] ) ) {
			$this->writer->set_auto_add( $menu_id, true );
		}
	}

	/**
	 * Build `wp_update_nav_menu_item()` args for one item and classify it.
	 *
	 * @param array<string,mixed> $item     Item node.
	 * @param string              $fallback Unresolved-item fallback.
	 * @return array{0:array<string,mixed>,1:string} Args plus a status of
	 *               `remapped`, `unresolved`, `custom`, or `skip`.
	 */
	private function build_item_args( array $item, string $fallback ): array {
		$reference = ( isset( $item['reference'] ) && is_array( $item['reference'] ) ) ? $item['reference'] : array();
		$type      = (string) ( $reference['type'] ?? 'custom' );
		$common    = $this->common_args( $item );

		if ( 'post_type' === $type || 'taxonomy' === $type ) {
			$object_id = $this->resolver->resolve( $reference );
			if ( $object_id > 0 ) {
				return array(
					$common + array(
						'menu-item-type'      => $type,
						'menu-item-object'    => (string) ( $reference['object'] ?? '' ),
						'menu-item-object-id' => $object_id,
					),
					'remapped',
				);
			}
			return $this->fallback_args( $item, $common, $fallback );
		}

		if ( 'post_type_archive' === $type ) {
			$object = (string) ( $reference['object'] ?? '' );
			if ( '' !== $object && post_type_exists( $object ) ) {
				return array(
					$common + array(
						'menu-item-type'   => 'post_type_archive',
						'menu-item-object' => $object,
						'menu-item-url'    => (string) ( $item['url'] ?? '' ),
					),
					'remapped',
				);
			}
			return $this->fallback_args( $item, $common, $fallback );
		}

		return array(
			$common + array(
				'menu-item-type' => 'custom',
				'menu-item-url'  => (string) ( $item['url'] ?? '' ),
			),
			'custom',
		);
	}

	/**
	 * Fallback for an item whose object could not be resolved.
	 *
	 * @param array<string,mixed> $item     Item node.
	 * @param array<string,mixed> $common   Shared args.
	 * @param string              $fallback Fallback mode.
	 * @return array{0:array<string,mixed>,1:string}
	 */
	private function fallback_args( array $item, array $common, string $fallback ): array {
		if ( self::FALLBACK_SKIP === $fallback ) {
			return array( array(), 'skip' );
		}
		return array(
			$common + array(
				'menu-item-type' => 'custom',
				'menu-item-url'  => (string) ( $item['url'] ?? '' ),
			),
			'unresolved',
		);
	}

	/**
	 * Shared `menu-item-*` args carried by every item kind.
	 *
	 * @param array<string,mixed> $item Item node.
	 * @return array<string,mixed>
	 */
	private function common_args( array $item ): array {
		$classes = ( isset( $item['classes'] ) && is_array( $item['classes'] ) ) ? $item['classes'] : array();

		return array(
			'menu-item-title'       => (string) ( $item['title'] ?? '' ),
			'menu-item-target'      => (string) ( $item['target'] ?? '' ),
			'menu-item-attr-title'  => (string) ( $item['attr_title'] ?? '' ),
			'menu-item-description' => (string) ( $item['description'] ?? '' ),
			'menu-item-xfn'         => (string) ( $item['xfn'] ?? '' ),
			'menu-item-classes'     => implode( ' ', array_map( 'strval', $classes ) ),
			'menu-item-position'    => (int) ( $item['menu_order'] ?? 0 ),
			'menu-item-status'      => 'publish',
		);
	}

	/**
	 * Derive a menu name that does not clash with an existing one.
	 *
	 * @param string $name Desired name.
	 * @return string
	 */
	private function unique_name( string $name ): string {
		if ( null === $this->repository->find_menu_by_name( $name ) ) {
			return $name;
		}
		$suffix = 2;
		do {
			$candidate = $name . ' (' . $suffix . ')';
			++$suffix;
		} while ( null !== $this->repository->find_menu_by_name( $candidate ) && $suffix < 100 );

		return $candidate;
	}
}
