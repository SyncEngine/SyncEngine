<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Model\BlueprintModel;
use SyncEngine\Model\Interface\Persistable;
use SyncEngine\Model\ModuleModel;

trait Supervisor
{
	use Config {
		initConfig as _initConfig;
	}

	protected ?AbstractModel $supervisor;

	private static array $_SUPERVISORS = [
		'module'    => ModuleModel::class,
		'blueprint' => BlueprintModel::class,
	];

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
				[ $model, $name ] = explode( ':', $supervisor );

				$model            = AbstractModel::getModelClass( $model );
				$this->supervisor = $model::get( $name );

				if ( method_exists( $this->supervisor, 'setSupervisable' ) ) {
					$this->supervisor->setSupervisable( $this );
				}
			}
		}

		return $this->supervisor;
	}

	public function setSupervisor( AbstractModel $model ): void
	{
		if ( ! $this->supportsSupervisor( $model ) ) {
			throw new \Exception( 'Supervisor not allowed' );
		}

		$this->supervisor = $model;

		if ( method_exists( $this->supervisor, 'setSupervisable' ) ) {
			$this->supervisor->setSupervisable( $this );
		}

		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'setSupervisor' ] ) ) {
			$this->getEntity()->setSupervisor( $model->getModelName() . ':' . $model->getName() );
		}
	}

	public function initSupervisor(): void
	{
		$supervisor = $this->getSupervisor();
		if ( $supervisor instanceof BlueprintModel ) {
			$supervisor->updateConfig();
		}
	}

	public function supportsSupervisor( string|AbstractModel $type ): bool
	{
		if ( is_object( $type ) ) {
			return in_array( $type::class, self::$_SUPERVISORS );
		}
		return array_key_exists( $type, self::$_SUPERVISORS );
	}
}
