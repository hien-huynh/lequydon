<?php
/**
 * Plugin settings definition: declares every group, tab, section, and field
 * that the options framework exposes on the admin page.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for the plugin's settings structure.
 *
 * Disable by defining `IMPORT_EXPORT_MENU_DISABLE_SETTINGS` to a truthy value before
 * the plugin loads, or by removing this class from {@see \ImportExportMenu\Plugin::run()}.
 *
 * Marked `final` because this is the plugin's own settings declaration, not
 * an extension point — extenders should hook into `import_export_menu_options_register`
 * directly instead of subclassing.
 *
 * @since 2.1.0
 */
final class Settings {

	/**
	 * Subscribe the registration callback to the framework's register hook.
	 */
	public function register_hooks(): void {
		if ( defined( 'IMPORT_EXPORT_MENU_DISABLE_SETTINGS' ) && \IMPORT_EXPORT_MENU_DISABLE_SETTINGS ) {
			return;
		}
		add_action( 'import_export_menu_options_register', array( $this, 'register' ) );
	}

	/**
	 * Register the bundled field showcase.
	 *
	 * This is a reference gallery of every field type, shown by default so the
	 * starter is explorable out of the box. When building a real plugin, hide it
	 * by defining `IMPORT_EXPORT_MENU_DISABLE_DEMO` (truthy) and register your own schema
	 * by hooking `import_export_menu_options_register` directly.
	 *
	 * @param Framework $framework Framework instance provided by the action.
	 */
	public function register( Framework $framework ): void {
		if ( ! self::demo_enabled() ) {
			return;
		}

		$this->register_groups( $framework );
		$this->register_general_tabs( $framework );
		$this->register_general_sections( $framework );
		$this->register_advanced_sections( $framework );
		$this->register_api_keys_sections( $framework );
		$this->register_custom_assets_sections( $framework );
		$this->register_appearance_sections( $framework );
		$this->register_experimental_sections( $framework );
	}

	/**
	 * Whether the bundled field showcase should load.
	 *
	 * @return bool True unless `IMPORT_EXPORT_MENU_DISABLE_DEMO` is defined and truthy.
	 */
	private static function demo_enabled(): bool {
		return ! ( defined( 'IMPORT_EXPORT_MENU_DISABLE_DEMO' ) && \IMPORT_EXPORT_MENU_DISABLE_DEMO );
	}

	// ------------------------------------------------------------------
	// Default values — single source of truth.
	// ------------------------------------------------------------------

