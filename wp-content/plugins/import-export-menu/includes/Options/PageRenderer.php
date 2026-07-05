<?php
/**
 * Renders the options page HTML using the registered groups/tabs/sections/fields.
 *
 * @package Import_Export_Menu
 * @since   2.1.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Pure presentational layer for the options page.
 *
 * Resolves the active group + tab from the URL, then delegates the actual
 * markup to the dashboard template. The Settings API takes care of the form
 * action, nonce, and option name; layout (sidebar + sub-tabs + sections) is
 * rendered manually so we control the HTML structure.
 *
 * @since 2.1.0
 */
final class PageRenderer {

	/**
	 * Field registry consulted while rendering.
	 *
	 * @var Registry
	 */
	private $registry;

	/**
	 * Storage repository consulted for current values.
	 *
	 * @var Repository
	 */
	private $repository;

	/**
	 * Settings API option group used by the form.
	 *
	 * @var string
	 */
	private $option_group;

	/**
	 * Settings API option name (also the form's input name prefix).
	 *
	 * @var string
	 */
	private $option_name;

	/**
	 * Submenu page slug used in tab links.
	 *
	 * @var string
	 */
	private $page_slug;

	/**
	 * Wire the renderer collaborators and Settings API identifiers.
	 *
	 * @param Registry   $registry     Field registry.
	 * @param Repository $repository   Storage repository.
	 * @param string     $option_group Settings API option group.
	 * @param string     $option_name  Settings API option name.
	 * @param string     $page_slug    Submenu page slug.
	 */
	public function __construct( Registry $registry, Repository $repository, string $option_group, string $option_name, string $page_slug ) {
		$this->registry     = $registry;
		$this->repository   = $repository;
		$this->option_group = $option_group;
		$this->option_name  = $option_name;
		$this->page_slug    = $page_slug;
	}

	/**
	 * Render the entire page.
	 */
	public function render(): void {
		if ( $this->registry->is_empty() ) {
			$this->render_empty_state();
			return;
		}

		$groups       = $this->registry->groups();
		$active_group = $this->resolve_active_group( $groups );
		$tabs         = $this->registry->tabs_for( $active_group );
		$active_tab   = $this->resolve_active_tab( $tabs );
		$values       = $this->repository->all();
		$reset_url    = wp_nonce_url(
			add_query_arg( 'action', Framework::RESET_ACTION, admin_url( 'admin-post.php' ) ),
			Framework::RESET_ACTION
		);

		$template = IMPORT_EXPORT_MENU_PATH . 'admin/partials/options-page.php';
		if ( ! file_exists( $template ) ) {
			return;
		}

		// Variables consumed by the template.
		$registry     = $this->registry;
		$option_group = $this->option_group;
		$option_name  = $this->option_name;
		$page_slug    = $this->page_slug;
		$has_sidebar  = $this->registry->has_groups();
		$renderer     = $this;

		require $template;
	}

