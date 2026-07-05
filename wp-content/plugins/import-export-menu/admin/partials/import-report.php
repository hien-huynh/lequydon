<?php
/**
 * Import report partial: per-menu and total outcome of an import or preview.
 *
 * Variables provided by the caller:
 *
 * @var array<string,mixed> $report Import report (menus + totals).
 *
 * @package Import_Export_Menu
 */

defined( 'ABSPATH' ) || exit;

$import_export_menu_menus   = ( isset( $report['menus'] ) && is_array( $report['menus'] ) ) ? $report['menus'] : array();
$import_export_menu_actions = array(
	'created'  => __( 'created', 'import-export-menu' ),
	'replaced' => __( 'replaced', 'import-export-menu' ),
	'merged'   => __( 'merged', 'import-export-menu' ),
);
?>
<div class="import-export-menu-notice import-export-menu-notice--success import-export-menu-notice--dismissible import-export-menu-import-report" role="status" data-dismiss-label="<?php echo esc_attr__( 'Dismiss this notice', 'import-export-menu' ); ?>">
	<span class="import-export-menu-notice__icon dashicons dashicons-yes-alt" aria-hidden="true"></span>
	<div class="import-export-menu-notice__body">
		<p class="import-export-menu-notice__title">
			<?php echo esc_html__( 'Import complete.', 'import-export-menu' ); ?>
		</p>
		<div class="import-export-menu-notice__message">
			<?php if ( empty( $import_export_menu_menus ) ) : ?>
				<p><?php echo esc_html__( 'No menus were found in the file.', 'import-export-menu' ); ?></p>
			<?php else : ?>
				<ul class="import-export-menu-import-report__list">
					<?php foreach ( $import_export_menu_menus as $import_export_menu_menu ) : ?>
						<?php
						$import_export_menu_action = (string) ( $import_export_menu_menu['action'] ?? '' );
						$import_export_menu_label  = $import_export_menu_actions[ $import_export_menu_action ] ?? $import_export_menu_action;
						?>
						<li class="import-export-menu-import-report__item">
							<strong><?php echo esc_html( (string) ( $import_export_menu_menu['name'] ?? '' ) ); ?></strong>
							<span class="import-export-menu-import-report__action"><?php echo esc_html( $import_export_menu_label ); ?></span>
							<span class="import-export-menu-import-report__counts">
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: items added, 2: items remapped, 3: items kept as links, 4: items skipped. */
										__( '%1$d added · %2$d remapped · %3$d as links · %4$d skipped', 'import-export-menu' ),
										(int) ( $import_export_menu_menu['items_created'] ?? 0 ),
										(int) ( $import_export_menu_menu['items_remapped'] ?? 0 ),
										(int) ( $import_export_menu_menu['items_unresolved'] ?? 0 ),
										(int) ( $import_export_menu_menu['items_skipped'] ?? 0 )
									)
								);
								?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php
			$import_export_menu_location = ( isset( $report['location'] ) && is_array( $report['location'] ) ) ? $report['location'] : null;
			if ( null !== $import_export_menu_location ) :
				?>
				<p class="import-export-menu-import-report__location">
					<?php
					if ( ! empty( $import_export_menu_location['assigned'] ) ) {
						echo esc_html(
							sprintf(
								/* translators: 1: menu name, 2: theme location label. */
								__( 'Assigned “%1$s” to the “%2$s” location.', 'import-export-menu' ),
								(string) ( $import_export_menu_location['menu'] ?? '' ),
								(string) ( $import_export_menu_location['label'] ?? '' )
							)
						);
						if ( ! empty( $import_export_menu_location['displaced'] ) ) {
							echo ' ';
							echo esc_html(
								sprintf(
									/* translators: %s: name of the menu removed from the location. */
									__( 'It replaced “%s” there.', 'import-export-menu' ),
									(string) $import_export_menu_location['displaced']
								)
							);
						}
					} else {
						echo esc_html(
							sprintf(
								/* translators: %s: theme location label. */
								__( 'Could not assign the “%s” location — it is not registered by the active theme.', 'import-export-menu' ),
								(string) ( $import_export_menu_location['label'] ?? '' )
							)
						);
					}
					?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</div>