	/**
	 * Every field's default value, keyed by field id.
	 *
	 * This is the one place to change a default: the value flows into the
	 * field registration below through {@see self::field_default()}, and the
	 * same accessor lets any other code read a field's default on its own.
	 *
	 * @return array<string,mixed>
	 */
	public static function field_defaults(): array {
		return array(
			// General → Basic Configuration → Text Inputs.
			'license_key'           => '',
			'admin_email'           => 'admin@example.com',
			'welcome_message'       => __( 'Welcome to our newly optimized WordPress site!', 'import-export-menu' ),

			// General → Basic Configuration → Feature Toggles.
			'debug_mode'            => true,
			'auto_minify'           => false,
			'lazy_loading'          => true,

			// General → Basic Configuration → Adjustment Controls.
			'cache_expiration'      => 24,
			'image_compression'     => 60,
			'max_upload_size'       => 64,

			// General → Basic Configuration → Selection Options.
			'default_user_role'     => 'subscriber',
			'backup_frequency'      => 'weekly',

			// General → Basic Configuration → Feature Selection.
			'features'              => array( 'email_notifications', 'auto_update' ),

			// General → Basic Configuration → Dropdown Selections.
			'timezone'              => 'asia_jakarta',
			'date_format'           => 'dmy',
			'language'              => 'en_us',

			// General → Basic Configuration → Advanced Input Types.
			'posts_per_page'        => 10,
			'brand_color'           => '#155dfc',
			'session_timeout'       => 30,

			// General → Basic Configuration → File Management.
			'site_logo'             => 0,
			'import_config'         => 0,

			// General → Advanced Options → Content Blocks.
			'layout_style'          => 'card',
			'campaign_end'          => '',
			'welcome_html'          => '<p>Welcome to your newly optimized WordPress site!</p>',

			// General → API Keys → Integration Keys.
			'analytics_key'         => '',
			'webhook_secret'        => '',

			// General → Custom CSS/JS.
			'custom_css'            => "/* Add your custom CSS here */\n\n\n",
			'custom_js'             => "// Add your custom JavaScript here\n\n\n",

			// Appearance → Theme → Typography.
			'heading_typography'    => array(
				'family'      => 'Inter',
				'size'        => 24,
				'weight'      => '600',
				'unit'        => 'px',
				'line_height' => 1.3,
			),

			// Appearance → Theme → Spacing.
			'card_padding'          => array(
				'top'    => 16,
				'right'  => 24,
				'bottom' => 16,
				'left'   => 24,
				'unit'   => 'px',
				'linked' => false,
			),
			'card_padding_advanced' => false,

			// Appearance → Theme → Experimental (conditional section + tab demo).
			'experimental_theme'    => false,
			'accent_intensity'      => 50,
			'labs_layout_engine'    => 'flex',

			// Experimental Lab group (conditional sidebar group demo).
			'lab_telemetry'         => false,
			'card_margin'           => array(
				'top'    => 0,
				'right'  => 0,
				'bottom' => 24,
				'left'   => 0,
				'unit'   => 'px',
			),

			// Appearance → Navigation → Navigation Links.
			'menu_links'            => array(
				array(
					'label'  => 'Home',
					'url'    => 'https://example.com/',
					'target' => '_self',
				),
				array(
					'label'  => 'Documentation',
					'url'    => 'https://example.com/docs',
					'target' => '_blank',
				),
			),
		);
	}

	/**
	 * Look up a single field's default value.
	 *
	 * @param string $field_id Field identifier.
	 * @return mixed Default value, or null when the field id is unknown.
	 */
	public static function field_default( string $field_id ) {
		$defaults = self::field_defaults();
		return $defaults[ $field_id ] ?? null;
	}

	/**
	 * Read a single field's saved value, falling back to its default.
	 *
	 * Mirrors {@see Repository::get()} but is callable from anywhere without a
	 * Framework instance — handy in templates, controllers, or REST code. The
	 * fallback comes from {@see self::field_default()}, so it stays in sync
	 * with what the options page persists.
	 *
	 * @param string $field_id Field identifier.
	 * @return mixed Saved value when present, otherwise the registered default
	 *               (or null when the field id is unknown).
	 */
	public static function get( string $field_id ) {
		$stored = get_option( Repository::OPTION_NAME, array() );
		if ( is_array( $stored ) && array_key_exists( $field_id, $stored ) ) {
			return $stored[ $field_id ];
		}
		return self::field_default( $field_id );
	}

	/**
	 * Register the sidebar groups visible in the reference dashboard.
	 *
	 * The last entry is a conditional group: its sidebar link only appears while
	 * the Appearance → Theme "experimental" toggle is on. Because that toggle
	 * lives in the Appearance group, the sidebar entry updates live as you flip
	 * it; from any other group the visibility is resolved on the server's first
	 * paint (the controlling field is not in the DOM there).
	 *
	 * @param Framework $framework Framework instance.
	 */
	private function register_groups( Framework $framework ): void {
		$framework
			->add_group( 'general', __( 'General Settings', 'import-export-menu' ), 'admin-generic', 10 )
			->add_group( 'appearance', __( 'Appearance', 'import-export-menu' ), 'art', 20 )
			->add_group( 'performance', __( 'Performance', 'import-export-menu' ), 'performance', 30 )
			->add_group( 'security', __( 'Security', 'import-export-menu' ), 'shield', 40 )
			->add_group( 'database', __( 'Database', 'import-export-menu' ), 'database', 50 )
			->add_group( 'integrations', __( 'Integrations', 'import-export-menu' ), 'admin-plugins', 60 )
			->add_group(
				'experimental',
				__( 'Experimental Lab', 'import-export-menu' ),
				'lightbulb',
				70,
				array(
					'condition' => array(
						'field' => 'experimental_theme',
						'value' => 1,
					),
				)
			);
	}

