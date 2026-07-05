<?php
/**
 * Code editor field backed by CodeMirror (via `wp_enqueue_code_editor`).
 *
 * @package Import_Export_Menu
 * @since   2.3.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a `<textarea>` that the matching JS upgrades to a CodeMirror
 * instance keyed by the `language` config (CSS, JS, HTML, PHP…). Stores
 * the raw text — the only sanitization is null-byte removal, since we
 * trust users with `manage_options` and the value is expected to flow
 * back into a `<style>` / `<script>` tag where escaping happens at output.
 *
 * Config extras:
 *   - language (string) — `css` (default), `javascript`, `html`, `php`,
 *                          `json`, or any CodeMirror MIME alias.
 *   - rows     (int)    — visible rows for the unenhanced textarea.
 *
 * @since 2.3.0
 */
class CodeField extends AbstractField {

	protected const TYPE = 'code';

	/**
	 * Render the textarea (CodeMirror upgrades it on load).
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current stored code.
	 */
	public function render( string $name_attr, $value ): void {
		$language = isset( $this->config['language'] ) ? (string) $this->config['language'] : 'css';
		$rows     = isset( $this->config['rows'] ) ? (int) $this->config['rows'] : 10;
		$code     = is_string( $value ) ? $value : '';

		printf(
			'<textarea id="%1$s" name="%2$s" rows="%3$s" class="import-export-menu-code-editor large-text code" data-language="%4$s">%5$s</textarea>',
			esc_attr( $this->id ),
			esc_attr( $name_attr ),
			esc_attr( (string) $rows ),
			esc_attr( $language ),
			esc_textarea( $code )
		);
		$this->render_description();
	}

	/**
	 * Sanitize the submitted code by stripping null bytes and slashes.
	 *
	 * Output rendering must escape the value contextually; we deliberately
	 * keep the stored value lossless so that round-tripping into the editor
	 * preserves whitespace and special characters exactly.
	 *
	 * @param mixed $raw Raw POST value.
	 * @return string
	 */
	public function sanitize( $raw ): string {
		if ( ! is_string( $raw ) ) {
			return '';
		}
		return str_replace( "\0", '', wp_unslash( $raw ) );
	}
}
