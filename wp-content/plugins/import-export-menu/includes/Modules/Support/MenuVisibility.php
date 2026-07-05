<?php
/**
 * Per-item visibility rule: storage, normalization, and the show/hide decision.
 *
 * @package Import_Export_Menu
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Modules\Support;

defined( 'ABSPATH' ) || exit;

/**
 * The shared rule layer behind the Conditional module and export/import.
 *
 * A nav-menu item may be shown to everyone, only logged-in users, or only
 * logged-out visitors. The rule lives in item post-meta; this class is the
 * single seam that reads, writes, and evaluates it so the editor field, the
 * frontend filter, and the serializer/importer all agree — without depending on
 * each other. {@see self::is_visible()} is a pure decision so it can be unit
 * tested without WordPress.
 *
 * Storage rule: the default ("everyone") is represented by the absence of meta,
 * so toggling an item back to everyone deletes the row and leaves no residue.
 *
 * @since 1.1.0
 */
final class MenuVisibility {

	/**
	 * Item post-meta key holding the rule.
	 */
	public const META_KEY = '_import_export_menu_visibility';

	/**
	 * Visible to everyone — the default (no meta stored).
	 */
	public const EVERYONE = 'everyone';

	/**
	 * Visible only to logged-in users.
	 */
	public const LOGGED_IN = 'logged_in';

	/**
	 * Visible only to logged-out visitors.
	 */
	public const LOGGED_OUT = 'logged_out';

	/**
	 * Coerce any value to a known rule, defaulting to {@see self::EVERYONE}.
	 *
	 * @param string $value Raw value (post-meta, request input, payload field).
	 * @return string One of the rule constants.
	 */
	public static function normalize( string $value ): string {
		return in_array( $value, array( self::LOGGED_IN, self::LOGGED_OUT ), true )
			? $value
			: self::EVERYONE;
	}

	/**
	 * Read a menu item's rule from post-meta.
	 *
	 * @param int $item_id Menu item id.
	 * @return string One of the rule constants.
	 */
	public static function for_item( int $item_id ): string {
		return self::normalize( (string) get_post_meta( $item_id, self::META_KEY, true ) );
	}

	/**
	 * Persist a menu item's rule, deleting the meta when it is the default.
	 *
	 * @param int    $item_id Menu item id.
	 * @param string $rule    Desired rule (will be normalized).
	 */
	public static function store( int $item_id, string $rule ): void {
		$rule = self::normalize( $rule );
		if ( self::EVERYONE === $rule ) {
			delete_post_meta( $item_id, self::META_KEY );
			return;
		}
		update_post_meta( $item_id, self::META_KEY, $rule );
	}

	/**
	 * Decide whether an item is visible under a rule for a given auth state.
	 *
	 * @param string $rule         One of the rule constants.
	 * @param bool   $is_logged_in Whether the current viewer is logged in.
	 * @return bool
	 */
	public static function is_visible( string $rule, bool $is_logged_in ): bool {
		switch ( self::normalize( $rule ) ) {
			case self::LOGGED_IN:
				return $is_logged_in;
			case self::LOGGED_OUT:
				return ! $is_logged_in;
			default:
				return true;
		}
	}

	/**
	 * Translated label map for the editor `<select>`, keyed by rule constant.
	 *
	 * @return array<string,string>
	 */
	public static function choices(): array {
		return array(
			self::EVERYONE   => __( 'Everyone', 'import-export-menu' ),
			self::LOGGED_IN  => __( 'Logged-in users only', 'import-export-menu' ),
			self::LOGGED_OUT => __( 'Logged-out visitors only', 'import-export-menu' ),
		);
	}
}
