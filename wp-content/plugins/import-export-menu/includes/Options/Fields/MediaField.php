<?php
/**
 * Generic file uploader backed by the WordPress Media Library.
 *
 * @package Import_Export_Menu
 * @since   2.3.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Stores an attachment ID; renders a "Choose File" button that opens the WP
 * Media frame, plus a filename preview and a "Remove" button when a file is
 * already selected.
 *
 * Config extras (all optional):
 *   - frame_title    (string) — title of the media modal.
 *   - frame_button   (string) — label of the modal's confirm button.
 *   - allowed_types  (string) — MIME prefix passed to the media library
 *                                (e.g. `application/pdf`); defaults to all
 *                                non-image types.
 *
 * @since 2.3.0
 */
class MediaField extends AbstractField {

	protected const TYPE = 'media';

	/**
	 * Default value coerced to a positive integer (or 0 = no selection).
	 *
	 * @return int
	 */
	public function default_value(): int {
		return is_numeric( $this->default ) ? max( 0, (int) $this->default ) : 0;
	}

	/**
	 * Render the picker control.
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current attachment ID.
	 */
	public function render( string $name_attr, $value ): void {
		$attachment_id = is_numeric( $value ) ? max( 0, (int) $value ) : 0;
		$filename      = '';
		$file_url      = '';
		if ( $attachment_id > 0 ) {
			$file_url = (string) wp_get_attachment_url( $attachment_id );
			if ( '' !== $file_url ) {
				$filename = basename( $file_url );
			}
		}
		$has_selection   = $attachment_id > 0;
		$empty_label     = __( 'No file chosen', 'import-export-menu' );
		$frame_title     = isset( $this->config['frame_title'] ) ? (string) $this->config['frame_title'] : __( 'Select File', 'import-export-menu' );
		$frame_button    = isset( $this->config['frame_button'] ) ? (string) $this->config['frame_button'] : __( 'Use this file', 'import-export-menu' );
		$allowed_types   = isset( $this->config['allowed_types'] ) ? (string) $this->config['allowed_types'] : '';
		$wrapper_classes = 'import-export-menu-media' . ( $has_selection ? ' has-selection' : '' );
		?>
		<div
			class="<?php echo esc_attr( $wrapper_classes ); ?>"
			data-frame-title="<?php echo esc_attr( $frame_title ); ?>"
			data-frame-button="<?php echo esc_attr( $frame_button ); ?>"
			data-allowed-types="<?php echo esc_attr( $allowed_types ); ?>"
		>
			<input
				type="hidden"
				id="<?php echo esc_attr( $this->id ); ?>"
				name="<?php echo esc_attr( $name_attr ); ?>"
				value="<?php echo esc_attr( (string) $attachment_id ); ?>"
				class="import-export-menu-media__input"
			/>
			<div class="import-export-menu-media__row">
				<button type="button" class="import-export-menu-options__button button button import-export-menu-media__choose">
					<?php echo esc_html__( 'Choose File', 'import-export-menu' ); ?>
				</button>
				<span
					class="import-export-menu-media__filename"
					aria-live="polite"
					data-empty="<?php echo esc_attr( $empty_label ); ?>"
				>
					<?php
					echo '' !== $filename
						? esc_html( $filename )
						: esc_html( $empty_label );
					?>
				</span>
				<button
					type="button"
					class="button-link import-export-menu-media__remove"
					<?php echo $has_selection ? '' : 'hidden'; ?>
				>
					<?php echo esc_html__( 'Remove', 'import-export-menu' ); ?>
				</button>
			</div>
		</div>
		<?php
		$this->render_description();
	}

	/**
	 * Sanitize the submitted attachment ID.
	 *
	 * Verifies the post exists and is of type `attachment`; anything else
	 * (deleted, wrong post type, non-numeric) collapses to 0 = no selection.
	 *
	 * @param mixed $raw Raw POST value.
	 * @return int
	 */
	public function sanitize( $raw ): int {
		if ( ! is_numeric( $raw ) ) {
			return 0;
		}
		$id = (int) $raw;
		if ( $id < 1 ) {
			return 0;
		}
		$post = get_post( $id );
		if ( null === $post || 'attachment' !== $post->post_type ) {
			return 0;
		}
		return $id;
	}
}
