<?php
/**
 * Menus feature module: a dashboard list of menus with per-menu actions.
 *
 * @package Import_Export_Menu
 * @since   3.0.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Menus;

use ImportExportMenu\Modules\ModuleInterface;
use ImportExportMenu\Modules\Support\MenuDuplicator;
use ImportExportMenu\Modules\Support\MenuImporter;
use ImportExportMenu\Modules\Support\MenuRepository;
use ImportExportMenu\Modules\Support\MenuSerializer;
use ImportExportMenu\Modules\Support\MenuWriter;
use ImportExportMenu\Modules\Support\ReferenceEncoder;
use ImportExportMenu\Modules\Support\ReferenceResolver;
use ImportExportMenu\Options\Framework;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the "Menus" dashboard group: an overview of every menu plus actions.
 *
 * The panel lists each menu with its item count, assigned theme locations, and
 * last-modified time, and offers a one-click Duplicate (and a link into the
 * native editor). Duplication runs through {@see MenuDuplicator}, so a clone keeps
 * its items, hierarchy, and visibility rules.
 *
 * @since 3.0.0
 */
final class MenusModule implements ModuleInterface {

	public const GROUP            = 'menus';
	public const ACTION_DUPLICATE = 'import_export_menu_duplicate_menu';
	public const ACTION_DELETE    = 'import_export_menu_delete_menu';

	/**
	 * Menu read layer.
	 *
	 * @var MenuRepository
	 */
	private $menus;

	/**
	 * Allow a repository to be injected; defaults to the real one.
	 *
	 * @param MenuRepository|null $menus Menu read layer.
	 */
	public function __construct( ?MenuRepository $menus = null ) {
		$this->menus = $menus ?? new MenuRepository();
	}

	/**
	 * Free tier — always loaded.
	 */
	public function tier(): string {
		return ModuleInterface::TIER_FREE;
	}

	/**
	 * Register the group, its panel renderer, and the duplicate handler.
	 */
	public function register_hooks(): void {
		add_action( 'import_export_menu_options_register', array( $this, 'register_group' ) );
		add_action( 'import_export_menu_options_render_group_' . self::GROUP, array( $this, 'render_panel' ) );
		add_action( 'admin_post_' . self::ACTION_DUPLICATE, array( $this, 'handle_duplicate' ) );
		add_action( 'admin_post_' . self::ACTION_DELETE, array( $this, 'handle_delete' ) );
	}

	/**
	 * Register the custom "Menus" sidebar group (first in the list).
	 *
	 * @param Framework $framework Options framework instance.
	 */
	public function register_group( Framework $framework ): void {
		$framework->add_group(
			self::GROUP,
			__( 'Menus', 'import-export-menu' ),
			'menu',
			5,
			array( 'custom' => true )
		);
	}

	/**
	 * Render the menus overview panel.
	 */
	public function render_panel(): void {
		$rows             = $this->rows();
		$duplicate_action = self::ACTION_DUPLICATE;
		$delete_action    = self::ACTION_DELETE;
		$notice           = $this->take_notice();

		require IMPORT_EXPORT_MENU_PATH . 'admin/partials/menus-panel.php';
	}

	/**
	 * Duplicate the chosen menu, then redirect back with a notice.
	 */
	public function handle_duplicate(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to duplicate menus.', 'import-export-menu' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION_DUPLICATE );

		$menu_id = isset( $_POST['menu_id'] ) ? (int) wp_unslash( $_POST['menu_id'] ) : 0;
		$report  = $menu_id > 0 ? $this->duplicator()->duplicate( $menu_id ) : array();
		$created = isset( $report['menus'][0]['menu_id'] ) ? (int) $report['menus'][0]['menu_id'] : 0;

		if ( $created > 0 ) {
			$this->store_notice(
				array(
					'type'    => 'success',
					'message' => sprintf(
						/* translators: %s: name of the newly created copy. */
						__( 'Menu duplicated as “%s”.', 'import-export-menu' ),
						(string) ( $report['menus'][0]['name'] ?? '' )
					),
				)
			);
		} else {
			$this->store_notice(
				array(
					'type'    => 'error',
					'message' => __( 'Could not duplicate that menu.', 'import-export-menu' ),
				)
			);
		}

		$this->redirect_back();
	}

