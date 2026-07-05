<?php
/**
 * Public entry point for the options framework.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options;

use ImportExportMenu\Options\Fields\AbstractField;

defined( 'ABSPATH' ) || exit;

/**
 * Orchestrates the options framework lifecycle:
 *  - exposes the public registration API (`add_tab`, `add_section`, `add_field`),
 *  - hooks the WordPress admin (menu page, Settings API, asset enqueues),
 *  - delegates rendering and sanitization to dedicated collaborators.
 *
 * Extension points (hooks):
 *   - action `import_export_menu_options_register`          (Framework $framework)
 *   - filter `import_export_menu_options_field_types`       (array $map)
 *   - filter `import_export_menu_options_sanitize_{field_id}` (mixed $value, mixed $raw, AbstractField $field)
 *   - action `import_export_menu_options_before_save`       (array $input, Registry $registry)
 *   - action `import_export_menu_options_after_save`        (array $clean, array $input, Registry $registry)
 *   - action `import_export_menu_options_render_group_{id}` (no args) — body of a custom action group
 *
 * @since 2.1.0
 */
final class Framework {

	public const OPTION_GROUP = 'import_export_menu_options';
	public const OPTION_NAME  = Repository::OPTION_NAME;
	public const PARENT_SLUG  = 'import-export-menu';
	public const PAGE_SLUG    = 'import-export-menu-settings';
	public const RESET_ACTION = 'import_export_menu_options_reset';

	/**
	 * Registry of tabs, sections, and fields.
	 *
	 * @var Registry
	 */
	private $registry;

	/**
	 * Storage repository for the merged option payload.
	 *
	 * @var Repository
	 */
	private $repository;

	/**
	 * Sanitization pipeline used as the Settings API callback.
	 *
	 * @var Sanitizer
	 */
	private $sanitizer;

	/**
	 * Builds Field instances from declarative config arrays.
	 *
	 * @var FieldFactory
	 */
	private $factory;

	/**
	 * Renders the options page template.
	 *
	 * @var PageRenderer
	 */
	private $renderer;

