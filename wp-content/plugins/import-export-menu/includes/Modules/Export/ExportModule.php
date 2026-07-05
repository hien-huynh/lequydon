<?php
/**
 * Export feature module: download navigation menus as a schema-v1 JSON file.
 *
 * @package Import_Export_Menu
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Export;

use ImportExportMenu\Modules\ModuleInterface;
use ImportExportMenu\Modules\Support\MenuRepository;
use ImportExportMenu\Modules\Support\MenuSerializer;
use ImportExportMenu\Modules\Support\ReferenceEncoder;
use ImportExportMenu\Options\Framework;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the "Export" dashboard group and streams the export download.
 *
 * The group is a custom action group (see Framework::add_group `custom`): its
 * panel renders a menu picker that posts to admin-post.php, where the handler
 * serializes the chosen menus and streams them back as a JSON attachment.
 *
 * @since 1.1.0
 */
final class ExportModule implements ModuleInterface {

	/**
	 * Dashboard group id and admin-post action / nonce slug.
	 */
	public const GROUP  = 'export';
	public const ACTION = 'import_export_menu_export';

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
	 * Register the group, its panel renderer, and the download handler.
	 */
	public function register_hooks(): void {
		add_action( 'import_export_menu_options_register', array( $this, 'register_group' ) );
		add_action( 'import_export_menu_options_render_group_' . self::GROUP, array( $this, 'render_panel' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_export' ) );
	}

	/**
	 * Register the custom "Export" sidebar group.
	 *
	 * @param Framework $framework Options framework instance.
	 */
	public function register_group( Framework $framework ): void {
		$framework->add_group(
			self::GROUP,
			__( 'Export', 'import-export-menu' ),
			'download',
			10,
			array( 'custom' => true )
		);
	}

	/**
	 * Render the export panel (menu picker + download button).
	 */
	public function render_panel(): void {
		$menus  = $this->menus->all_menus();
		$action = self::ACTION;
		require IMPORT_EXPORT_MENU_PATH . 'admin/partials/export-panel.php';
	}

	/**
	 * Handle the export submission: serialize the chosen menus and stream them.
	 */
	public function handle_export(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to export menus.', 'import-export-menu' ) );
		}
		check_admin_referer( self::ACTION );

		$menu_ids = array();
		if ( isset( $_POST['menu_ids'] ) && is_array( $_POST['menu_ids'] ) ) {
			$menu_ids = array_map( 'intval', wp_unslash( $_POST['menu_ids'] ) );
		}

		$serializer = new MenuSerializer( $this->menus, new ReferenceEncoder() );
		$json       = wp_json_encode( $serializer->serialize( $menu_ids ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		if ( false === $json ) {
			wp_die( esc_html__( 'Could not encode the menu export.', 'import-export-menu' ) );
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $this->filename() . '"' );
		header( 'Content-Length: ' . strlen( $json ) );

		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON file body served as a download, not HTML.
		exit;
	}

	/**
	 * Build a dated, site-scoped export filename.
	 *
	 * @return string
	 */
	private function filename(): string {
		$site = sanitize_title( (string) get_bloginfo( 'name' ) );
		$site = '' !== $site ? $site . '-' : '';

		return 'menu-export-' . $site . gmdate( 'Ymd-His' ) . '.json';
	}
}
