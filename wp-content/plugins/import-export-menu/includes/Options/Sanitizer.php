<?php
/**
 * Sanitization pipeline for options framework submissions.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Walks every registered field, applies its `sanitize()` method, and runs the
 * result through the per-field filter so extenders can intercept values.
 *
 * Used as the `sanitize_callback` of `register_setting()`. Unknown POST keys
 * are ignored — the registry is the source of truth.
 *
 * @since 2.1.0
 */
final class Sanitizer {

	/**
	 * Field registry walked during sanitization.
	 *
	 * @var Registry
	 */
	private $registry;

	/**
	 * Repository consulted for the previously stored values.
	 *
	 * @var Repository
	 */
	private $repository;

	/**
	 * Wire the collaborators the sanitizer reads from.
	 *
	 * @param Registry   $registry   Field registry.
	 * @param Repository $repository Repository for previously stored values.
	 */
	public function __construct( Registry $registry, Repository $repository ) {
		$this->registry   = $registry;
		$this->repository = $repository;
	}

	/**
	 * Sanitize the entire submitted payload.
	 *
	 * Merges sanitized values on top of the previously stored values so that
	 * fields not present in the current form (e.g. another navigation group)
	 * survive. Only the fields belonging to the submitted group are walked —
	 * see {@see self::submitted_field_ids()}.
	 *
	 * @param mixed $input Raw POST value provided by Settings API.
	 * @return array<string,mixed>
	 */
	public function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$clean = $this->repository->all();

		/**
		 * Fires before sanitization. Use to short-circuit or mutate the raw
		 * input before per-field sanitizers run.
		 *
		 * @param array<string,mixed> $input    Raw input.
		 * @param Registry            $registry Registered structure.
		 */
		do_action( 'import_export_menu_options_before_save', $input, $this->registry );

		foreach ( $this->submitted_field_ids() as $field_id ) {
			$field = $this->registry->field( $field_id );
			if ( null === $field ) {
				continue;
			}
			$raw   = array_key_exists( $field_id, $input ) ? $input[ $field_id ] : null;
			$value = $field->sanitize( $raw );

			/**
			 * Filter the sanitized value of a single field.
			 *
			 * @param mixed                                 $value Sanitized value.
			 * @param mixed                                 $raw   Raw input.
			 * @param \ImportExportMenu\Options\Fields\AbstractField $field Field instance.
			 */
			$value = apply_filters( "import_export_menu_options_sanitize_{$field_id}", $value, $raw, $field );

			$clean[ $field_id ] = $value;
		}

		/**
		 * Fires after the per-field sanitizers run, before WordPress writes the option.
		 *
		 * @param array<string,mixed> $clean    Sanitized payload.
		 * @param array<string,mixed> $input    Raw input.
		 * @param Registry            $registry Registered structure.
		 */
		do_action( 'import_export_menu_options_after_save', $clean, $input, $this->registry );

		return $clean;
	}

	/**
	 * Field ids that were rendered on the submitted form.
	 *
	 * The options page renders one navigation group at a time (all of that
	 * group's tabs are present in the DOM and submitted together), so only
	 * that group's fields should be re-sanitized; everything else keeps its
	 * stored value via the {@see Repository::all()} seed above. Without this
	 * scoping, saving one group would reset every other group's fields to
	 * their defaults because they are absent from `$input`.
	 *
	 * Falls back to every registered field when no `_active_group` hint is
	 * present (e.g. programmatic calls and unit tests), preserving the
	 * original "sanitize everything" behaviour.
	 *
	 * The settings nonce is verified by `wp-admin/options.php` before this
	 * sanitize callback runs, so re-checking it here would be redundant.
	 *
	 * @return array<int,string>
	 */
	private function submitted_field_ids(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- options.php verifies the settings nonce before this sanitize callback runs.
		$active_group = isset( $_POST['_active_group'] ) ? sanitize_key( wp_unslash( (string) $_POST['_active_group'] ) ) : '';
		if ( '' === $active_group ) {
			return array_keys( $this->registry->all_fields() );
		}
		return $this->registry->field_ids_for_group( $active_group );
	}
}