	/**
	 * Plugin handle used as the asset prefix.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version used for asset cache busting.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Hook suffix returned by `add_submenu_page`.
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Wire the framework collaborators. Use {@see self::create()} to get a fully wired instance.
	 *
	 * @param Registry     $registry    Field registry.
	 * @param Repository   $repository  Storage repository.
	 * @param Sanitizer    $sanitizer   Sanitization pipeline.
	 * @param FieldFactory $factory     Field factory.
	 * @param PageRenderer $renderer    Page renderer.
	 * @param string       $plugin_name Plugin handle.
	 * @param string       $version     Plugin version.
	 */
	public function __construct(
		Registry $registry,
		Repository $repository,
		Sanitizer $sanitizer,
		FieldFactory $factory,
		PageRenderer $renderer,
		string $plugin_name,
		string $version
	) {
		$this->registry    = $registry;
		$this->repository  = $repository;
		$this->sanitizer   = $sanitizer;
		$this->factory     = $factory;
		$this->renderer    = $renderer;
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Convenience constructor wiring the default collaborators.
	 *
	 * @param string $plugin_name Plugin handle.
	 * @param string $version     Plugin version.
	 */
	public static function create( string $plugin_name, string $version ): self {
		$registry   = new Registry();
		$repository = new Repository( $registry );
		$sanitizer  = new Sanitizer( $registry, $repository );
		$factory    = new FieldFactory();
		$renderer   = new PageRenderer( $registry, $repository, self::OPTION_GROUP, self::OPTION_NAME, self::PAGE_SLUG );

		return new self( $registry, $repository, $sanitizer, $factory, $renderer, $plugin_name, $version );
	}

	/**
	 * Register WP hooks. Call once during plugin bootstrap.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'collect_registrations' ), 5 );
		add_action( 'admin_menu', array( $this, 'register_submenu' ), 20 );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_' . self::RESET_ACTION, array( $this, 'handle_reset' ) );
	}

	/**
	 * Fire the registration action so consumers can declare their schema.
	 *
	 * Runs at `init` priority 5 to give themes/plugins a chance to register
	 * fields before any code reads `Repository::all()`.
	 */
	public function collect_registrations(): void {
		/**
		 * Register options framework tabs/sections/fields.
		 *
		 * @param Framework $framework Framework instance — call $framework->add_tab/section/field on it.
		 */
		do_action( 'import_export_menu_options_register', $this );
	}

	// ------------------------------------------------------------------
	// Public registration API.
	// ------------------------------------------------------------------

	/**
	 * Register a top-level navigation group rendered in the dashboard sidebar.
	 *
	 * Groups are optional — plugins that only need a single navigation level
	 * can skip them and call `add_tab()` directly. The renderer hides the
	 * sidebar when no explicit group is registered.
	 *
	 * @param string              $id       Group slug.
	 * @param string              $label    Display label.
	 * @param string              $icon     Dashicon name without the `dashicons-` prefix.
	 * @param int                 $priority Sort priority (lower runs first).
	 * @param array<string,mixed> $args     Optional extras. Recognised keys:
	 *                                       `condition` (single rule or list of rules)
	 *                                       and `condition_relation` (`AND`/`OR`) for
	 *                                       conditionally showing the sidebar entry;
	 *                                       `custom` (bool) to render the group as an
	 *                                       action panel via
	 *                                       `import_export_menu_options_render_group_{id}`
	 *                                       instead of settings tabs.
	 */
	public function add_group( string $id, string $label, string $icon = '', int $priority = 10, array $args = array() ): self {
		$this->registry->add_group(
			new Group(
				$id,
				$label,
				$icon,
				$priority,
				isset( $args['condition'] ) && is_array( $args['condition'] ) ? $args['condition'] : array(),
				isset( $args['condition_relation'] ) ? (string) $args['condition_relation'] : 'AND',
				! empty( $args['custom'] )
			)
		);
		return $this;
	}

	/**
	 * Register a tab inside a group.
	 *
	 * Pass an empty `$group_id` to keep the tab in the lazily-created default
	 * group, which preserves the simpler 1-level navigation for small plugins.
	 *
	 * @param string              $id       Tab slug.
	 * @param string              $label    Display label.
	 * @param string              $group_id Parent group slug (empty for the default group).
	 * @param int                 $priority Sort priority within the group.
	 * @param string              $icon     Optional dashicon name without the `dashicons-` prefix.
	 * @param array<string,mixed> $args     Optional extras. Recognised keys:
	 *                                       `condition` (single rule or list of rules)
	 *                                       and `condition_relation` (`AND`/`OR`) for
	 *                                       conditionally showing the sub-tab.
	 */
	public function add_tab( string $id, string $label, string $group_id = '', int $priority = 10, string $icon = '', array $args = array() ): self {
		$this->registry->add_tab(
			new Tab(
				$id,
				$label,
				$priority,
				$icon,
				isset( $args['condition'] ) && is_array( $args['condition'] ) ? $args['condition'] : array(),
				isset( $args['condition_relation'] ) ? (string) $args['condition_relation'] : 'AND'
			),
			$group_id
		);
		return $this;
	}

	/**
	 * Register a section under a tab.
	 *
	 * @param string              $tab_id      Parent tab slug.
	 * @param string              $id          Section slug.
	 * @param string              $title       Display title.
	 * @param string              $description Optional intro copy.
	 * @param int                 $priority    Sort priority within the tab.
	 * @param array<string,mixed> $args        Optional extras. Recognised keys:
	 *                                          `condition` (single rule or list of rules)
	 *                                          and `condition_relation` (`AND`/`OR`) for
	 *                                          conditionally showing the section card.
	 */
	public function add_section( string $tab_id, string $id, string $title, string $description = '', int $priority = 10, array $args = array() ): self {
		$this->registry->add_section(
			new Section(
				$id,
				$tab_id,
				$title,
				$description,
				$priority,
				isset( $args['condition'] ) && is_array( $args['condition'] ) ? $args['condition'] : array(),
				isset( $args['condition_relation'] ) ? (string) $args['condition_relation'] : 'AND'
			)
		);
		return $this;
	}

	/**
	 * Register a field under a section.
	 *
	 * @param string              $section_id Parent section slug.
	 * @param array<string,mixed> $config     Field config (must include `id` and `type`).
	 */
	public function add_field( string $section_id, array $config ): self {
		$field = $this->factory->make( $config );
		$this->registry->add_field( $section_id, $field );
		return $this;
	}

	/**
	 * Register a fully-built {@see AbstractField} instance directly.
	 *
	 * Use this when you have a custom field class outside the factory map.
	 *
	 * @param string        $section_id Parent section slug.
	 * @param AbstractField $field      Field instance.
	 */
	public function add_field_instance( string $section_id, AbstractField $field ): self {
		$this->registry->add_field( $section_id, $field );
		return $this;
	}

	// ------------------------------------------------------------------
	// Hook callbacks.
	// ------------------------------------------------------------------

	/**
	 * Register the Settings submenu under the Import Export Menu top-level menu.
	 */
	public function register_submenu(): void {
		$this->page_hook = (string) add_submenu_page(
			self::PARENT_SLUG,
			__( 'Settings', 'import-export-menu' ),
			__( 'Settings', 'import-export-menu' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this->renderer, 'render' )
		);
	}

	/**
	 * Handle the Reset Defaults form submission.
	 *
	 * Verifies the nonce + capability, deletes the option, then redirects
	 * back to the page with `settings-updated=reset` for messaging.
	 */
	public function handle_reset(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to reset these settings.', 'import-export-menu' ) );
		}
		check_admin_referer( self::RESET_ACTION );
		$this->repository->delete();

		$redirect = add_query_arg(
			array(
				'page'             => self::PAGE_SLUG,
				'settings-updated' => 'reset',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Wire the Settings API option, sanitize callback, and registered defaults.
	 */
	public function register_setting(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'default'           => $this->registry->defaults(),
				'sanitize_callback' => array( $this->sanitizer, 'sanitize' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Enqueue admin assets only on the options page.
	 *
	 * @param string $hook Admin page hook suffix.
	 */
	public function enqueue_assets( $hook ): void {
		if ( '' === $this->page_hook || (string) $hook !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		$field_types = $this->collect_registered_field_types();
		$script_deps = array( 'jquery', 'wp-color-picker' );

		if ( in_array( 'media', $field_types, true ) || in_array( 'image', $field_types, true ) ) {
			wp_enqueue_media();
			$script_deps[] = 'media-editor';
		}

		if ( in_array( 'code', $field_types, true ) ) {
			$cm_settings   = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
			$script_deps[] = 'wp-codemirror';
			wp_localize_script(
				$this->plugin_name . '-options',
				'importExportMenuCodeEditor',
				is_array( $cm_settings ) ? $cm_settings : array()
			);
		}

		if ( in_array( 'repeater', $field_types, true ) ) {
			$script_deps[] = 'jquery-ui-sortable';
		}

		wp_enqueue_style(
			$this->plugin_name . '-options',
			IMPORT_EXPORT_MENU_ASSETS_URL . 'css/options-page.css',
			array( 'wp-color-picker' ),
			$this->asset_version( 'assets/css/options-page.css' )
		);

		wp_enqueue_script(
			$this->plugin_name . '-options',
			IMPORT_EXPORT_MENU_ASSETS_URL . 'js/options-page.js',
			array_values( array_unique( $script_deps ) ),
			$this->asset_version( 'assets/js/options-page.js' ),
			true
		);
	}

	/**
	 * Collect the unique type slugs of every registered field.
	 *
	 * Used to decide which optional WordPress assets to enqueue (media
	 * library, code editor, …) without forcing every option page to load
	 * everything.
	 *
	 * @return array<int,string>
	 */
	private function collect_registered_field_types(): array {
		$types = array();
		foreach ( $this->registry->all_fields() as $field ) {
			$types[] = $field->type();
		}
		return array_values( array_unique( $types ) );
	}

	// ------------------------------------------------------------------
	// Public accessors (read-only, used by tests/extenders).
	// ------------------------------------------------------------------

	/**
	 * Field registry, exposed for advanced extensions and tests.
	 */
	public function registry(): Registry {
		return $this->registry;
	}

	/**
	 * Storage repository, exposed for reading values.
	 */
	public function repository(): Repository {
		return $this->repository;
	}

	/**
	 * Sanitization pipeline, exposed primarily for tests.
	 */
	public function sanitizer(): Sanitizer {
		return $this->sanitizer;
	}

	/**
	 * Resolve a cache-busting version string for a built asset.
	 *
	 * Uses the file's mtime so editors see changes immediately during dev,
	 * and falls back to the plugin version when the artifact is missing.
	 *
	 * @param string $relative_path Path relative to the plugin root.
	 */
	private function asset_version( string $relative_path ): string {
		$absolute = IMPORT_EXPORT_MENU_PATH . $relative_path;
		if ( file_exists( $absolute ) ) {
			$mtime = filemtime( $absolute );
			if ( false !== $mtime ) {
				return (string) $mtime;
			}
		}
		return $this->version;
	}
}
