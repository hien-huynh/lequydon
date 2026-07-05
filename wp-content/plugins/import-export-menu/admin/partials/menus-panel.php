<?php
/**
 * Menus panel: overview of every menu with per-menu actions.
 *
 * Variables provided by {@see \ImportExportMenu\Modules\Menus\MenusModule::render_panel()}:
 *
 * @var array<int,array<string,mixed>> $rows             One display row per menu.
 * @var string                         $duplicate_action admin-post action for duplication.
 * @var string                         $delete_action    admin-post action for deletion.
 * @var array<string,mixed>|null       $notice           Post-redirect notice, if any.
 *
 * @package Import_Export_Menu
 */

defined( 'ABSPATH' ) || exit;

$import_export_menu_datetime_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
?>
<?php if ( is_array( $notice ) ) : ?>
	<?php if ( 'success' === ( $notice['type'] ?? '' ) ) : ?>
		<div class="import-export-menu-notice import-export-menu-notice--success import-export-menu-notice--dismissible" role="status" aria-live="polite" data-dismiss-label="<?php echo esc_attr__( 'Dismiss this notice', 'import-export-menu' ); ?>">
			<span class="import-export-menu-notice__icon dashicons dashicons-yes-alt" aria-hidden="true"></span>
			<div class="import-export-menu-notice__body">
				<div class="import-export-menu-notice__message"><p><?php echo esc_html( (string) ( $notice['message'] ?? '' ) ); ?></p></div>
			</div>
		</div>
	<?php else : ?>
		<div class="import-export-menu-notice import-export-menu-notice--error import-export-menu-notice--dismissible" role="alert" data-dismiss-label="<?php echo esc_attr__( 'Dismiss this notice', 'import-export-menu' ); ?>">
			<span class="import-export-menu-notice__icon dashicons dashicons-warning" aria-hidden="true"></span>
			<div class="import-export-menu-notice__body">
				<div class="import-export-menu-notice__message"><p><?php echo esc_html( (string) ( $notice['message'] ?? '' ) ); ?></p></div>
			</div>
		</div>
	<?php endif; ?>
<?php endif; ?>