	/**
	 * Register the four sub-tabs under the General Settings group.
	 *
	 * @param Framework $framework Framework instance.
	 */
	private function register_general_tabs( Framework $framework ): void {
		$framework
			->add_tab( 'basic', __( 'Basic Configuration', 'import-export-menu' ), 'general', 10 )
			->add_tab( 'advanced', __( 'Advanced Options', 'import-export-menu' ), 'general', 20 )
			->add_tab( 'api_keys', __( 'API Keys', 'import-export-menu' ), 'general', 30 )
			->add_tab( 'custom_assets', __( 'Custom CSS/JS', 'import-export-menu' ), 'general', 40 );
	}

	/**
	 * Register the seven sections + their fields under General → Basic Configuration.
	 *
	 * Each section maps to one of the field families showcased in the
	 * reference dashboard image.
	 *
	 * @param Framework $framework Framework instance.
	 */
	private function register_general_sections( Framework $framework ): void {
		$framework->add_section(
			'basic',
			'text_inputs',
			__( 'Text Inputs & Configuration', 'import-export-menu' ),
			__( 'Demo text inputs', 'import-export-menu' )
		);
		$framework
			->add_field(
				'text_inputs',
				array(
					'id'          => 'license_key',
					'tier'        => 'free',
					'type'        => 'text',
					'width'       => 'lg',
					'label'       => __( 'Plugin License Key', 'import-export-menu' ),
					'description' => __( 'Required for automatic updates and support.', 'import-export-menu' ),
					'placeholder' => __( 'Enter your license key', 'import-export-menu' ),
					'default'     => self::field_default( 'license_key' ),
				)
			)
			->add_field(
				'text_inputs',
				array(
					'id'          => 'admin_email',
					'tier'        => 'free',
					'type'        => 'text',
					'width'       => 'xl',
					'input_type'  => 'email',
					'label'       => __( 'Admin Email Address', 'import-export-menu' ),
					'description' => __( 'Where notifications will be sent.', 'import-export-menu' ),
					'default'     => self::field_default( 'admin_email' ),
				)
			)
			->add_field(
				'text_inputs',
				array(
					'id'      => 'welcome_message',
					'tier'    => 'free',
					'type'    => 'textarea',
					'label'   => __( 'Custom Welcome Message', 'import-export-menu' ),
					'default' => self::field_default( 'welcome_message' ),
					'rows'    => 4,
				)
			);

		$framework->add_section(
			'basic',
			'feature_toggles',
			__( 'Feature Toggles', 'import-export-menu' )
		);
		$framework
			->add_field(
				'feature_toggles',
				array(
					'id'          => 'debug_mode',
					'tier'        => 'free',
					'type'        => 'toggle',
					'label'       => __( 'Enable Debug Mode', 'import-export-menu' ),
					'description' => __( 'Logs errors to debug.log for troubleshooting.', 'import-export-menu' ),
					'default'     => self::field_default( 'debug_mode' ),
				)
			)
			->add_field(
				'feature_toggles',
				array(
					'id'          => 'auto_minify',
					'tier'        => 'free',
					'type'        => 'toggle',
					'label'       => __( 'Auto-Minify CSS/JS', 'import-export-menu' ),
					'description' => __( 'Automatically compress assets to improve load times.', 'import-export-menu' ),
					'default'     => self::field_default( 'auto_minify' ),
				)
			)
			->add_field(
				'feature_toggles',
				array(
					'id'          => 'lazy_loading',
					'tier'        => 'free',
					'type'        => 'toggle',
					'label'       => __( 'Enable Lazy Loading', 'import-export-menu' ),
					'description' => __( 'Defer loading of images and iframes in viewport.', 'import-export-menu' ),
					'default'     => self::field_default( 'lazy_loading' ),
				)
			);

		$framework->add_section(
			'basic',
			'adjustment_controls',
			__( 'Adjustment Controls', 'import-export-menu' )
		);
		$framework
			->add_field(
				'adjustment_controls',
				array(
					'id'          => 'cache_expiration',
					'tier'        => 'free',
					'type'        => 'slider',
					'label'       => __( 'Cache Expiration Time', 'import-export-menu' ),
					'min'         => 0,
					'max'         => 168,
					'step'        => 1,
					'unit'        => __( ' Hours', 'import-export-menu' ),
					'left_label'  => '0',
					'right_label' => '72h',
					'default'     => self::field_default( 'cache_expiration' ),
				)
			)
			->add_field(
				'adjustment_controls',
				array(
					'id'          => 'image_compression',
					'tier'        => 'free',
					'type'        => 'slider',
					'label'       => __( 'Image Compression Level', 'import-export-menu' ),
					'min'         => 0,
					'max'         => 100,
					'step'        => 1,
					'unit'        => '%',
					'left_label'  => __( 'Lossless', 'import-export-menu' ),
					'right_label' => __( 'Aggressive', 'import-export-menu' ),
					'default'     => self::field_default( 'image_compression' ),
				)
			)
			->add_field(
				'adjustment_controls',
				array(
					'id'          => 'max_upload_size',
					'tier'        => 'free',
					'type'        => 'slider',
					'label'       => __( 'Maximum Upload Size (MB)', 'import-export-menu' ),
					'min'         => 1,
					'max'         => 512,
					'step'        => 1,
					'unit'        => 'MB',
					'left_label'  => '1 MB',
					'right_label' => '512 MB',
					'default'     => self::field_default( 'max_upload_size' ),
				)
			);

		$framework->add_section(
			'basic',
			'selection_options',
			__( 'Selection Options', 'import-export-menu' )
		);
		$framework
			->add_field(
				'selection_options',
				array(
					'id'      => 'default_user_role',
					'tier'    => 'free',
					'type'    => 'radio',
					'label'   => __( 'Default User Role for Sync', 'import-export-menu' ),
					'default' => self::field_default( 'default_user_role' ),
					'choices' => array(
						'subscriber'  => __( 'Subscriber', 'import-export-menu' ),
						'contributor' => __( 'Contributor', 'import-export-menu' ),
						'author'      => __( 'Author', 'import-export-menu' ),
					),
				)
			)
			->add_field(
				'selection_options',
				array(
					'id'      => 'backup_frequency',
					'tier'    => 'free',
					'type'    => 'radio',
					'label'   => __( 'Backup Frequency', 'import-export-menu' ),
					'default' => self::field_default( 'backup_frequency' ),
					'choices' => array(
						'daily'   => __( 'Daily', 'import-export-menu' ),
						'weekly'  => __( 'Weekly', 'import-export-menu' ),
						'monthly' => __( 'Monthly', 'import-export-menu' ),
					),
				)
			);

		$framework->add_section(
			'basic',
			'feature_selection',
			__( 'Feature Selection', 'import-export-menu' )
		);
		$framework->add_field(
			'feature_selection',
			array(
				'id'      => 'features',
				'tier'    => 'free',
				'type'    => 'checkbox',
				'label'   => __( 'Enabled Features', 'import-export-menu' ),
				'default' => self::field_default( 'features' ),
				'choices' => array(
					'email_notifications' => __( 'Enable Email Notifications', 'import-export-menu' ),
					'auto_update'         => __( 'Auto-Update Plugin', 'import-export-menu' ),
					'analytics'           => __( 'Enable Analytics Tracking', 'import-export-menu' ),
					'error_reporting'     => __( 'Enable Error Reporting', 'import-export-menu' ),
				),
			)
		);

		$framework->add_section(
			'basic',
			'dropdown_selections',
			__( 'Dropdown Selections', 'import-export-menu' )
		);
		$framework
			->add_field(
				'dropdown_selections',
				array(
					'id'      => 'timezone',
					'tier'    => 'free',
					'type'    => 'select',
					'label'   => __( 'Timezone Settings', 'import-export-menu' ),
					'default' => self::field_default( 'timezone' ),
					'choices' => array(
						'utc'           => __( 'UTC', 'import-export-menu' ),
						'asia_jakarta'  => __( 'UTC +07:00 — Bangkok, Jakarta', 'import-export-menu' ),
						'asia_tokyo'    => __( 'UTC +09:00 — Tokyo, Seoul', 'import-export-menu' ),
						'europe_london' => __( 'UTC +00:00 — London', 'import-export-menu' ),
					),
				)
			)
			->add_field(
				'dropdown_selections',
				array(
					'id'      => 'date_format',
					'tier'    => 'free',
					'type'    => 'select',
					'label'   => __( 'Date Format', 'import-export-menu' ),
					'default' => self::field_default( 'date_format' ),
					'choices' => array(
						'dmy' => 'DD/MM/YYYY',
						'mdy' => 'MM/DD/YYYY',
						'ymd' => 'YYYY-MM-DD',
					),
				)
			)
			->add_field(
				'dropdown_selections',
				array(
					'id'      => 'language',
					'tier'    => 'free',
					'type'    => 'select',
					'label'   => __( 'Language', 'import-export-menu' ),
					'default' => self::field_default( 'language' ),
					'choices' => array(
						'en_us' => 'English (US)',
						'id_id' => 'Bahasa Indonesia',
						'ja_jp' => '日本語',
					),
				)
			);

		$framework->add_section(
			'basic',
			'advanced_inputs',
			__( 'Advanced Input Types', 'import-export-menu' )
		);
		$framework
			->add_field(
				'advanced_inputs',
				array(
					'id'          => 'posts_per_page',
					'tier'        => 'free',
					'type'        => 'number',
					'label'       => __( 'Maximum Posts Per Page', 'import-export-menu' ),
					'description' => __( 'Number of posts to display on archive pages.', 'import-export-menu' ),
					'min'         => 1,
					'max'         => 100,
					'step'        => 1,
					'default'     => self::field_default( 'posts_per_page' ),
				)
			)
			->add_field(
				'advanced_inputs',
				array(
					'id'      => 'brand_color',
					'tier'    => 'free',
					'type'    => 'color',
					'label'   => __( 'Primary Brand Color', 'import-export-menu' ),
					'default' => self::field_default( 'brand_color' ),
				)
			)
			->add_field(
				'advanced_inputs',
				array(
					'id'          => 'session_timeout',
					'tier'        => 'free',
					'type'        => 'number',
					'label'       => __( 'Session Timeout (minutes)', 'import-export-menu' ),
					'description' => __( 'Auto-logout users after this period of inactivity.', 'import-export-menu' ),
					'min'         => 5,
					'max'         => 1440,
					'step'        => 5,
					'default'     => self::field_default( 'session_timeout' ),
				)
			);

		$framework->add_section(
			'basic',
			'file_management',
			__( 'File Management', 'import-export-menu' )
		);
		$framework
			->add_field(
				'file_management',
				array(
					'id'           => 'site_logo',
					'tier'         => 'pro',
					'type'         => 'image',
					'label'        => __( 'Custom Logo Upload', 'import-export-menu' ),
					'description'  => __( 'Click to upload or drag and drop. PNG, JPG, or SVG up to 5 MB.', 'import-export-menu' ),
					'preview_size' => 'medium',
					'default'      => self::field_default( 'site_logo' ),
				)
			)
			->add_field(
				'file_management',
				array(
					'id'            => 'import_config',
					'tier'          => 'pro',
					'type'          => 'media',
					'label'         => __( 'Import Configuration File', 'import-export-menu' ),
					'description'   => __( 'Upload a JSON or YAML configuration export.', 'import-export-menu' ),
					'allowed_types' => 'application',
					'frame_title'   => __( 'Select Configuration File', 'import-export-menu' ),
					'frame_button'  => __( 'Use this configuration', 'import-export-menu' ),
					'default'       => self::field_default( 'import_config' ),
				)
			);
	}

