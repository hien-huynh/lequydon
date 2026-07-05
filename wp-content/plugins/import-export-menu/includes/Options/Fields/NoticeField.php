<?php
/**
 * Display-only notice / callout field.
 *
 * @package Import_Export_Menu
 * @since   2.5.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Renders an informational callout box. It does not collect any input — the
 * stored value is always an empty string — so it's safe to drop into a form
 * purely for guidance, deprecation notes, or release warnings.
 *
 * Config extras:
 *   - variant (string) — `info` (default), `success`, `warning`, or `error`.
 *   - title   (string) — bold heading inside the callout.
 *   - message (string) — rich HTML body (filtered through `wp_kses_post`).
 *                        Falls back to the field's `description` when omitted.
 *
 * @since 2.5.0
 */
class NoticeField extends AbstractField {

	protected const TYPE = 'notice';

	/**
	 * Map of variant slug => Dashicon CSS class used for the leading icon.
	 *
	 * @var array<string,string>
	 */
	private const VARIANT_ICONS = array(
		'info'    => 'dashicons-info-outline',
		'success' => 'dashicons-yes-alt',
		'warning' => 'dashicons-warning',
		'error'   => 'dashicons-dismiss',
	);

	/**
	 * Render the callout.
	 *
	 * @param string $name_attr HTML name attribute (unused; notices have no input).
	 * @param mixed  $value     Current stored value (unused).
	 */
	public function render( string $name_attr, $value ): void {
		unset( $name_attr, $value );

		$variant = isset( $this->config['variant'] ) ? (string) $this->config['variant'] : 'info';
		if ( ! array_key_exists( $variant, self::VARIANT_ICONS ) ) {
			$variant = 'info';
		}
		$icon    = self::VARIANT_ICONS[ $variant ];
		$title   = isset( $this->config['title'] ) ? (string) $this->config['title'] : '';
		$message = isset( $this->config['message'] ) ? (string) $this->config['message'] : $this->description;
		?>
		<div class="import-export-menu-notice import-export-menu-notice--<?php echo esc_attr( $variant ); ?>" role="status">
			<span class="import-export-menu-notice__icon dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
			<div class="import-export-menu-notice__body">
				<?php if ( '' !== $title ) : ?>
					<p class="import-export-menu-notice__title"><?php echo esc_html( $title ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $message ) : ?>
					<div class="import-export-menu-notice__message"><?php echo wp_kses_post( $message ); ?></div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Notices never persist a value; always return an empty string.
	 *
	 * @param mixed $raw Raw POST value (ignored).
	 */
	public function sanitize( $raw ): string {
		unset( $raw );
		return '';
	}

	/**
	 * Disable the `render_description()` helper output — notices put their
	 * description text inside the styled body instead.
	 */
	public function description(): string {
		return '';
	}
}
