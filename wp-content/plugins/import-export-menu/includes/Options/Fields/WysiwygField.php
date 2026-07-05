<?php
/**
 * Rich-text WYSIWYG field backed by `wp_editor()` / TinyMCE.
 *
 * @package Import_Export_Menu
 * @since   2.3.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps the WordPress {@see wp_editor()} helper. Sanitization runs the
 * value through {@see wp_kses_post()} so we keep all post-allowed HTML
 * tags but strip anything WordPress itself wouldn't accept in a post body.
 *
 * Config extras:
 *   - rows          (int)  — visible rows; passed straight to wp_editor.
 *   - editor_height (int)  — preferred editor height in pixels.
 *   - media_buttons (bool) — show the "Add Media" button (default false; opt in
 *                            per field when uploads are actually relevant).
 *   - teeny         (bool) — render the simplified toolbar (default false).
 *   - visible       (bool) — when `false`, skip rendering the field row entirely
 *                            (the stored value is preserved). Default true.
 *
 * @since 2.3.0
 */
class WysiwygField extends AbstractField {

	protected const TYPE = 'wysiwyg';

	/**
	 * Render the TinyMCE editor.
	 *
	 * Note: `wp_editor()` echoes its own `<textarea>` and JS bootstrap; we
	 * just wrap it in a div so our SCSS can target the field consistently.
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current stored HTML.
	 */
	public function render( string $name_attr, $value ): void {
		$html          = is_string( $value ) ? $value : '';
		$rows          = isset( $this->config['rows'] ) ? (int) $this->config['rows'] : 8;
		$editor_height = isset( $this->config['editor_height'] ) ? (int) $this->config['editor_height'] : 250;
		$media_buttons = isset( $this->config['media_buttons'] ) ? (bool) $this->config['media_buttons'] : false;
		$teeny         = isset( $this->config['teeny'] ) ? (bool) $this->config['teeny'] : false;

		// `wp_editor` requires alphanumeric + underscore for the editor id.
		$editor_id = preg_replace( '/[^a-z0-9_]/i', '_', $this->id );

		echo '<div class="import-export-menu-wysiwyg">';
		wp_editor(
			$html,
			(string) $editor_id,
			array(
				'textarea_name' => $name_attr,
				'textarea_rows' => $rows,
				'editor_height' => $editor_height,
				'media_buttons' => $media_buttons,
				'teeny'         => $teeny,
				'wpautop'       => true,
			)
		);
		echo '</div>';
		$this->render_description();
	}

	/**
	 * Sanitize via wp_kses_post so post-allowed HTML survives the round trip.
	 *
	 * @param mixed $raw Raw POST value.
	 * @return string
	 */
	public function sanitize( $raw ): string {
		if ( ! is_string( $raw ) ) {
			return '';
		}
		return wp_kses_post( wp_unslash( $raw ) );
	}
}
