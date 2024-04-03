<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\BlueprintModel;
use SyncEngine\Model\Interface\Persistable;
use SyncEngine\Model\ModuleModel;

trait Supervisor
{
	use Config {
		initConfig as _initConfig;
	}

	protected ?AbstractModel $supervisor;

	protected function initConfig(): void
	{
		$this->_initConfig();
		$this->initSupervisor();
	}

	public function getSupervisor(): ?AbstractModel
	{
		if ( ! isset( $this->supervisor ) ) {
			$this->supervisor = null;

			if ( ! $this instanceof Persistable || ! is_callable( [ $this->getEntity(), 'getSupervisor' ] ) ) {
				return $this->supervisor;
			}

			$supervisor = $this->getEntity()->getSupervisor();

			if ( $supervisor ) {
				$this->setSupervisor( $supervisor );
			}
		}

		return $this->supervisor;
	}

	public function setSupervisor( AbstractModel|string $model ): void
	{
		if ( is_string( $model ) ) {
			$parts = explode( ':', $model );
			$model = array_shift( $parts );
			$name  = implode( ':', $parts );

			$model = AbstractModel::getModelClass( $model );
			// @todo Error handler?
			$model = $model::get( $name );
		}

		if ( ! $model || ! $this->supportsSupervisor( $model ) ) {
			throw new \Exception( 'Supervisor not allowed' );
		}

		$this->supervisor = $model;

		if ( method_exists( $this->supervisor, 'setSupervisable' ) ) {
			$this->supervisor->setSupervisable( $this );
		}

		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'setSupervisor' ] ) ) {
			$supervisor = $model->getModelName();
			if ( $model instanceof ServiceModel ) {
				$supervisor .= ':' . $model->getClassLocator();
			} elseif ( $model instanceof EntityModel ) {
				$supervisor .= ':' . $model->getId();
			} else {
				$supervisor .= ':' . $model->getRef();
			}
			$this->getEntity()->setSupervisor( $supervisor );
		}
	}

	public function initSupervisor(): void
	{
		$supervisor = $this->getSupervisor();
		if ( $supervisor instanceof BlueprintModel ) {
			$supervisor->parseConfig();
		}
	}

	public function supportsSupervisor( string|AbstractModel $type ): bool
	{
		$supported = $this->getSupportedSupervisors();
		if ( is_object( $type ) ) {
			return in_array( $type::class, $supported );
		}
		if ( str_ends_with( $type, 'Model' ) ) {
			return in_array( $type, $supported );
		}
		return array_key_exists( strtolower( $type ), $supported );
	}

	public function getSupportedSupervisors(): array
	{
		return [
			'module'    => ModuleModel::class,
			'blueprint' => BlueprintModel::class,
		];
	}
}
