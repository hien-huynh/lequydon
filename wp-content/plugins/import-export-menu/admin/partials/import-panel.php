<?php
/**
 * Import panel: upload an export, choose a mode, then import.
 *
 * Variables provided by {@see \ImportExportMenu\Modules\Import\ImportModule::render_panel()}:
 *
 * @var string                    $import_action  admin-post action for the import.
 * @var string                    $undo_action    admin-post action to restore the backup.
 * @var array{captured_at:string,menu_count:int}|null $backup_info Last backup metadata.
 * @var array<string,mixed>|null  $report         Report from the last real import/undo.
 * @var string                    $error          Error from the last import attempt.
 * @var array<string,string>      $locations      Registered theme locations (slug => label).
 *
 * @package Import_Export_Menu
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( '' !== $error ) : ?>
	<div class="import-export-menu-notice import-export-menu-notice--error import-export-menu-notice--dismissible" role="alert" data-dismiss-label="<?php echo esc_attr__( 'Dismiss this notice', 'import-export-menu' ); ?>">
		<span class="import-export-menu-notice__icon dashicons dashicons-warning" aria-hidden="true"></span>
		<div class="import-export-menu-notice__body">
			<div class="import-export-menu-notice__message"><p><?php echo esc_html( $error ); ?></p></div>
		</div>
	</div>
<?php endif; ?>

<?php
if ( is_array( $report ) ) {
	require IMPORT_EXPORT_MENU_PATH . 'admin/partials/import-report.php';
}
?>

<?php if ( is_array( $backup_info ) ) : ?>
	<div class="import-export-menu-notice import-export-menu-notice--warning import-export-menu-import__undo" role="status">
		<span class="import-export-menu-notice__icon dashicons dashicons-backup" aria-hidden="true"></span>
		<div class="import-export-menu-notice__body">
			<div class="import-export-menu-notice__message">
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of menus saved in the backup. */
							_n(
								'A backup of %d menu was saved before your last import.',
								'A backup of %d menus was saved before your last import.',
								(int) $backup_info['menu_count'],
								'import-export-menu'
							),
							(int) $backup_info['menu_count']
						)
					);
					?>
				</p>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="import-export-menu-import__undo-form">
				<input type="hidden" name="action" value="<?php echo esc_attr( $undo_action ); ?>" />
				<?php wp_nonce_field( $undo_action ); ?>
				<button
					type="submit"
					class="button button-outline import-export-menu-import__undo-button"
					data-import-export-menu-confirm="<?php echo esc_attr__( 'Undo the last import? This restores your menus to the saved backup.', 'import-export-menu' ); ?>"
					data-import-export-menu-confirm-title="<?php echo esc_attr__( 'Undo last import?', 'import-export-menu' ); ?>"
					data-import-export-menu-confirm-ok="<?php echo esc_attr__( 'Undo import', 'import-export-menu' ); ?>"
					data-import-export-menu-confirm-cancel="<?php echo esc_attr__( 'Cancel', 'import-export-menu' ); ?>"
					data-import-export-menu-confirm-variant="danger"
					data-import-export-menu-confirm-submit="1"
				>
					<?php echo esc_html__( 'Undo last import', 'import-export-menu' ); ?>
				</button>
			</form>
		</div>
	</div>
<?php endif; ?>

<section class="import-export-menu-options__section" id="section-import">
	<header class="import-export-menu-options__section-header">
		<h3 class="import-export-menu-options__section-title"><?php echo esc_html__( 'Import a menu', 'import-export-menu' ); ?></h3>
		<p class="import-export-menu-options__section-description">
			<?php echo esc_html__( 'Upload an export file, choose how to import, then import.', 'import-export-menu' ); ?>
		</p>
	</header>
	<div class="import-export-menu-options__section-body">
		<form
			method="post"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			enctype="multipart/form-data"
			class="import-export-menu-import"
		>
			<input type="hidden" name="action" value="<?php echo esc_attr( $import_action ); ?>" />
			<?php wp_nonce_field( $import_action ); ?>

			<div class="import-export-menu-field import-export-menu-field--file" data-field-id="import_file">
				<div class="import-export-menu-field__label">
					<label for="import-export-menu-import-file"><?php echo esc_html__( 'Export file (.json)', 'import-export-menu' ); ?></label>
				</div>
				<div class="import-export-menu-field__control">
					<input type="file" id="import-export-menu-import-file" name="import_file" accept="application/json,.json" required />
				</div>
			</div>

			<div class="import-export-menu-field import-export-menu-field--radio" data-field-id="import_mode">
				<div class="import-export-menu-field__label">
					<label for="import_mode"><?php echo esc_html__( 'When a menu of the same name exists', 'import-export-menu' ); ?></label>
				</div>
				<div class="import-export-menu-field__control">
					<fieldset>
						<label style="display:block;margin-bottom:8px;"><input type="radio" name="import_mode" value="create" checked /> <?php echo esc_html__( 'Create a new menu', 'import-export-menu' ); ?></label>
						<label style="display:block;margin-bottom:8px;"><input type="radio" name="import_mode" value="replace" /> <?php echo esc_html__( 'Replace it', 'import-export-menu' ); ?></label>
						<label style="display:block;margin-bottom:8px;"><input type="radio" name="import_mode" value="merge" /> <?php echo esc_html__( 'Merge into it', 'import-export-menu' ); ?></label>
					</fieldset>
				</div>
			</div>

			<div class="import-export-menu-field import-export-menu-field--radio" data-field-id="import_fallback">
				<div class="import-export-menu-field__label">
					<label for="import_fallback"><?php echo esc_html__( 'When a linked item cannot be found', 'import-export-menu' ); ?></label>
				</div>
				<div class="import-export-menu-field__control">
					<fieldset>
						<label style="display:block;margin-bottom:8px;"><input type="radio" name="import_fallback" value="custom" checked /> <?php echo esc_html__( 'Keep it as a custom link', 'import-export-menu' ); ?></label>
						<label style="display:block;margin-bottom:8px;"><input type="radio" name="import_fallback" value="skip" /> <?php echo esc_html__( 'Skip it', 'import-export-menu' ); ?></label>
					</fieldset>
				</div>
			</div>

			<?php if ( ! empty( $locations ) ) : ?>
				<div class="import-export-menu-field import-export-menu-field--select" data-field-id="import_location">
					<div class="import-export-menu-field__label">
						<label for="import-export-menu-import-location"><?php echo esc_html__( 'Assign the imported menu to a theme location', 'import-export-menu' ); ?></label>
					</div>
					<div class="import-export-menu-field__control">
						<select id="import-export-menu-import-location" name="import_location">
							<option value=""><?php echo esc_html__( '— Don’t assign —', 'import-export-menu' ); ?></option>
							<?php foreach ( $locations as $import_export_menu_loc_slug => $import_export_menu_loc_label ) : ?>
								<option value="<?php echo esc_attr( (string) $import_export_menu_loc_slug ); ?>"><?php echo esc_html( (string) $import_export_menu_loc_label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_html__( 'If another menu already uses that location, it will be replaced.', 'import-export-menu' ); ?></p>
					</div>
				</div>
			<?php endif; ?>

			<div class="import-export-menu-import__actions">
				<?php submit_button( __( 'Import', 'import-export-menu' ), 'primary import-export-menu-import__submit', 'submit', false ); ?>
			</div>
		</form>
	</div>
</section>