	/**
	 * Populate the Advanced Options tab with a WYSIWYG content example.
	 *
	 * @param Framework $framework Framework instance.
	 */
	private function register_advanced_sections( Framework $framework ): void {
		$framework->add_section(
			'advanced',
			'content_blocks',
			__( 'Content Blocks', 'import-export-menu' ),
			__( 'Rich-text content emitted by your plugin templates.', 'import-export-menu' )
		);
		$framework
			->add_field(
				'content_blocks',
				array(
					'id'      => 'content_blocks_notice',
					'tier'    => 'free',
					'type'    => 'notice',
					'variant' => 'info',
					'title'   => __( 'Heads up', 'import-export-menu' ),
					'message' => __( 'These blocks are rendered inside your plugin templates. Use the campaign end date below to auto-expire the welcome message.', 'import-export-menu' ),
				)
			)
			->add_field(
				'content_blocks',
				array(
					'id'      => 'layout_style',
					'tier'    => 'free',
					'type'    => 'button_group',
					'label'   => __( 'Layout style', 'import-export-menu' ),
					'default' => self::field_default( 'layout_style' ),
					'choices' => array(
						'list'   => array(
							'label' => __( 'List', 'import-export-menu' ),
							'icon'  => 'dashicons-editor-ul',
						),
						'card'   => array(
							'label' => __( 'Card', 'import-export-menu' ),
							'icon'  => 'dashicons-screenoptions',
						),
						'banner' => array(
							'label' => __( 'Banner', 'import-export-menu' ),
							'icon'  => 'dashicons-align-wide',
						),
					),
				)
			)
			->add_field(
				'content_blocks',
				array(
					'id'          => 'campaign_end',
					'tier'        => 'free',
					'type'        => 'date',
					'label'       => __( 'Campaign end date', 'import-export-menu' ),
					'description' => __( 'Welcome message hides itself after this date.', 'import-export-menu' ),
					'default'     => self::field_default( 'campaign_end' ),
				)
			)
			->add_field(
				'content_blocks',
				array(
					'id'            => 'welcome_html',
					'tier'          => 'pro',
					'type'          => 'wysiwyg',
					'label'         => __( 'Welcome Message Body', 'import-export-menu' ),
					'description'   => __( 'Rendered on the dashboard widget. Supports the standard post HTML.', 'import-export-menu' ),
					'editor_height' => 220,
					'default'       => self::field_default( 'welcome_html' ),
				)
			);
	}

