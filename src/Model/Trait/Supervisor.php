<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Exception\InvalidParameterException;
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
		getConfigDependencies as _getConfigDependencies;
	}

	protected ?AbstractModel $supervisor;

	protected function initConfig(): void
	{
		$this->_initConfig();
		$this->initSupervisor();
	}

	public function getConfigDependencies( array|bool $recursive = false ): array
	{
		$supervisor = $this->getSupervisor();
		if ( $supervisor ) {
			return $supervisor->getConfigDependencies( $recursive );
		}
		return $this->_getConfigDependencies( $recursive );
	}

	public function getIcon(): string
	{
		$icon = '';

		if ( is_callable( $this, 'getIcon' ) ) {
			$icon = $this->getIcon();
		}

		if ( ! $icon && $this instanceof Persistable && is_callable( [ $this->getEntity(), 'getIcon' ] ) ) {
			$icon = $this->getEntity()->getIcon();
		}

		if ( ! $icon && is_callable( [ $this->getSupervisor(), 'getIcon' ] ) ) {
			$icon = $this->getSupervisor()->getIcon();
		}

		return $icon ?? '';
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

	public function exportSupervisor(): ?string
	{
		if ( $this instanceof Persistable ) {
			// Return raw database value.
			return $this->getEntity()->getSupervisor();
		}

		$supervisor = $this->getSupervisor();

		if ( $supervisor instanceof ServiceModel ) {
			return $supervisor->getClassLocator();
		}

		return $supervisor ? AbstractModel::getModelName( $supervisor::class ) : null;
	}

	public function setSupervisor( AbstractModel|string|null $model ): void
	{
		if ( is_string( $model ) ) {
			$param = $model;
			$parts = explode( ':', $model );
			$model = array_shift( $parts );
			$name  = implode( ':', $parts );

			$modelClass = AbstractModel::getModelClass( $model );
			if ( $modelClass && method_exists( $modelClass, 'get' ) ) {
				$model = $modelClass::get( $name );
			} else {
				throw new InvalidParameterException( 'Supervisor type does not exist: ' . $param );
			}
		}

		if ( ! $model ) {
			// @todo Properly handle errors on interface.
			throw new InvalidParameterException( 'Supervisor not found: ' . $model );
		}

		if ( ! $this->supportsSupervisor( $model ) ) {
			// @todo Properly handle errors on interface.
			throw new InvalidParameterException( 'Supervisor not allowed: ' . $model );
		}

		$this->supervisor = $model;

		if ( method_exists( $this->supervisor, 'setSupervisable' ) ) {
			$this->supervisor->setSupervisable( $this );
		}

		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'setSupervisor' ] ) ) {
			$supervisor = $model::getModelName();
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
			$supervisor->initSupervisableConfig();
		}
	}

	public function runSupervisorValidate(): bool
	{
		if ( $this->supportsSupervisor( BlueprintModel::class ) ) {
			$blueprint = $this->getConfig( '_blueprint._class' );
			if ( $blueprint ) {
				$this->setSupervisor( BlueprintModel::get( $blueprint ) );
			}
		}

		$supervisor = $this->getSupervisor();
		if ( $supervisor instanceof BlueprintModel ) {
			$supervisor->update( true );
		}

		return true;
	}

	public function supportsSupervisor( string|AbstractModel $type ): bool
	{
		$supported = $this->getSupportedSupervisors();
		if ( is_object( $type ) ) {
			foreach ( $supported as $supervisor ) {
				if ( is_a( $type, $supervisor ) ) {
					return true;
				}
			}
			return false;
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
