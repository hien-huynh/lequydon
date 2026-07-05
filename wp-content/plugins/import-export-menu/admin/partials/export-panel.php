<?php
/**
 * Export panel: pick menus and download them as a schema-v1 JSON file.
 *
 * Variables provided by {@see \ImportExportMenu\Modules\Export\ExportModule::render_panel()}:
 *
 * @var \WP_Term[] $menus  Navigation menus available to export.
 * @var string     $action admin-post action name.
 *
 * @package Import_Export_Menu
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="import-export-menu-options__section" id="section-export">
	<header class="import-export-menu-options__section-header">
		<h3 class="import-export-menu-options__section-title"><?php echo esc_html__( 'Export menus', 'import-export-menu' ); ?></h3>
		<p class="import-export-menu-options__section-description">
			<?php echo esc_html__( 'Select the menus to export. The download is a portable JSON file you can import on another site.', 'import-export-menu' ); ?>
		</p>
	</header>
	<div class="import-export-menu-options__section-body">
		<?php if ( empty( $menus ) ) : ?>
			<div class="import-export-menu-notice import-export-menu-notice--info" role="status">
				<span class="import-export-menu-notice__icon dashicons dashicons-info" aria-hidden="true"></span>
				<div class="import-export-menu-notice__body">
					<div class="import-export-menu-notice__message">
						<p><?php echo esc_html__( 'No navigation menus found yet. Create one under Appearance → Menus, then come back to export it.', 'import-export-menu' ); ?></p>
					</div>
				</div>
			</div>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="import-export-menu-export">
				<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>" />
				<?php wp_nonce_field( $action ); ?>

				<div class="import-export-menu-field import-export-menu-field--checkbox" data-field-id="menu_ids">
					<div class="import-export-menu-field__label">
						<label for="menu_ids"><?php echo esc_html__( 'Menus to export', 'import-export-menu' ); ?></label>
					</div>
					<div class="import-export-menu-field__control">
						<fieldset>
							<?php foreach ( $menus as $import_export_menu_menu ) : ?>
								<label class="import-export-menu-export__item" style="display:block;margin-bottom:8px;">
									<input
										type="checkbox"
										name="menu_ids[]"
										value="<?php echo esc_attr( (string) $import_export_menu_menu->term_id ); ?>"
										checked
									/>
									<?php echo esc_html( $import_export_menu_menu->name ); ?>
									<span class="import-export-menu-export__count">
										<?php
										echo esc_html(
											sprintf(
												/* translators: %d: number of items in the menu. */
												_n( '%d item', '%d items', (int) $import_export_menu_menu->count, 'import-export-menu' ),
												(int) $import_export_menu_menu->count
											)
										);
										?>
									</span>
								</label>
							<?php endforeach; ?>
						</fieldset>
					</div>
				</div>

				<?php submit_button( __( 'Download export', 'import-export-menu' ), 'primary import-export-menu-export__submit', 'submit', false ); ?>
			</form>
		<?php endif; ?>
	</div>
</section>
