<?php
/**
 * Import feature module: rebuild navigation menus from an export file.
 *
 * @package Import_Export_Menu
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Import;

use ImportExportMenu\Modules\ModuleInterface;
use ImportExportMenu\Modules\Support\ImportBackup;
use ImportExportMenu\Modules\Support\ImportValidator;
use ImportExportMenu\Modules\Support\MenuImporter;
use ImportExportMenu\Modules\Support\MenuRepository;
use ImportExportMenu\Modules\Support\MenuSerializer;
use ImportExportMenu\Modules\Support\MenuWriter;
use ImportExportMenu\Modules\Support\ReferenceEncoder;
use ImportExportMenu\Modules\Support\ReferenceResolver;
use ImportExportMenu\Options\Framework;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the "Import" dashboard group and runs uploads through the engine.
 *
 * The panel takes an uploaded file or pasted JSON, a mode, and an unresolved
 * fallback. On import it snapshots the current menus for undo, imports for real,
 * and shows a report. A separate action restores the last snapshot.
 *
 * @since 1.1.0
 */
final class ImportModule implements ModuleInterface {

	public const GROUP         = 'import';
	public const ACTION_IMPORT = 'import_export_menu_import';
	public const ACTION_UNDO   = 'import_export_menu_import_undo';

	private const MAX_BYTES = 5242880;

	/**
	 * Free tier — always loaded.
	 */
	public function tier(): string {
		return ModuleInterface::TIER_FREE;
	}

	/**
	 * Register the group, its panel renderer, and the action handlers.
	 */
	public function register_hooks(): void {
		add_action( 'import_export_menu_options_register', array( $this, 'register_group' ) );
		add_action( 'import_export_menu_options_render_group_' . self::GROUP, array( $this, 'render_panel' ) );
		add_action( 'admin_post_' . self::ACTION_IMPORT, array( $this, 'handle_import' ) );
		add_action( 'admin_post_' . self::ACTION_UNDO, array( $this, 'handle_undo' ) );
	}

	/**
	 * Register the custom "Import" sidebar group.
	 *
	 * @param Framework $framework Options framework instance.
	 */
	public function register_group( Framework $framework ): void {
		$framework->add_group(
			self::GROUP,
			__( 'Import', 'import-export-menu' ),
			'upload',
			20,
			array( 'custom' => true )
		);
	}

	/**
	 * Render the import panel (upload/paste form, last report, undo).
	 */
	public function render_panel(): void {
		$import_action = self::ACTION_IMPORT;
		$undo_action   = self::ACTION_UNDO;
		$backup_info   = $this->backup()->info();
		$report        = $this->take_report();
		$error         = $this->take_error();
		$locations     = ( new MenuRepository() )->registered_locations();

		require IMPORT_EXPORT_MENU_PATH . 'admin/partials/import-panel.php';
	}

	/**
	 * Run an import: snapshot for undo, import, then show the report.
	 */
	public function handle_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to import menus.', 'import-export-menu' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION_IMPORT );

		$raw     = $this->read_raw();
		$payload = null;
		if ( '' === trim( $raw ) ) {
			$code = ImportValidator::ERR_EMPTY;
		} else {
			$payload = $this->decode( $raw );
			$code    = ( null === $payload )
				? ImportValidator::ERR_MALFORMED
				: ( new ImportValidator() )->validate( $payload );
		}

		if ( ImportValidator::OK === $code && is_array( $payload ) ) {
			$this->backup()->capture();
			$report = $this->importer()->import( $payload, $this->mode(), false, $this->fallback(), $this->location() );
			$this->store_report( $report );
		} else {
			$this->store_error( ImportValidator::message( $code ) );
		}