	/**
	 * Populate the API Keys tab with sensitive credential examples.
	 *
	 * @param Framework $framework Framework instance.
	 */
	private function register_api_keys_sections( Framework $framework ): void {
		$framework->add_section(
			'api_keys',
			'integration_keys',
			__( 'Integration Keys', 'import-export-menu' ),
			__( 'Credentials for third-party services. Stored in plain text — use environment variables in production.', 'import-export-menu' )
		);
		$framework
			->add_field(
				'integration_keys',
				array(
					'id'         => 'analytics_key',
					'tier'       => 'free',
					'type'       => 'text',
					'label'      => __( 'Analytics API Key', 'import-export-menu' ),
					'input_type' => 'password',
					'default'    => self::field_default( 'analytics_key' ),
				)
			)
			->add_field(
				'integration_keys',
				array(
					'id'         => 'webhook_secret',
					'tier'       => 'free',
					'type'       => 'text',
					'label'      => __( 'Webhook Signing Secret', 'import-export-menu' ),
					'input_type' => 'password',
					'default'    => self::field_default( 'webhook_secret' ),
				)
			);
	}

	/**
	 * Populate the Custom CSS/JS tab with two code editors.
	 *
	 * @param Framework $framework Framework instance.
	 */
	private function register_custom_assets_sections( Framework $framework ): void {
		$framework->add_section(
			'custom_assets',
			'custom_css',
			__( 'Custom CSS', 'import-export-menu' ),
			__( 'Stylesheet appended to every admin page. Be specific with selectors.', 'import-export-menu' )
		);
		$framework->add_field(
			'custom_css',
			array(
				'id'       => 'custom_css',
				'tier'     => 'pro',
				'type'     => 'code',
				'label'    => __( 'Stylesheet', 'import-export-menu' ),
				'language' => 'css',
				'rows'     => 10,
				'default'  => self::field_default( 'custom_css' ),
			)
		);

		$framework->add_section(
			'custom_assets',
			'custom_js',
			__( 'Custom JavaScript', 'import-export-menu' ),
			__( 'Inline script enqueued in the admin footer. Wrap in IIFE to avoid leaking globals.', 'import-export-menu' )
		);
		$framework->add_field(
			'custom_js',
			array(
				'id'       => 'custom_js',
				'tier'     => 'pro',
				'type'     => 'code',
				'label'    => __( 'Script', 'import-export-menu' ),
				'language' => 'javascript',
				'rows'     => 10,
				'default'  => self::field_default( 'custom_js' ),
			)
		);
	}

