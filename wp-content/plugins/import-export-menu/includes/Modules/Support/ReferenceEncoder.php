<?php
/**
 * Encodes a menu item's content reference into a portable, id-independent form.
 *
 * @package Import_Export_Menu
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Turns a nav menu item's raw object id into a reference that survives migration.
 *
 * A menu item stores the database id of the post or term it points to. That id
 * is meaningless on another site, so exporting it verbatim breaks every link on
 * import. This encoder records what the item points to in resolvable terms —
 * the object slug and path, plus the title as a last-resort fingerprint — so the
 * importer (Sprint 4) can re-resolve it to whatever id matches on the target.
 *
 * Items arrive as `WP_Post` objects decorated by `wp_setup_nav_menu_item()`,
 * whose `type`/`object`/`object_id`/`title` live as dynamic properties; they are
 * read through `WP_Post::to_array()` so the access stays statically typed.
 *
 * @since 1.1.0
 */
final class ReferenceEncoder {

	/**
	 * Build a portable reference for a fully set-up menu item.
	 *
	 * @param \WP_Post $item Menu item from `wp_get_nav_menu_items()`.
	 * @return array<string,string> Reference keyed by `type`, and — depending on
	 *                              the item kind — `object`, `slug`, `path`,
	 *                              `fingerprint`.
	 */
	public function encode( \WP_Post $item ): array {
		$fields = $item->to_array();
		$type   = $this->string_field( $fields, 'type' );

		switch ( $type ) {
			case 'post_type':
				return array( 'type' => $type ) + $this->encode_post( $fields );
			case 'taxonomy':
				return array( 'type' => $type ) + $this->encode_term( $fields );
			case 'post_type_archive':
			case 'custom':
			default:
				return array(
					'type'   => $type,
					'object' => $this->string_field( $fields, 'object' ),
				);
		}
	}

	/**
	 * Reference fields for a post-type item (page, post, custom post type).
	 *
	 * @param array<string,mixed> $fields Menu item fields.
	 * @return array<string,string>
	 */
	private function encode_post( array $fields ): array {
		$reference = array(
			'object'      => $this->string_field( $fields, 'object' ),
			'fingerprint' => $this->string_field( $fields, 'title' ),
		);

		$post = get_post( $this->int_field( $fields, 'object_id' ) );
		if ( $post instanceof \WP_Post ) {
			$reference['slug'] = (string) $post->post_name;

			$path = get_page_uri( $post );
			if ( is_string( $path ) ) {
				$reference['path'] = $path;
			}
		}

		return $reference;
	}

	/**
	 * Reference fields for a taxonomy item (category, tag, custom taxonomy).
	 *
	 * @param array<string,mixed> $fields Menu item fields.
	 * @return array<string,string>
	 */
	private function encode_term( array $fields ): array {
		$reference = array(
			'object'      => $this->string_field( $fields, 'object' ),
			'fingerprint' => $this->string_field( $fields, 'title' ),
		);

		$term = get_term( $this->int_field( $fields, 'object_id' ), $this->string_field( $fields, 'object' ) );
		if ( $term instanceof \WP_Term ) {
			$reference['slug'] = (string) $term->slug;
		}

		return $reference;
	}

	/**
	 * Read a menu item field as a string.
	 *
	 * @param array<string,mixed> $fields Menu item fields.
	 * @param string              $key    Field key.
	 * @return string
	 */
	private function string_field( array $fields, string $key ): string {
		return isset( $fields[ $key ] ) ? (string) $fields[ $key ] : '';
	}

	/**
	 * Read a menu item field as an integer.
	 *
	 * @param array<string,mixed> $fields Menu item fields.
	 * @param string              $key    Field key.
	 * @return int
	 */
	private function int_field( array $fields, string $key ): int {
		return isset( $fields[ $key ] ) ? (int) $fields[ $key ] : 0;
	}
}
