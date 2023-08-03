<?php

namespace App\Model\Trait;

use App\Controller\EntityController;
use App\Model\Interface\Persistable;
use App\Service\Tasks;
use App\Service\Webservices;

trait Config
{
	protected array $config = [];

	public function getConfig( $key = null, $default = null ): mixed
	{
		if ( $key ) {
			return $this->config[ $key ] ?? $default;
		}

		return $this->config;
	}

	public function setConfig( $value, $key = null ): void
	{
		if ( $key ) {
			$this->config[ $key ] = $value;
		} else {
			$this->config = $value;
		}

		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'setConfig' ] ) ) {
			$this->getEntity()->setConfig( $this->config );
		}
	}

	public function fetchConfigEntityDependencies(): void
	{
		$this->setConfig( $this->getConfigEntityDependencies(), '_dependencies' );
	}

	public function getConfigEntityDependencies( $config = [], $fields = [] ): array
	{
		if ( ! $config ) {
			$config = $this->getConfig();
		}
		if ( ! $fields ) {
			$fields = $this->getConfigFields();
		}

		if ( ! $config || ! $fields ) {
			return [];
		}

		$entities = [];

		foreach ( $fields as $key => $field ) {
			$name  = $field['name'] ?? $key;
			$value = $config[ $name ] ?? null;

			// Pull entities from fields config.
			if ( ! empty( $field['type'] ) && $value ) {
				switch ( $field['type'] ) {
					case 'entity':
						$entity = $field['entity'] ?? '';
						if ( $entity ) {
							$entityId    = ( is_numeric( $value ) ) ? $value : $value['id'] ?? 0;
							$entityModel = EntityController::getEntityModelClass( ucfirst( $entity ) );
							$entityModel = $entityModel::get( $entityId );
							if ( $entityModel instanceof Persistable ) {

								$entities[] = $entity . ':' . $entityId;
								if ( method_exists( $entityModel, 'getConfigEntityDependencies' ) ) {
									$entities = array_merge( $entities, $entityModel->getConfigEntityDependencies() );
								}
							}
						}
					break;
					case 'entities':
						$entity = $field['entity'] ?? '';
						if ( $entity ) {
							foreach ( $value as $id ) {
								$entityId    = ( is_numeric( $id ) ) ? $id : $id['id'] ?? 0;
								$entityModel = EntityController::getEntityModelClass( ucfirst( $entity ) );
								$entityModel = $entityModel::get( $entityId );
								if ( $entityModel instanceof Persistable ) {

									$entities[] = $entity . ':' . $entityId;
									if ( method_exists( $entityModel, 'getConfigEntityDependencies' ) ) {
										$entities = array_merge( $entities, $entityModel->getConfigEntityDependencies() );
									}
								}
							}
						}
					break;

					case 'tasks':
						foreach ( $value as $taskConfig ) {
							$taskModel = Tasks::getTask( $taskConfig['_class'] );
							$entities  = array_merge( $entities, $taskModel->getConfigEntityDependencies( $taskConfig ) );
						}
					break;

					case 'webservice':
						$webserviceModel = Webservices::getWebservice( $value['_class'] );
						$entities        = array_merge( $entities, $webserviceModel->getConfigEntityDependencies( $config[ $name ] ) );
					break;
				}
			}

			if ( ! empty( $field['nested'] ) && $value ) {
				$entities = array_merge( $entities, $this->getConfigEntityDependencies( $value, $field['nested'] ) );
				unset( $field['nested'] );
			} elseif ( is_array( $field ) ) {
				$entities = array_merge( $entities, $this->getConfigEntityDependencies( $config, $field ) );
			}
		}

		sort( $entities );

		return array_unique( $entities );
	}

	public function getConfigFields(): array
	{
		if ( method_exists( $this, 'getFields' ) ) {
			return $this->getFields();
		}
	}
}
