<?php
/**
 * Visibility select shown on each item in the native menu editor.
 *
 * Variables provided by {@see \ImportExportMenu\Modules\Conditional\ConditionalModule::render_field()}:
 *
 * @var int                   $item_id Menu item id.
 * @var string                $current Current rule for this item.
 * @var array<string,string>  $choices Rule value => label map.
 * @var string                $field   Request field name carrying the rule.
 *
 * @package Import_Export_Menu
 */

defined( 'ABSPATH' ) || exit;
?>
<p class="field-visibility description description-wide import-export-menu-item-visibility">
	<label for="edit-menu-item-visibility-<?php echo esc_attr( (string) $item_id ); ?>">
		<?php echo esc_html__( 'Visibility', 'import-export-menu' ); ?><br />
		<select
			id="edit-menu-item-visibility-<?php echo esc_attr( (string) $item_id ); ?>"
			name="<?php echo esc_attr( $field ); ?>[<?php echo esc_attr( (string) $item_id ); ?>]"
			class="widefat edit-menu-item-visibility"
		>
			<?php foreach ( $choices as $import_export_menu_value => $import_export_menu_label ) : ?>
				<option value="<?php echo esc_attr( $import_export_menu_value ); ?>" <?php selected( $current, $import_export_menu_value ); ?>>
					<?php echo esc_html( $import_export_menu_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</label>
</p>
<?php
