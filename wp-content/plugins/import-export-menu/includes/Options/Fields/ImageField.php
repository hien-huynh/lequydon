<?php
/**
 * Image uploader with thumbnail preview.
 *
 * @package Import_Export_Menu
 * @since   2.3.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Specialised {@see MediaField} that restricts the media library to images
 * and renders the current selection as a thumbnail preview.
 *
 * Config extras inherit from MediaField, plus:
 *   - preview_size (string) — image size used for the preview thumbnail
 *                              (default `medium`). Falls back to `full`
 *                              when the requested size is unavailable.
 *
 * @since 2.3.0
 */
class ImageField extends MediaField {

	protected const TYPE = 'image';

	/**
	 * Render the image picker control with thumbnail.
	 *
	 * @param string $name_attr HTML name attribute.
	 * @param mixed  $value     Current attachment ID.
	 */
	public function render( string $name_attr, $value ): void {
		$attachment_id = is_numeric( $value ) ? max( 0, (int) $value ) : 0;
		$preview_size  = isset( $this->config['preview_size'] ) ? (string) $this->config['preview_size'] : 'medium';
		$preview_url   = '';
		if ( $attachment_id > 0 ) {
			$preview = wp_get_attachment_image_src( $attachment_id, $preview_size );
			if ( false === $preview ) {
				$preview = wp_get_attachment_image_src( $attachment_id, 'full' );
			}
			if ( is_array( $preview ) && ! empty( $preview[0] ) ) {
				$preview_url = (string) $preview[0];
			}
		}
		$has_selection   = $attachment_id > 0;
		$frame_title     = isset( $this->config['frame_title'] ) ? (string) $this->config['frame_title'] : __( 'Select Image', 'import-export-menu' );
		$frame_button    = isset( $this->config['frame_button'] ) ? (string) $this->config['frame_button'] : __( 'Use this image', 'import-export-menu' );
		$wrapper_classes = 'import-export-menu-media import-export-menu-media--image' . ( $has_selection ? ' has-selection' : '' );
		?>
		<div
			class="<?php echo esc_attr( $wrapper_classes ); ?>"
			data-frame-title="<?php echo esc_attr( $frame_title ); ?>"
			data-frame-button="<?php echo esc_attr( $frame_button ); ?>"
			data-allowed-types="image"
		>
			<input
				type="hidden"
				id="<?php echo esc_attr( $this->id ); ?>"
				name="<?php echo esc_attr( $name_attr ); ?>"
				value="<?php echo esc_attr( (string) $attachment_id ); ?>"
				class="import-export-menu-media__input"
			/>
			<div
				class="import-export-menu-media__preview<?php echo $has_selection ? ' has-image' : ''; ?>"
				style="<?php echo '' !== $preview_url ? 'background-image:url(' . esc_url( $preview_url ) . ');' : ''; ?>"
				aria-hidden="<?php echo $has_selection ? 'false' : 'true'; ?>"
			>
				<?php // Always rendered; CSS hides it while an image is selected so it can return on Remove. ?>
				<span class="import-export-menu-media__placeholder">
					<span class="dashicons dashicons-cloud-upload" aria-hidden="true"></span>
					<span><?php echo esc_html__( 'Click to upload or drag and drop', 'import-export-menu' ); ?></span>
					<span class="import-export-menu-media__hint">
						<?php echo esc_html__( 'PNG, JPG or SVG up to 5 MB', 'import-export-menu' ); ?>
					</span>
				</span>
			</div>
			<div class="import-export-menu-media__row">
				<button type="button" class="import-export-menu-options__button button import-export-menu-media__choose">
					<?php
					echo $has_selection
						? esc_html__( 'Replace Image', 'import-export-menu' )
						: esc_html__( 'Choose Image', 'import-export-menu' );
					?>
				</button>
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
	 * Inherits MediaField's attachment validation, then additionally rejects
	 * attachments whose MIME type is not an image.
	 *
	 * @param mixed $raw Raw POST value.
	 * @return int
	 */
	public function sanitize( $raw ): int {
		$id = parent::sanitize( $raw );
		if ( $id < 1 ) {
			return 0;
		}
		if ( ! wp_attachment_is_image( $id ) ) {
			return 0;
		}
		return $id;
	}
}