		$this->redirect_back();
	}

	/**
	 * Restore the snapshot captured before the last import.
	 */
	public function handle_undo(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to undo an import.', 'import-export-menu' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION_UNDO );

		$report = $this->backup()->restore();
		if ( ! empty( $report ) ) {
			$this->store_report( $report );
		}
		$this->redirect_back();
	}

	// ------------------------------------------------------------------
	// Input. The public handlers above verify the nonce before these run.
	// ------------------------------------------------------------------

	// phpcs:disable WordPress.Security.NonceVerification.Missing

	/**
	 * Read the raw import body from an uploaded file.
	 *
	 * @return string Raw JSON, or an empty string when no usable file was provided.
	 */
	private function read_raw(): string {
		if ( isset( $_FILES['import_file'] ) && is_array( $_FILES['import_file'] ) && UPLOAD_ERR_OK === (int) ( $_FILES['import_file']['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			$tmp  = (string) ( $_FILES['import_file']['tmp_name'] ?? '' );
			$size = (int) ( $_FILES['import_file']['size'] ?? 0 );
			if ( '' !== $tmp && is_uploaded_file( $tmp ) && $size > 0 && $size <= self::MAX_BYTES ) {
				$contents = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a validated upload temp file.
				if ( false !== $contents ) {
					return $contents;
				}
			}
		}

		return '';
	}

	/**
	 * Selected import mode, defaulting to "create".
	 *
	 * @return string
	 */
	private function mode(): string {
		$mode = isset( $_POST['import_mode'] ) ? sanitize_key( (string) wp_unslash( $_POST['import_mode'] ) ) : '';
		return in_array( $mode, array( MenuImporter::MODE_CREATE, MenuImporter::MODE_REPLACE, MenuImporter::MODE_MERGE ), true )
			? $mode
			: MenuImporter::MODE_CREATE;
	}

	/**
	 * Selected unresolved-item fallback, defaulting to "custom".
	 *
	 * @return string
	 */
	private function fallback(): string {
		$fallback = isset( $_POST['import_fallback'] ) ? sanitize_key( (string) wp_unslash( $_POST['import_fallback'] ) ) : '';
		return MenuImporter::FALLBACK_SKIP === $fallback ? MenuImporter::FALLBACK_SKIP : MenuImporter::FALLBACK_CUSTOM;
	}

	/**
	 * Selected theme location to assign, validated against the active theme.
	 *
	 * @return string A registered location slug, or '' when none or invalid.
	 */
	private function location(): string {
		$location = isset( $_POST['import_location'] ) ? sanitize_key( (string) wp_unslash( $_POST['import_location'] ) ) : '';
		if ( '' === $location ) {
			return '';
		}
		$registered = ( new MenuRepository() )->registered_locations();
		return isset( $registered[ $location ] ) ? $location : '';
	}

	// phpcs:enable WordPress.Security.NonceVerification.Missing

	/**
	 * Decode a raw body to a payload, tolerating a BOM or surrounding noise.
	 *
	 * @param string $raw Raw body.
	 * @return mixed Decoded JSON, or null when it cannot be read.
	 */
	private function decode( string $raw ) {
		$raw = trim( $raw );
		if ( 0 === strncmp( $raw, "\xEF\xBB\xBF", 3 ) ) {
			$raw = substr( $raw, 3 );
		}
		if ( '' === $raw ) {
			return null;
		}

		$decoded = json_decode( $raw, true );
		if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
			$start = strpos( $raw, '{' );
			$end   = strrpos( $raw, '}' );
			if ( false !== $start && false !== $end && $end > $start ) {
				$decoded = json_decode( substr( $raw, $start, $end - $start + 1 ), true );
			}
		}

		return $decoded;
	}

	/**
	 * Redirect back to the Import group page.
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

	// ------------------------------------------------------------------
	// User-scoped transients for the post-redirect report/error.
	// ------------------------------------------------------------------

	/**
	 * Persist a report for display after the redirect.
	 *
	 * @param array<string,mixed> $report Import report.
	 */
	private function store_report( array $report ): void {
		set_transient( $this->report_key(), $report, MINUTE_IN_SECONDS );
	}

	/**
	 * Pull and clear the stored report.
	 *
	 * @return array<string,mixed>|null
	 */
	private function take_report(): ?array {
		$report = get_transient( $this->report_key() );
		if ( false === $report ) {
			return null;
		}
		delete_transient( $this->report_key() );
		return is_array( $report ) ? $report : null;
	}

	/**
	 * Persist an error for display after the redirect.
	 *
	 * @param string $message Error message.
	 */
	private function store_error( string $message ): void {
		set_transient( $this->error_key(), $message, MINUTE_IN_SECONDS );
	}

	/**
	 * Pull and clear the stored error.
	 *
	 * @return string
	 */
	private function take_error(): string {
		$error = get_transient( $this->error_key() );
		if ( false === $error ) {
			return '';
		}
		delete_transient( $this->error_key() );
		return (string) $error;
	}

	/**
	 * Transient key for the current user's last import report.
	 *
	 * @return string
	 */
	private function report_key(): string {
		return 'import_export_menu_import_report_' . get_current_user_id();
	}

	/**
	 * Transient key for the current user's last import error.
	 *
	 * @return string
	 */
	private function error_key(): string {
		return 'import_export_menu_import_error_' . get_current_user_id();
	}

	// ------------------------------------------------------------------
	// Service graph.
	// ------------------------------------------------------------------

	/**
	 * Build the import engine.
	 *
	 * @return MenuImporter
	 */
	private function importer(): MenuImporter {
		return new MenuImporter( new MenuRepository(), new MenuWriter(), new ReferenceResolver() );
	}

	/**
	 * Build the backup helper.
	 *
	 * @return ImportBackup
	 */
	private function backup(): ImportBackup {
		$repository = new MenuRepository();
		$writer     = new MenuWriter();

		return new ImportBackup(
			$repository,
			$writer,
			new MenuSerializer( $repository, new ReferenceEncoder() ),
			new MenuImporter( $repository, $writer, new ReferenceResolver() )
		);
	}
}
