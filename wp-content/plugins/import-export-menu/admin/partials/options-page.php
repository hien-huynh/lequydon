<?php
/**
 * Options page template.
 *
 * Variables provided by {@see \ImportExportMenu\Options\PageRenderer::render()}:
 *
 * @var \ImportExportMenu\Options\Registry     $registry
 * @var \ImportExportMenu\Options\PageRenderer $renderer
 * @var array<int,\ImportExportMenu\Options\Group> $groups
 * @var array<int,\ImportExportMenu\Options\Tab>   $tabs
 * @var string                          $active_group
 * @var string                          $active_tab
 * @var bool                            $has_sidebar
 * @var string                          $option_group
 * @var string                          $option_name
 * @var string                          $page_slug
 * @var array<string,mixed>             $values
 * @var string                          $reset_url
 *
 * @package Import_Export_Menu
 */

defined( 'ABSPATH' ) || exit;

$import_export_menu_active_group_obj = null;
foreach ( $groups as $import_export_menu_group_obj ) {
	if ( $import_export_menu_group_obj->id() === $active_group ) {
		$import_export_menu_active_group_obj = $import_export_menu_group_obj;
		break;
	}
}

$import_export_menu_active_tab_obj = null;
foreach ( $tabs as $import_export_menu_tab_obj ) {
	if ( $import_export_menu_tab_obj->id() === $active_tab ) {
		$import_export_menu_active_tab_obj = $import_export_menu_tab_obj;
		break;
	}
}

$import_export_menu_page_url = static function ( array $args ) use ( $page_slug ): string {
	return add_query_arg(
		array_merge( array( 'page' => $page_slug ), $args ),
		admin_url( 'admin.php' )
	);
};