	/**
	 * Render a single field's row (called by the partial template).
	 *
	 * @param Fields\AbstractField $field Field instance.
	 * @param mixed                $value Current value.
	 */
	public function render_field_row( $field, $value ): void {
		if ( ! $field->is_visible() ) {
			return;
		}
		$name_attr = $this->option_name . '[' . $field->id() . ']';
		$row_class = 'import-export-menu-field import-export-menu-field--' . $field->type();
		if ( 'pro' === $field->tier() ) {
			$row_class .= ' import-export-menu-field--is-pro';
		}
		$condition_attrs = $this->condition_attributes( $field->condition() );
		?>
		<div
			class="<?php echo esc_attr( $row_class ); ?>"
			data-field-id="<?php echo esc_attr( $field->id() ); ?>"
			<?php echo $condition_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped in condition_attributes(). ?>
		>
			<div class="import-export-menu-field__label">
				<label for="<?php echo esc_attr( $field->id() ); ?>">
					<?php echo esc_html( $field->label() ); ?>
					<?php $this->render_tier_badge( $field->tier() ); ?>
				</label>
			</div>
			<div class="import-export-menu-field__control">
				<?php $field->render( $name_attr, $value ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Build the conditional-visibility HTML attributes for any element that can
	 * be shown/hidden by a control's value — fields, sections, tabs, and groups
	 * all share this. Emits a `data-condition` JSON blob (read by the options
	 * JS) plus an initial ` hidden` when the stored values already fail the
	 * rule, so the server's first paint matches what the JS would resolve.
	 *
	 * The returned string is already escaped and may be echoed directly.
	 *
	 * @param array{rules:array<int,array<string,mixed>>,relation:string}|array{} $condition Normalised condition.
	 * @return string Leading-space-prefixed attribute string, or '' when unconditional.
	 */
	public function condition_attributes( array $condition ): string {
		if ( empty( $condition ) ) {
			return '';
		}
		$attrs = '';
		$json  = wp_json_encode(
			array(
				'rules'       => $condition['rules'],
				'relation'    => $condition['relation'],
				'option_name' => $this->option_name,
			)
		);
		if ( false !== $json ) {
			$attrs .= ' data-condition="' . esc_attr( $json ) . '"';
		}
		// Resolve initial visibility server-side so a conditionally hidden
		// element does not flash before the JS runs.
		if ( ! $this->is_condition_met( $condition ) ) {
			$attrs .= ' hidden';
		}
		return $attrs;
	}

	/**
	 * Render a guidance notice when the user is viewing a group or tab whose
	 * condition is NOT satisfied by the saved values.
	 *
	 * Cross-navigation conditions (a group gated on a control that lives in a
	 * different group) are confusing: the area is reachable, but the control
	 * that governs it is not on the page, so the user cannot see why it might
	 * vanish from the menu. This surfaces that dependency explicitly — naming
	 * the controlling control(s) and where to find them — without blocking the
	 * controls below it. When the condition is already met (the normal case)
	 * nothing is rendered.
	 *
	 * @param array{rules:array<int,array<string,mixed>>,relation:string}|array{} $condition Normalised condition.
	 */
	public function render_dependency_notice( array $condition ): void {
		if ( empty( $condition ) || $this->is_condition_met( $condition ) ) {
			return;
		}

		$labels = array();
		foreach ( $condition['rules'] as $rule ) {
			$field_id    = (string) $rule['field'];
			$field       = $this->registry->field( $field_id );
			$label       = ( null !== $field && '' !== $field->label() ) ? $field->label() : $field_id;
			$group_label = $this->locate_field_group_label( $field_id );
			if ( '' !== $group_label ) {
				/* translators: 1: control label, 2: navigation group label. */
				$labels[] = sprintf( __( '%1$s (under %2$s)', 'import-export-menu' ), $label, $group_label );
			} else {
				$labels[] = $label;
			}
		}
		$labels = array_values( array_unique( $labels ) );
		$joined = implode( ', ', $labels );

		$message = count( $labels ) > 1
			/* translators: %s: comma-separated list of control labels. */
			? sprintf( __( 'This page only appears while %s are set to show it. Update them and save your changes to keep access from the menu.', 'import-export-menu' ), $joined )
			/* translators: %s: control label. */
			: sprintf( __( 'This page only appears while %s is set to show it. Update it and save your changes to keep access from the menu.', 'import-export-menu' ), $joined );
		?>
		<div class="import-export-menu-notice import-export-menu-notice--warning import-export-menu-options__dependency-notice" role="status">
			<span class="import-export-menu-notice__icon dashicons dashicons-warning" aria-hidden="true"></span>
			<div class="import-export-menu-notice__body">
				<p class="import-export-menu-notice__title"><?php echo esc_html__( 'This page depends on another setting', 'import-export-menu' ); ?></p>
				<div class="import-export-menu-notice__message"><p><?php echo esc_html( $message ); ?></p></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Find the label of the navigation group that contains a given field, or an
	 * empty string when the field is ungrouped or unknown. Used to point the
	 * dependency notice at the right place in the sidebar.
	 *
	 * @param string $field_id Field identifier.
	 */
	private function locate_field_group_label( string $field_id ): string {
		foreach ( $this->registry->groups() as $group ) {
			if ( in_array( $field_id, $this->registry->field_ids_for_group( $group->id() ), true ) ) {
				return $group->label();
			}
		}
		return '';
	}

	/**
	 * Evaluate a field's visibility condition against the stored values.
	 *
	 * Mirrors the client-side logic in `options-page.js` (`evaluateConditions`
	 * + `compareValue`) so the server's first paint matches what the JS would
	 * resolve, avoiding a flash of a field that should start hidden.
	 *
	 * @param array{rules:array<int,array<string,mixed>>,relation:string} $condition Normalised condition.
	 * @return bool True when the field should be visible.
	 */
	private function is_condition_met( array $condition ): bool {
		if ( empty( $condition['rules'] ) ) {
			return true;
		}
		$results = array();
		foreach ( $condition['rules'] as $rule ) {
			$actual   = $this->repository->get( (string) $rule['field'] );
			$operator = isset( $rule['operator'] ) ? (string) $rule['operator'] : '==';
			$expected = $rule['value'] ?? '';
			if ( is_array( $actual ) && '==' === $operator ) {
				$haystack  = array_map( array( $this, 'condition_stringify' ), $actual );
				$results[] = in_array( $this->condition_stringify( $expected ), $haystack, true );
				continue;
			}
			$results[] = $this->condition_compare( $actual, $operator, $expected );
		}
		if ( 'OR' === $condition['relation'] ) {
			return in_array( true, $results, true );
		}
		return ! in_array( false, $results, true );
	}

	/**
	 * Compare a single (scalar) actual value against the rule expectation.
	 *
	 * @param mixed  $actual   Stored value.
	 * @param string $operator One of `==`, `!=`, `in`, `not_in`.
	 * @param mixed  $expected Rule's expected value.
	 * @return bool
	 */
	private function condition_compare( $actual, string $operator, $expected ): bool {
		$a = $this->condition_stringify( $actual );
		switch ( $operator ) {
			case '!=':
				return $a !== $this->condition_stringify( $expected );
			case 'in':
				return is_array( $expected )
					&& in_array( $a, array_map( array( $this, 'condition_stringify' ), $expected ), true );
			case 'not_in':
				return is_array( $expected )
					&& ! in_array( $a, array_map( array( $this, 'condition_stringify' ), $expected ), true );
			case '==':
			default:
				return $a === $this->condition_stringify( $expected );
		}
	}

	/**
	 * Coerce a value to the same string form the browser would read from the
	 * control, so booleans map to the toggle's `0`/`1` rather than PHP's `''`/`1`.
	 *
	 * @param mixed $value Value to stringify.
	 * @return string
	 */
	private function condition_stringify( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}
		return (string) $value;
	}

	/**
	 * Render the small plan badge that sits inline with a field's label.
	 *
	 * Only the `pro` tier is surfaced visually — the framework still tracks
	 * `free` internally so the row class stays consistent, but rendering it as
	 * a badge would add noise (every non-pro field is implicitly free).
	 *
	 * @param string $tier Tier slug returned from `AbstractField::tier()`.
	 */
	private function render_tier_badge( string $tier ): void {
		if ( 'pro' !== $tier ) {
			return;
		}
		printf(
			' <span class="import-export-menu-field__tier import-export-menu-field__tier--pro">%s</span>',
			esc_html__( 'Pro', 'import-export-menu' )
		);
	}

	/**
	 * Resolve the active group from the URL, falling back to the first group.
	 *
	 * @param array<int,Group> $groups Registered groups.
	 */
	private function resolve_active_group( array $groups ): string {
		if ( empty( $groups ) ) {
			return Registry::DEFAULT_GROUP;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation arg.
		$requested = isset( $_GET['group'] ) ? sanitize_key( wp_unslash( (string) $_GET['group'] ) ) : '';
		foreach ( $groups as $group ) {
			if ( $group->id() === $requested ) {
				return $requested;
			}
		}
		return $groups[0]->id();
	}

	/**
	 * Resolve the active tab within a group from the URL.
	 *
	 * @param array<int,Tab> $tabs Tabs in the active group.
	 */
	private function resolve_active_tab( array $tabs ): string {
		if ( empty( $tabs ) ) {
			return '';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation arg.
		$requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : '';
		foreach ( $tabs as $tab ) {
			if ( $tab->id() === $requested ) {
				return $requested;
			}
		}
		return $tabs[0]->id();
	}

	/**
	 * Render a friendly placeholder when nothing has been registered.
	 */
	private function render_empty_state(): void {
		?>
		<div class="wrap import-export-menu-options import-export-menu-options--empty">
			<h1><?php echo esc_html__( 'Settings', 'import-export-menu' ); ?></h1>
			<p><?php echo esc_html__( 'No settings have been registered yet.', 'import-export-menu' ); ?></p>
			<p>
				<?php
				echo wp_kses_post(
					__( 'Hook into <code>import_export_menu_options_register</code> to add tabs, sections, and fields.', 'import-export-menu' )
				);
				?>
			</p>
		</div>
		<?php
	}
}