	/**
	 * Delete the chosen menu, then redirect back with a notice.
	 */
	public function handle_delete(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to delete menus.', 'import-export-menu' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION_DELETE );

		$menu_id = isset( $_POST['menu_id'] ) ? (int) wp_unslash( $_POST['menu_id'] ) : 0;
		$menu    = $menu_id > 0 ? $this->menus->menu( $menu_id ) : null;

		if ( null !== $menu ) {
			$name = (string) $menu->name;
			( new MenuWriter() )->delete_menu( $menu_id );
			$this->store_notice(
				array(
					'type'    => 'success',
					'message' => sprintf(
						/* translators: %s: name of the deleted menu. */
						__( 'Menu “%s” deleted.', 'import-export-menu' ),
						$name
					),
				)
			);
		} else {
			$this->store_notice(
				array(
					'type'    => 'error',
					'message' => __( 'That menu no longer exists.', 'import-export-menu' ),
				)
			);
		}

		$this->redirect_back();
	}

	/**
	 * Build the display rows for every menu.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function rows(): array {
		$rows = array();
		foreach ( $this->menus->all_menus() as $menu ) {
			$menu_id = (int) $menu->term_id;
			$rows[]  = array(
				'id'        => $menu_id,
				'name'      => (string) $menu->name,
				'count'     => (int) $menu->count,
				'locations' => $this->location_labels( $menu_id ),
				'modified'  => $this->menus->last_modified( $menu_id ),
				'edit_url'  => admin_url( 'nav-menus.php?action=edit&menu=' . $menu_id ),
			);
		}
		return $rows;
	}

	/**
	 * Human labels of the theme locations a menu is assigned to.
	 *
	 * @param int $menu_id Menu term id.
	 * @return string[]
	 */
	private function location_labels( int $menu_id ): array {
		$registered = $this->menus->registered_locations();
		$labels     = array();
		foreach ( $this->menus->locations_for_menu( $menu_id ) as $slug ) {
			$labels[] = isset( $registered[ $slug ] ) ? (string) $registered[ $slug ] : $slug;
		}
		return $labels;
	}

	/**
	 * Build the menu duplicator with its full service graph.
	 *
	 * @return MenuDuplicator
	 */
	private function duplicator(): MenuDuplicator {
		$repository = new MenuRepository();

		return new MenuDuplicator(
			new MenuSerializer( $repository, new ReferenceEncoder() ),
			new MenuImporter( $repository, new MenuWriter(), new ReferenceResolver() )
		);
	}

	/**
	 * Redirect back to the Menus group page.
	 */
	private function redirect_back(): void {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => Framework::PAGE_SLUG,
					'group' => self::GROUP,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Persist a notice for display after the redirect.
	 *
	 * @param array<string,mixed> $notice Notice payload.
	 */
	private function store_notice( array $notice ): void {
		set_transient( $this->notice_key(), $notice, MINUTE_IN_SECONDS );
	}

	/**
	 * Pull and clear the stored notice.
	 *
	 * @return array<string,mixed>|null
	 */
	private function take_notice(): ?array {
		$notice = get_transient( $this->notice_key() );
		if ( false === $notice ) {
			return null;
		}
		delete_transient( $this->notice_key() );
		return is_array( $notice ) ? $notice : null;
	}

	/**
	 * Transient key for the current user's last menus-panel notice.
	 *
	 * @return string
	 */
	private function notice_key(): string {
		return 'import_export_menu_menus_notice_' . get_current_user_id();
	}
}
