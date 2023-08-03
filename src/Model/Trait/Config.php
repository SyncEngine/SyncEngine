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
		$store = array_keys( $this->getConfigEntityDependencies() );
		$this->setConfig( $store, '_dependencies' );
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

		$dependencies = [];

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

								$dependencies[ $entity . ':' . $entityId ] = $entityModel;
								if ( method_exists( $entityModel, 'getConfigEntityDependencies' ) ) {
									$dependencies = array_merge( $dependencies, $entityModel->getConfigEntityDependencies() );
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

									$dependencies[ $entity . ':' . $entityId ] = $entityModel;
									if ( method_exists( $entityModel, 'getConfigEntityDependencies' ) ) {
										$dependencies = array_merge( $dependencies, $entityModel->getConfigEntityDependencies() );
									}
								}
							}
						}
					break;

					case 'tasks':
						foreach ( $value as $taskConfig ) {
							$taskModel    = Tasks::getTask( $taskConfig['_class'] );
							$dependencies = array_merge( $dependencies, $taskModel->getConfigEntityDependencies( $taskConfig ) );
						}
					break;

					case 'webservice':
						$webserviceModel = Webservices::getWebservice( $value['_class'] );
						$dependencies    = array_merge( $dependencies, $webserviceModel->getConfigEntityDependencies( $config[ $name ] ) );
					break;
				}
			}

			if ( ! empty( $field['nested'] ) && $value ) {
				$dependencies = array_merge( $dependencies, $this->getConfigEntityDependencies( $value, $field['nested'] ) );
				unset( $field['nested'] );
			} elseif ( is_array( $field ) ) {
				$dependencies = array_merge( $dependencies, $this->getConfigEntityDependencies( $config, $field ) );
			}
		}

		ksort( $dependencies );

		return $dependencies;
	}

	public function getConfigFields(): array
	{
		if ( method_exists( $this, 'getFields' ) ) {
			return $this->getFields();
		}
	}
}
