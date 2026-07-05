<?php
/**
 * Shared conditional-visibility normalisation for the options framework.
 *
 * @package Import_Export_Menu
 * @since   2.3.0
 */

declare(strict_types=1);

namespace ImportExportMenu\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Coerces the declarative `condition` config — accepted on fields, sections,
 * tabs, and groups — into the uniform `array{rules, relation}` shape the
 * renderer emits as `data-condition` JSON and evaluates server-side.
 *
 * Two config shapes are accepted:
 *
 *   1. Single rule:
 *      `[ 'field' => 'mode', 'value' => 'advanced' ]`
 *
 *   2. Multiple rules:
 *      `[
 *          [ 'field' => 'enabled', 'value' => true ],
 *          [ 'field' => 'mode', 'operator' => '!=', 'value' => 'simple' ],
 *      ]`
 *      The combinator defaults to AND; pass the relation as `'OR'` to change it.
 *
 * Supported operators: `==` (default), `!=`, `in` (value as array), `not_in`.
 *
 * @since 2.3.0
 */
trait HasConditions {

	/**
	 * Normalise a raw `condition` config plus its relation into the canonical
	 * shape, or an empty array when there is no usable condition.
	 *
	 * @param mixed $condition Raw `condition` config (single rule or list of rules).
	 * @param mixed $relation  Raw relation (`AND` or `OR`); anything else means AND.
	 * @return array{rules:array<int,array<string,mixed>>,relation:string}|array{}
	 */
	protected function normalise_condition( $condition, $relation ): array {
		if ( empty( $condition ) || ! is_array( $condition ) ) {
			return array();
		}
		$rules = $this->normalise_condition_rules( $condition );
		if ( empty( $rules ) ) {
			return array();
		}
		$relation = is_string( $relation ) && 'OR' === strtoupper( $relation ) ? 'OR' : 'AND';
		return array(
			'rules'    => $rules,
			'relation' => $relation,
		);
	}

	/**
	 * Coerce both the single-rule and list-of-rules config shapes into a
	 * uniform list of `[ field, operator, value ]` records.
	 *
	 * @param array<string,mixed> $config Raw `condition` config.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalise_condition_rules( array $config ): array {
		// Single rule shape (assoc array with `field` key).
		if ( isset( $config['field'] ) ) {
			return array( $this->normalise_single_rule( $config ) );
		}
		$rules = array();
		foreach ( $config as $rule ) {
			if ( ! is_array( $rule ) || empty( $rule['field'] ) ) {
				continue;
			}
			$rules[] = $this->normalise_single_rule( $rule );
		}
		return $rules;
	}

	/**
	 * Normalise a single rule into the canonical record.
	 *
	 * @param array<string,mixed> $rule Raw rule.
	 * @return array<string,mixed>
	 */
	private function normalise_single_rule( array $rule ): array {
		return array(
			'field'    => (string) $rule['field'],
			'operator' => isset( $rule['operator'] ) ? (string) $rule['operator'] : '==',
			'value'    => $rule['value'] ?? '',
		);
	}
}