<section class="import-export-menu-options__section import-export-menu-menus" id="section-menus">
	<header class="import-export-menu-options__section-header">
		<h3 class="import-export-menu-options__section-title"><?php echo esc_html__( 'Your menus', 'import-export-menu' ); ?></h3>
		<p class="import-export-menu-options__section-description">
			<?php echo esc_html__( 'Every navigation menu on your site. Duplicate a menu to experiment safely — the copy keeps its items, structure, and visibility rules.', 'import-export-menu' ); ?>
		</p>
	</header>
	<div class="import-export-menu-options__section-body">
		<?php if ( empty( $rows ) ) : ?>
			<div class="import-export-menu-notice import-export-menu-notice--info" role="status">
				<span class="import-export-menu-notice__icon dashicons dashicons-info" aria-hidden="true"></span>
				<div class="import-export-menu-notice__body">
					<div class="import-export-menu-notice__message">
						<p><?php echo esc_html__( 'No navigation menus yet. Create one under Appearance → Menus, then manage it from here.', 'import-export-menu' ); ?></p>
					</div>
				</div>
			</div>
		<?php else : ?>
			<table class="import-export-menu-menus__table">
				<caption class="screen-reader-text"><?php echo esc_html__( 'List of navigation menus', 'import-export-menu' ); ?></caption>
				<thead>
					<tr>
						<th scope="col" class="import-export-menu-menus__col-id"><?php echo esc_html__( 'ID', 'import-export-menu' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Menu', 'import-export-menu' ); ?></th>
						<th scope="col" class="import-export-menu-menus__col-items"><?php echo esc_html__( 'Items', 'import-export-menu' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Locations', 'import-export-menu' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Last modified', 'import-export-menu' ); ?></th>
						<th scope="col" class="import-export-menu-menus__col-actions" style="text-align: right;"><?php echo esc_html__( 'Actions', 'import-export-menu' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $import_export_menu_row ) : ?>
						<?php $import_export_menu_locations = (array) $import_export_menu_row['locations']; ?>
						<tr>
							<td class="import-export-menu-menus__col-id">
								<span class="import-export-menu-menus__id">#<?php echo esc_html( (string) $import_export_menu_row['id'] ); ?></span>
							</td>
							<td class="import-export-menu-menus__name">
								<span class="import-export-menu-menus__name-text"><?php echo esc_html( (string) $import_export_menu_row['name'] ); ?></span>
							</td>
							<td class="import-export-menu-menus__col-items">
								<span class="import-export-menu-menus__count"><?php echo esc_html( number_format_i18n( (int) $import_export_menu_row['count'] ) ); ?></span>
							</td>
							<td>
								<?php if ( empty( $import_export_menu_locations ) ) : ?>
									<span class="import-export-menu-menus__muted" aria-hidden="true">—</span>
									<span class="screen-reader-text"><?php echo esc_html__( 'Not assigned', 'import-export-menu' ); ?></span>
								<?php else : ?>
									<span class="import-export-menu-menus__chips">
										<?php foreach ( $import_export_menu_locations as $import_export_menu_location ) : ?>
											<span class="import-export-menu-menus__chip"><?php echo esc_html( (string) $import_export_menu_location ); ?></span>
										<?php endforeach; ?>
									</span>
								<?php endif; ?>
							</td>
							<td class="import-export-menu-menus__modified">
								<?php
								$import_export_menu_modified = $import_export_menu_row['modified'];
								if ( null === $import_export_menu_modified ) {
									echo '<span class="import-export-menu-menus__muted" aria-hidden="true">—</span><span class="screen-reader-text">' . esc_html__( 'No items', 'import-export-menu' ) . '</span>';
								} else {
									$import_export_menu_ts = strtotime( (string) $import_export_menu_modified . ' UTC' );
									echo esc_html( false === $import_export_menu_ts ? (string) $import_export_menu_modified : date_i18n( $import_export_menu_datetime_format, $import_export_menu_ts ) );
								}
								?>
							</td>
							<td class="import-export-menu-menus__actions">
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="import-export-menu-menus__duplicate-form">
									<input type="hidden" name="action" value="<?php echo esc_attr( $duplicate_action ); ?>" />
									<input type="hidden" name="menu_id" value="<?php echo esc_attr( (string) $import_export_menu_row['id'] ); ?>" />
									<?php wp_nonce_field( $duplicate_action ); ?>
									<button
										type="submit"
										class="button button-primary import-export-menu-menus__duplicate"
										aria-label="
										<?php
										echo esc_attr(
											sprintf(
												/* translators: %s: menu name. */
												__( 'Duplicate %s', 'import-export-menu' ),
												(string) $import_export_menu_row['name']
											)
										);
										?>
										"
									>
										<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
										<?php echo esc_html__( 'Duplicate', 'import-export-menu' ); ?>
									</button>
								</form>
								<a
									class="button button-outline import-export-menu-menus__edit"
									href="<?php echo esc_url( (string) $import_export_menu_row['edit_url'] ); ?>"
									aria-label="
									<?php
									echo esc_attr(
										sprintf(
											/* translators: %s: menu name. */
											__( 'Edit %s', 'import-export-menu' ),
											(string) $import_export_menu_row['name']
										)
									);
									?>
									"
								>
									<span class="dashicons dashicons-edit" aria-hidden="true"></span>
									<?php echo esc_html__( 'Edit', 'import-export-menu' ); ?>
								</a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="import-export-menu-menus__delete-form">
									<input type="hidden" name="action" value="<?php echo esc_attr( $delete_action ); ?>" />
									<input type="hidden" name="menu_id" value="<?php echo esc_attr( (string) $import_export_menu_row['id'] ); ?>" />
									<?php wp_nonce_field( $delete_action ); ?>
									<button
										type="submit"
										class="button button-danger import-export-menu-menus__delete"
										aria-label="
										<?php
										echo esc_attr(
											sprintf(
												/* translators: %s: menu name. */
												__( 'Delete %s', 'import-export-menu' ),
												(string) $import_export_menu_row['name']
											)
										);
										?>
										"
										data-import-export-menu-confirm="
										<?php
										echo esc_attr(
											sprintf(
												/* translators: %s: menu name. */
												__( 'Delete the “%s” menu? This permanently removes the menu and all its items, and cannot be undone.', 'import-export-menu' ),
												(string) $import_export_menu_row['name']
											)
										);
										?>
										"
										data-import-export-menu-confirm-title="<?php echo esc_attr__( 'Delete menu?', 'import-export-menu' ); ?>"
										data-import-export-menu-confirm-ok="<?php echo esc_attr__( 'Delete menu', 'import-export-menu' ); ?>"
										data-import-export-menu-confirm-cancel="<?php echo esc_attr__( 'Cancel', 'import-export-menu' ); ?>"
										data-import-export-menu-confirm-variant="danger"
										data-import-export-menu-confirm-submit="1"
									>
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
										<?php echo esc_html__( 'Delete', 'import-export-menu' ); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</section>
