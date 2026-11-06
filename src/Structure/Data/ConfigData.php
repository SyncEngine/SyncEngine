<?php

namespace SyncEngine\Structure\Data;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\TaskModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Service\ConditionsValidator;

class ConfigData extends ResourceData
{
	/**
	 * Whether a raw (unparsed) config value counts as configured by the user.
	 *
	 * - Scalars: configured when non-empty, numeric (`0`, `"0"`) or boolean (`false`).
	 * - Tags (`{{ ... }}`) are non-empty strings, so they are configured even if they resolve to nothing at runtime.
	 * - Arrays: configured when at least one nested value is configured. An array without values (e.g. an empty
	 *   schema form structure like `{ "query": { "$filter": "" } }`) is not configured.
	 *
	 * Always evaluate this on the raw config, before tag parsing.
	 */
	public static function isConfiguredValue( mixed $value ): bool
	{
		if ( $value instanceof \Traversable ) {
			$value = iterator_to_array( $value );
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( self::isConfiguredValue( $item ) ) {
					return true;
				}
			}

			return false;
		}

		return ! empty( $value ) || is_numeric( $value ) || is_bool( $value );
	}

	/**
	 * Keep only the top-level keys whose value is configured (see isConfiguredValue()).
	 */
	public function filterConfigured(): static
	{
		return new static( array_filter( $this->getArrayCopy(), [ self::class, 'isConfiguredValue' ] ) );
	}

	/**
	 * Normalize unconfigured user input: raw empty strings (untouched form fields) become null.
	 *
	 * Intended for schema-driven user input only (currently routine input in Flow steps and Trigger routines).
	 * Keys are kept so the input keeps its shape. Tags, `0`, `false` and `[]` are kept as-is.
	 * Apply to the raw config, before tag parsing, so tags that resolve to "" keep their value.
	 */
	public function nullUnconfigured(): static
	{
		return new static( self::nullEmptyStrings( $this->getArrayCopy() ) );
	}

	/**
	 * @param  array<mixed>  $config
	 *
	 * @return array<mixed>
	 */
	private static function nullEmptyStrings( array $config ): array
	{
		foreach ( $config as $key => $value ) {
			if ( is_array( $value ) ) {
				$config[ $key ] = self::nullEmptyStrings( $value );
			} elseif ( '' === $value ) {
				$config[ $key ] = null;
			}
		}

		return $config;
	}

	public function isConfigured( array|FieldCollection $fields = [], ?iterable $config = null ): bool
	{
		if ( ! empty( $fields ) ) {
			$config = $this->sanitize( $fields, $config );
		} else {
			$config ??= $this->normalize();
		}

		return self::isConfiguredValue( $config );
	}

	public function sanitize( array|FieldCollection $fields, ?iterable $config = null ): array
	{
		$remove    = [];
		$validator = new ConditionsValidator();
		$config  ??= $this->normalize();

		// @todo Refactor to use Field type objects instead of raw arrays
		$fields = $fields instanceof FieldCollection ? $fields->normalize() : $fields;

		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$name = $field['name'] ?? $key;

			if ( isset( $field['default'] ) && ! isset( $config[ $name ] ) ) {
				$config[ $name ] = $field['default'];
			}

			if ( isset( $config[ $name ] ) ) {
				$value   = $config[ $name ];
				$recurse = is_iterable( $value );

				if ( isset( $field['type'] ) ) {

					// Validate field.
					if ( ! empty( $field['conditions'] ) && ! $validator->validate( $field['conditions'], $config ) ) {
						if ( ! isset( $remove[ $name ] ) ) {
							$remove[ $name ] = true;
						}
						continue;
					}

					// Ensures this field will not be marked for removal again.
					$remove[ $name ] = false;

					// Parse subfields from fields config.
					if ( $recurse ) {
						// Disable recursing for known field types.
						$recurse = false;

						switch ( $field['type'] ) {
							case 'entity':
								$entity = $field['entity'] ?? '';
								if ( $entity ) {
									$entityModel = EntityModel::get( $value, $entity );
									if ( $entityModel instanceof Configurable ) {
										// @todo Use $field object to get fields and sanitize?
										$config[ $name ] = $this->sanitize( $entityModel->getConfigFields( $field ), $value );
									}
								}
							break;
							case 'entities':
								$entity = $field['entity'] ?? '';
								if ( $entity ) {
									foreach ( $value as $index => $entityConfig ) {
										$entityModel = EntityModel::get( $entityConfig, $entity );
										if ( $entityModel instanceof Configurable && is_iterable( $entityConfig ) ) {
											// @todo Use $field object to get fields and sanitize?
											$config[ $name ][ $index ] = $this->sanitize( $entityModel->getConfigFields( $field ), $entityConfig );
										}
									}
								}
							break;

							case 'tasks':
								foreach ( $value as $index => $taskConfig ) {
									$taskModel = TaskModel::get( $taskConfig['_class'] );
									if ( $taskModel && is_iterable( $taskConfig ) ) {
										$config[ $name ][ $index ] = $this->sanitize( $taskModel->getFields(), $taskConfig );
									} else {
										// @todo Remove?
									}
								}
							break;

							case 'webservice':
								$webserviceModel = WebserviceModel::get( $value['_class'] );
								if ( $webserviceModel ) {
									$config[ $name ] = $this->sanitize( $webserviceModel->getFields(), $value );
								} else {
									// @todo Remove?
								}
							break;

							case 'repeater':
								foreach ( $value as $index => $repeaterConfig ) {
									if ( is_iterable( $repeaterConfig ) ) {
										$config[ $name ][ $index ] = $this->sanitize( $field['fieldset'] ?? [], $repeaterConfig );
									}
								}
							break;

							case 'schema':
								/*if ( is_array( $value ) ) {
									// @todo
								}*/
							break;

							default:
								$recurse = true;
							break;
						}
					}
				}

				// @todo Tabs, Wizard?
				if ( $recurse && isset( $field['nested'] ) ) {
					$config[ $name ] = $this->sanitize( $field['nested'], $value );
					continue;
				}
			}

			$config = $this->sanitize( $field, $config );
		}

		foreach ( $remove as $key => $check ) {
			if ( true === $check ) {
				unset( $config[ $key ] );
			}
		}

		return $config;
	}
}