$import_export_menu_content_class = 'import-export-menu-options' . ( $has_sidebar ? ' import-export-menu-options--with-sidebar' : '' );
?>
<div class="wrap import-export-menu-wrap">
	<h1 class="import-export-menu-wrap__title"><?php echo esc_html__( 'Settings', 'import-export-menu' ); ?></h1>

	<?php settings_errors( $option_name ); ?>

	<div class="<?php echo esc_attr( $import_export_menu_content_class ); ?>">

		<?php if ( $has_sidebar ) : ?>
			<aside class="import-export-menu-options__sidebar" aria-label="<?php echo esc_attr__( 'Plugin groups', 'import-export-menu' ); ?>">
				<div class="import-export-menu-options__brand">
					<span class="import-export-menu-options__brand-icon dashicons dashicons-admin-plugins" aria-hidden="true"></span>
					<span class="import-export-menu-options__brand-name"><?php echo esc_html( defined( 'IMPORT_EXPORT_MENU_NAME' ) ? IMPORT_EXPORT_MENU_NAME : get_admin_page_title() ); ?></span>
					<span class="import-export-menu-options__brand-version">v<?php echo esc_html( defined( 'IMPORT_EXPORT_MENU_VERSION' ) ? IMPORT_EXPORT_MENU_VERSION : '' ); ?></span>
				</div>
				<nav class="import-export-menu-options__group-nav">
					<?php foreach ( $groups as $import_export_menu_group_item ) : ?>
						<?php
						$import_export_menu_is_active = $import_export_menu_group_item->id() === $active_group;
						$import_export_menu_group_url = $import_export_menu_page_url( array( 'group' => $import_export_menu_group_item->id() ) );
						?>
						<a
							href="<?php echo esc_url( $import_export_menu_group_url ); ?>"
							class="import-export-menu-options__group-link<?php echo $import_export_menu_is_active ? ' is-active' : ''; ?>"
							<?php echo $import_export_menu_is_active ? 'aria-current="page"' : ''; ?>
							<?php echo $renderer->condition_attributes( $import_export_menu_group_item->condition() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped in condition_attributes(). ?>
						>
							<?php if ( '' !== $import_export_menu_group_item->icon() ) : ?>
								<span class="dashicons dashicons-<?php echo esc_attr( $import_export_menu_group_item->icon() ); ?>" aria-hidden="true"></span>
							<?php else : ?>
								<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
							<?php endif; ?>
							<span class="import-export-menu-options__group-label"><?php echo esc_html( $import_export_menu_group_item->label() ); ?></span>
						</a>
					<?php endforeach; ?>
				</nav>
				<div class="import-export-menu-options__sidebar-footer">
					<?php
					/**
					 * Slot for plugin-specific sidebar footer content (e.g. premium upsell).
					 *
					 * Consumers should output a self-contained block; the framework
					 * provides no default markup here.
					 */
					do_action( 'import_export_menu_options_sidebar_footer' );
					?>
					<div class="import-export-menu-upgrade-box">
						<span class="dashicons dashicons-cart import-export-menu-upgrade-box__icon" aria-hidden="true"></span>
						<p class="import-export-menu-upgrade-box__label"><?php echo esc_html__( 'Get Premium', 'import-export-menu' ); ?></p>
						<p class="import-export-menu-upgrade-box__text"><?php echo esc_html__( 'Unlock all advanced features', 'import-export-menu' ); ?></p>
						<a href="#" target="_blank" class="import-export-menu-upgrade-box__button"><?php echo esc_html__( 'Upgrade Now', 'import-export-menu' ); ?></a>
					</div>
				</div>
			</aside>
		<?php endif; ?>

		<div class="import-export-menu-options__content">

			<?php if ( null !== $import_export_menu_active_group_obj && $import_export_menu_active_group_obj->is_custom() ) : ?>

				<section class="import-export-menu-options__panel import-export-menu-options__panel--custom">
					<header class="import-export-menu-options__header">
						<div class="import-export-menu-options__header-top">
							<h2 class="import-export-menu-options__title"><?php echo esc_html( $import_export_menu_active_group_obj->label() ); ?></h2>
						</div>
					</header>
					<div class="import-export-menu-options__sections">
						<?php
						/**
						 * Render the body of a custom (action) group.
						 *
						 * Fires in place of the settings form for groups registered
						 * with `'custom' => true`; the owning module echoes its own
						 * panel (which may contain a <form> posting to admin-post.php).
						 *
						 * @since 1.1.0
						 */
						do_action( 'import_export_menu_options_render_group_' . $active_group );
						?>
					</div>
				</section>

			<?php else : ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="import-export-menu-options__form">
				<?php settings_fields( $option_group ); ?>
				<input type="hidden" name="_active_group" value="<?php echo esc_attr( $active_group ); ?>" />
				<input type="hidden" name="_active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />

				<header class="import-export-menu-options__header">
					<div class="import-export-menu-options__header-top">
						<h2 class="import-export-menu-options__title">
							<?php
							echo esc_html(
								null !== $import_export_menu_active_group_obj && '' !== $import_export_menu_active_group_obj->label()
									? $import_export_menu_active_group_obj->label()
									: __( 'Settings', 'import-export-menu' )
							);
							?>
						</h2>
						<div class="import-export-menu-options__header-actions">
							<a
								href="<?php echo esc_url( $reset_url ); ?>"
								class="import-export-menu-options__button button button-outline import-export-menu-options__reset"
								data-import-export-menu-confirm="<?php echo esc_attr__( 'Reset all settings to their defaults? This cannot be undone.', 'import-export-menu' ); ?>"
								data-import-export-menu-confirm-title="<?php echo esc_attr__( 'Reset settings?', 'import-export-menu' ); ?>"
								data-import-export-menu-confirm-ok="<?php echo esc_attr__( 'Reset Defaults', 'import-export-menu' ); ?>"
								data-import-export-menu-confirm-cancel="<?php echo esc_attr__( 'Cancel', 'import-export-menu' ); ?>"
								data-import-export-menu-confirm-variant="danger"
							>
								<?php echo esc_html__( 'Reset Defaults', 'import-export-menu' ); ?>
							</a>
							<?php
							submit_button(
								__( 'Save Changes', 'import-export-menu' ),
								'primary import-export-menu-options__save',
								'submit-header',
								false
							);
							?>
						</div>
					</div>
					<?php if ( count( $tabs ) > 1 ) : ?>
						<nav class="import-export-menu-options__tabs" aria-label="<?php echo esc_attr__( 'Settings sections', 'import-export-menu' ); ?>">
							<?php foreach ( $tabs as $import_export_menu_tab_item ) : ?>
								<?php
								$import_export_menu_tab_url   = $import_export_menu_page_url(
									array(
										'group' => $active_group,
										'tab'   => $import_export_menu_tab_item->id(),
									)
								);
								$import_export_menu_is_active = $import_export_menu_tab_item->id() === $active_tab;
								?>
								<a
									href="<?php echo esc_url( $import_export_menu_tab_url ); ?>"
									class="import-export-menu-options__tab<?php echo $import_export_menu_is_active ? ' is-active' : ''; ?>"
									data-tab="<?php echo esc_attr( $import_export_menu_tab_item->id() ); ?>"
									<?php echo $import_export_menu_is_active ? 'aria-current="page"' : ''; ?>
									<?php echo $renderer->condition_attributes( $import_export_menu_tab_item->condition() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped in condition_attributes(). ?>
								>
									<?php echo esc_html( $import_export_menu_tab_item->label() ); ?>
								</a>
							<?php endforeach; ?>
						</nav>
					<?php endif; ?>
				</header>

				<div class="import-export-menu-options__sections">
					<?php
					// Explain the dependency when the active group is gated on a
					// control that isn't on this page (e.g. it lives in another
					// group). The controls still render below — this is guidance,
					// not a lock.
					if ( null !== $import_export_menu_active_group_obj ) {
						$renderer->render_dependency_notice( $import_export_menu_active_group_obj->condition() );
					}
					?>
					<?php if ( empty( $tabs ) ) : ?>
						<div class="import-export-menu-options__empty">
							<p>
								<?php echo esc_html__( 'Nothing has been registered for this view yet.', 'import-export-menu' ); ?>
							</p>
						</div>
					<?php else : ?>
						<?php
						/*
						 * Render every tab in the active group up front so the JS can
						 * switch between them with show/hide instead of a full reload.
						 * Inactive panels are kept in the layout but visually hidden via
						 * CSS (so embedded editors size correctly); without JS the tab
						 * links keep real hrefs and fall back to a normal page load.
						 */
						foreach ( $tabs as $import_export_menu_tab_panel ) :
							$import_export_menu_panel_tab_id   = $import_export_menu_tab_panel->id();
							$import_export_menu_panel_active   = $import_export_menu_panel_tab_id === $active_tab;
							$import_export_menu_panel_sections = $registry->sections_for( $import_export_menu_panel_tab_id );
							?>
							<div
								class="import-export-menu-options__tab-panel<?php echo $import_export_menu_panel_active ? ' is-active' : ''; ?>"
								data-tab-panel="<?php echo esc_attr( $import_export_menu_panel_tab_id ); ?>"
								<?php echo $renderer->condition_attributes( $import_export_menu_tab_panel->condition() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped in condition_attributes(). ?>
							>
								<?php
								// Same guidance, scoped to a conditional sub-tab the
								// user has landed on while its rule is unmet.
								$renderer->render_dependency_notice( $import_export_menu_tab_panel->condition() );
								?>
								<?php if ( empty( $import_export_menu_panel_sections ) ) : ?>
									<div class="import-export-menu-options__empty">
										<p>
											<?php echo esc_html__( 'Nothing has been registered for this view yet.', 'import-export-menu' ); ?>
										</p>
									</div>
								<?php else : ?>
									<?php foreach ( $import_export_menu_panel_sections as $import_export_menu_section ) : ?>
										<section
										class="import-export-menu-options__section"
										id="section-<?php echo esc_attr( $import_export_menu_section->id() ); ?>"
										<?php echo $renderer->condition_attributes( $import_export_menu_section->condition() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped in condition_attributes(). ?>
									>
											<header class="import-export-menu-options__section-header">
												<h3 class="import-export-menu-options__section-title"><?php echo esc_html( $import_export_menu_section->title() ); ?></h3>
												<?php if ( '' !== $import_export_menu_section->description() ) : ?>
													<p class="import-export-menu-options__section-description">
														<?php echo esc_html( $import_export_menu_section->description() ); ?>
													</p>
												<?php endif; ?>
											</header>
											<div class="import-export-menu-options__section-body">
												<?php foreach ( $registry->fields_for( $import_export_menu_section->id() ) as $import_export_menu_field ) : ?>
													<?php
													$import_export_menu_value = array_key_exists( $import_export_menu_field->id(), $values )
														? $values[ $import_export_menu_field->id() ]
														: $import_export_menu_field->default_value();
													$renderer->render_field_row( $import_export_menu_field, $import_export_menu_value );
													?>
												<?php endforeach; ?>
											</div>
										</section>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<div class="import-export-menu-options__footer">
					<div class="import-export-menu-options__footer-actions">
						<a
							href="<?php echo esc_url( $reset_url ); ?>"
							class="import-export-menu-options__button button button-outline import-export-menu-options__reset"
							data-import-export-menu-confirm="<?php echo esc_attr__( 'Reset all settings to their defaults? This cannot be undone.', 'import-export-menu' ); ?>"
							data-import-export-menu-confirm-title="<?php echo esc_attr__( 'Reset settings?', 'import-export-menu' ); ?>"
							data-import-export-menu-confirm-ok="<?php echo esc_attr__( 'Reset Defaults', 'import-export-menu' ); ?>"
							data-import-export-menu-confirm-cancel="<?php echo esc_attr__( 'Cancel', 'import-export-menu' ); ?>"
							data-import-export-menu-confirm-variant="danger"
						>
							<?php echo esc_html__( 'Reset Defaults', 'import-export-menu' ); ?>
						</a>
						<?php
						submit_button(
							__( 'Save Changes', 'import-export-menu' ),
							'primary import-export-menu-options__save',
							'submit',
							false
						);
						?>
					</div>
				</div>
			</form>

			<?php endif; ?>

		</div>
	</div>
</div>
