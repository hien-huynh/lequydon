<?php
/**
 * Frontend (public-facing) hook controller.
 *
 * @link       https://yukyhendiawan.com
 * @since      2.1.0
 *
 * @package    Import_Export_Menu
 */

declare(strict_types=1);

namespace ImportExportMenu\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Registers public-facing enqueues, gated behind opt-in filters.
 *
 * The boilerplate ships with no real frontend functionality, so loading
 * empty CSS/JS on every page view would be pure overhead. Forks should
 * return `true` from the filters below once they actually need the assets,
 * ideally only when a relevant template / shortcode / block is present.
 *
 * @since 2.1.0
 */
final class Controller {

	/**
	 * Plugin handle, used as the asset handle.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version, used for asset cache busting.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Capture plugin metadata used to register and version assets.
	 *
	 * @param string $plugin_name Plugin handle (used as asset handle).
	 * @param string $version     Plugin version (used for cache busting).
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register WP hooks for this component.
	 */
	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue the public stylesheet, gated by an opt-in filter.
	 */
	public function enqueue_styles(): void {
		/**
		 * Filter whether to enqueue the plugin's frontend stylesheet.
		 *
		 * Defaults to false because the boilerplate ships no frontend UI.
		 * Hook in `true` (typically conditionally) once the plugin actually
		 * has frontend functionality on the current request.
		 *
		 * @since 2.1.0
		 *
		 * @param bool $enqueue Whether to enqueue. Default false.
		 */
		if ( ! apply_filters( 'import_export_menu_enqueue_frontend_styles', false ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name,
			IMPORT_EXPORT_MENU_PLUGIN_URL . 'public/css/import-export-menu-public.css',
			array(),
			$this->version
		);
	}

	/**
	 * Enqueue the public script, gated by an opt-in filter.
	 */
	public function enqueue_scripts(): void {
		/**
		 * Filter whether to enqueue the plugin's frontend script.
		 *
		 * @since 2.1.0
		 *
		 * @param bool $enqueue Whether to enqueue. Default false.
		 */
		if ( ! apply_filters( 'import_export_menu_enqueue_frontend_scripts', false ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name,
			IMPORT_EXPORT_MENU_PLUGIN_URL . 'public/js/import-export-menu-public.js',
			array( 'jquery' ),
			$this->version,
			true
		);
	}
}
