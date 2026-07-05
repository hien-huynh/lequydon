<?php
/**
 * Resolves a portable menu-item reference to a local object id.
 *
 * @package Import_Export_Menu
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Support;

defined( 'ABSPATH' ) || exit;

/**
 * The import-side counterpart of {@see ReferenceEncoder}.
 *
 * Takes the slug/path/fingerprint an export recorded and finds the matching
 * post or term on this site, so a menu item points at the right content after a
 * migration instead of a stale id. Resolution is best-effort and ordered most-
 * to least precise: path, then slug, then the title fingerprint. Custom links
 * and post-type archives carry no object, so they resolve to 0.
 *
 * @since 1.1.0
 */
final class ReferenceResolver {

	/**
	 * Resolve a reference to a local object id.
	 *
	 * @param array<string,mixed> $reference Reference produced by {@see ReferenceEncoder}.
	 * @return int Local object id, or 0 when nothing matches (or the item has no object).
	 */
	public function resolve( array $reference ): int {
		switch ( (string) ( $reference['type'] ?? '' ) ) {
			case 'post_type':
				return $this->resolve_post( $reference );
			case 'taxonomy':
				return $this->resolve_term( $reference );
			default:
				return 0;
		}
	}

	/**
	 * Resolve a post-type reference (page, post, custom post type).
	 *
	 * @param array<string,mixed> $reference Reference fields.
	 * @return int
	 */
	private function resolve_post( array $reference ): int {
		$object = (string) ( $reference['object'] ?? 'page' );

		$path = (string) ( $reference['path'] ?? '' );
		if ( '' !== $path ) {
			$post = get_page_by_path( $path, OBJECT, $object );
			if ( $post instanceof \WP_Post ) {
				return (int) $post->ID;
			}
		}

		$slug = (string) ( $reference['slug'] ?? '' );
		if ( '' !== $slug ) {
			$by_slug = $this->post_by_slug( $slug, $object );
			if ( $by_slug > 0 ) {
				return $by_slug;
			}
		}

		$fingerprint = (string) ( $reference['fingerprint'] ?? '' );
		if ( '' !== $fingerprint ) {
			return $this->post_by_title( $fingerprint, $object );
		}

		return 0;
	}

	/**
	 * Resolve a taxonomy reference (category, tag, custom taxonomy).
	 *
	 * @param array<string,mixed> $reference Reference fields.
	 * @return int
	 */
	private function resolve_term( array $reference ): int {
		$taxonomy = (string) ( $reference['object'] ?? '' );
		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return 0;
		}

		$slug = (string) ( $reference['slug'] ?? '' );
		if ( '' !== $slug ) {
			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( $term instanceof \WP_Term ) {
				return (int) $term->term_id;
			}
		}

		$fingerprint = (string) ( $reference['fingerprint'] ?? '' );
		if ( '' !== $fingerprint ) {
			$term = get_term_by( 'name', $fingerprint, $taxonomy );
			if ( $term instanceof \WP_Term ) {
				return (int) $term->term_id;
			}
		}

		return 0;
	}

	/**
	 * Find a post id by its slug within a post type.
	 *
	 * @param string $slug      Post slug.
	 * @param string $post_type Post type.
	 * @return int Post id, or 0 when not found.
	 */
	private function post_by_slug( string $slug, string $post_type ): int {
		$post = get_page_by_path( $slug, OBJECT, $post_type );
		if ( $post instanceof \WP_Post ) {
			return (int) $post->ID;
		}

		$ids = get_posts(
			array(
				'name'        => $slug,
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);

		return empty( $ids ) ? 0 : (int) $ids[0];
	}

	/**
	 * Find a post id by an exact title within a post type.
	 *
	 * @param string $title     Post title.
	 * @param string $post_type Post type.
	 * @return int Post id, or 0 when not found.
	 */
	private function post_by_title( string $title, string $post_type ): int {
		$ids = get_posts(
			array(
				'title'       => $title,
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);

		return empty( $ids ) ? 0 : (int) $ids[0];
	}
}
