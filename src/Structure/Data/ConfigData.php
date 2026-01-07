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
	public function sanitize( array|FieldCollection $fields, ?iterable $config = null ): array
	{
		$validator = new ConditionsValidator();
		if ( null === $config ) {
			$config = $this->normalize();
		}

		// @todo This will eventually need to move into the Field type objects.
		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$name = $field['name'] ?? $key;

			if ( isset( $config[ $name ] ) ) {
				$value = $config[ $name ];

				if ( isset( $field['type'] ) ) {

					// Validate field.
					if ( ! empty( $field['conditions'] ) && ! $validator->validate( $field['conditions'], $config ) ) {
						unset( $config[ $name ] );
						continue;
					}

					$recurse = true;

					// Parse subfields from fields config.
					if ( is_iterable( $value ) ) {
						$recurse = false;

						switch ( $field['type'] ) {
							case 'entity':
								$entity = $field['entity'] ?? '';
								if ( $entity ) {
									$entityModel = EntityModel::get( $value, $entity );
									if ( $entityModel instanceof Configurable ) {
										$config[ $name ] = $this->sanitize( $entityModel->getFields(), $value );
									}
								}
							break;
							case 'entities':
								$entity = $field['entity'] ?? '';
								if ( $entity ) {
									foreach ( $value as $index => $entityConfig ) {
										$entityModel = EntityModel::get( $entityConfig, $entity );
										if ( $entityModel instanceof Configurable ) {
											$config[ $name ][ $index ] = $this->sanitize( $entityModel->getFields(), $entityConfig );
										}
									}
								}
							break;

							case 'tasks':
								foreach ( $value as $index => $taskConfig ) {
									$taskModel = TaskModel::get( $taskConfig['_class'] );
									if ( $taskModel ) {
										$config[ $name ][ $index ] = $this->sanitize( $taskModel->getFields(), $taskConfig );
									} else {
										// @todo Error.
									}
								}
							break;

							case 'webservice':
								$webserviceModel = WebserviceModel::get( $value['_class'] );
								if ( $webserviceModel ) {
									$config[ $name ] = $this->sanitize( $webserviceModel->getFields(), $value );
								} else {
									// @todo Error.
								}
							break;

							case 'repeater':
								foreach ( $value as $index => $repeaterConfig ) {
									$config[ $name ][ $index ] = $this->sanitize( $field['fieldset'] ?? [], $repeaterConfig );
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

					if ( ! $recurse ) {
						// Field type parsed, stop recursing.
						continue;
					}

				}

				// @todo Tabs, Wizard?
				if ( isset( $field['nested'] ) && is_iterable( $value ) ) {
					$config[ $name ] = $this->sanitize( $field['nested'], $value );
					continue;
				}
			}

			$config = $this->sanitize( $field, $config );
		}

		return $config;
	}

}