	/**
	 * Populate the Appearance group with typography + spacing + repeater +
	 * conditional-fields examples so every iteration-3 control has a visible
	 * sample in the dashboard.
	 *
	 * @param Framework $framework Framework instance.
	 */
	private function register_appearance_sections( Framework $framework ): void {
		$framework
			->add_tab( 'theme', __( 'Theme', 'import-export-menu' ), 'appearance', 10 )
			->add_tab( 'navigation', __( 'Navigation', 'import-export-menu' ), 'appearance', 20 )
			// Conditional tab — only appears while the Theme tab's "experimental"
			// toggle is on. Demonstrates tab-level conditions; the controlling
			// field lives in another tab of the same group, which is fine because
			// every tab in the active group is rendered up-front.
			->add_tab(
				'labs',
				__( 'Labs', 'import-export-menu' ),
				'appearance',
				30,
				'',
				array(
					'condition' => array(
						'field' => 'experimental_theme',
						'value' => 1,
					),
				)
			);

		$framework->add_section(
			'theme',
			'theme_typography',
			__( 'Typography', 'import-export-menu' ),
			__( 'Composite control — family, size, weight, and line-height in one go.', 'import-export-menu' )
		);
		$framework
			->add_field(
				'theme_typography',
				array(
					'id'      => 'heading_typography',
					'tier'    => 'free',
					'type'    => 'typography',
					'label'   => __( 'Heading style', 'import-export-menu' ),
					'default' => self::field_default( 'heading_typography' ),
				)
			)
			->add_field(
				'theme_typography',
				array(
					'id'          => 'experimental_theme',
					'tier'        => 'free',
					'type'        => 'toggle',
					'label'       => __( 'Enable experimental theme features', 'import-export-menu' ),
					'description' => __( 'Reveals the "Experimental" section below and an extra "Labs" tab — demonstrates conditional sections and tabs.', 'import-export-menu' ),
					'default'     => self::field_default( 'experimental_theme' ),
				)
			);

		$framework->add_section(
			'theme',
			'theme_spacing',
			__( 'Spacing', 'import-export-menu' ),
			__( 'Box model spacing with optional uniform mode — click the link icon to sync all four sides.', 'import-export-menu' )
		);
		$framework
			->add_field(
				'theme_spacing',
				array(
					'id'         => 'card_padding',
					'tier'       => 'free',
					'type'       => 'spacing',
					'label'      => __( 'Card padding', 'import-export-menu' ),
					'min'        => 0,
					'max'        => 80,
					'allow_link' => true,
					'default'    => self::field_default( 'card_padding' ),
				)
			)
			->add_field(
				'theme_spacing',
				array(
					'id'          => 'card_padding_advanced',
					'tier'        => 'free',
					'type'        => 'toggle',
					'label'       => __( 'Show advanced spacing', 'import-export-menu' ),
					'description' => __( 'Reveals an extra margin control — demonstrates conditional fields.', 'import-export-menu' ),
					'default'     => self::field_default( 'card_padding_advanced' ),
				)
			)
			->add_field(
				'theme_spacing',
				array(
					'id'          => 'card_margin',
					'tier'        => 'free',
					'type'        => 'spacing',
					'label'       => __( 'Card margin', 'import-export-menu' ),
					'description' => __( 'Visible only when the toggle above is on.', 'import-export-menu' ),
					'condition'   => array(
						'field' => 'card_padding_advanced',
						'value' => 1,
					),
					'default'     => self::field_default( 'card_margin' ),
				)
			);

		// Conditional section — the whole card is shown/hidden by the toggle in
		// the Typography section above. Demonstrates section-level conditions.
		$framework->add_section(
			'theme',
			'theme_experimental',
			__( 'Experimental', 'import-export-menu' ),
			__( 'Visible only while "experimental theme features" is enabled.', 'import-export-menu' ),
			30,
			array(
				'condition' => array(
					'field' => 'experimental_theme',
					'value' => 1,
				),
			)
		);
		$framework->add_field(
			'theme_experimental',
			array(
				'id'      => 'accent_intensity',
				'tier'    => 'free',
				'type'    => 'slider',
				'label'   => __( 'Accent intensity', 'import-export-menu' ),
				'min'     => 0,
				'max'     => 100,
				'step'    => 1,
				'unit'    => '%',
				'default' => self::field_default( 'accent_intensity' ),
			)
		);

		// Section under the conditional "Labs" tab registered above.
		$framework->add_section(
			'labs',
			'labs_general',
			__( 'Labs', 'import-export-menu' ),
			__( 'Bleeding-edge options. The entire tab is gated on the experimental toggle.', 'import-export-menu' )
		);
		$framework->add_field(
			'labs_general',
			array(
				'id'      => 'labs_layout_engine',
				'tier'    => 'free',
				'type'    => 'radio',
				'label'   => __( 'Layout engine', 'import-export-menu' ),
				'default' => self::field_default( 'labs_layout_engine' ),
				'choices' => array(
					'flex' => __( 'Flexbox', 'import-export-menu' ),
					'grid' => __( 'CSS Grid', 'import-export-menu' ),
				),
			)
		);

		$framework->add_section(
			'navigation',
			'nav_links',
			__( 'Navigation Links', 'import-export-menu' ),
			__( 'Repeater — add as many rows as needed; each is a self-contained group of sub-fields.', 'import-export-menu' )
		);
		$framework->add_field(
			'nav_links',
			array(
				'id'        => 'menu_links',
				'tier'      => 'free',
				'type'      => 'repeater',
				'label'     => __( 'Menu links', 'import-export-menu' ),
				'row_label' => __( 'Link', 'import-export-menu' ),
				'add_label' => __( 'Add link', 'import-export-menu' ),
				'min_rows'  => 0,
				'max_rows'  => 10,
				'default'   => self::field_default( 'menu_links' ),
				'fields'    => array(
					array(
						'id'      => 'label',
						'type'    => 'text',
						'label'   => __( 'Label', 'import-export-menu' ),
						'default' => '',
					),
					array(
						'id'         => 'url',
						'type'       => 'text',
						'input_type' => 'url',
						'label'      => __( 'URL', 'import-export-menu' ),
						'default'    => '',
					),
					array(
						'id'      => 'target',
						'type'    => 'select',
						'label'   => __( 'Open in', 'import-export-menu' ),
						'default' => '_self',
						'choices' => array(
							'_self'  => __( 'Same tab', 'import-export-menu' ),
							'_blank' => __( 'New tab', 'import-export-menu' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Populate the conditional "Experimental Lab" sidebar group.
	 *
	 * The group itself is gated on the `experimental_theme` toggle (see
	 * {@see self::register_groups()}); this just gives it a tab, section, and
	 * field so it is a real destination once revealed.
	 *
	 * @param Framework $framework Framework instance.
	 */
	private function register_experimental_sections( Framework $framework ): void {
		$framework->add_tab( 'lab_overview', __( 'Overview', 'import-export-menu' ), 'experimental', 10 );
		$framework->add_section(
			'lab_overview',
			'lab_settings',
			__( 'Lab Settings', 'import-export-menu' ),
			__( 'This whole sidebar group only appears while experimental theme features are enabled.', 'import-export-menu' )
		);
		$framework->add_field(
			'lab_settings',
			array(
				'id'          => 'lab_telemetry',
				'tier'        => 'free',
				'type'        => 'toggle',
				'label'       => __( 'Share anonymous lab telemetry', 'import-export-menu' ),
				'description' => __( 'Help us tune experimental features by sending anonymous usage data.', 'import-export-menu' ),
				'default'     => self::field_default( 'lab_telemetry' ),
			)
		);
	}
}
