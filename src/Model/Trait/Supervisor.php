<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Model\Interface\Persistable;

trait Supervisor
{
	protected AbstractModel $supervisor;

	public function getSupervisor(): ?AbstractModel
	{
		if ( ! isset( $this->supervisor ) ) {
			if ( ! $this instanceof Persistable || ! is_callable( [ $this->getEntity(), 'getSupervisor' ] ) ) {
				return null;
			}
			$control = $this->getEntity()->getSupervisor();

			[ $model, $name ] = explode( ':', $control );

			$model            = AbstractModel::getModelClass( $model );
			$this->supervisor = $model::get( $name );
		}

		return $this->supervisor;
	}

	public function setSupervisor( AbstractModel $controlModel ): void
	{
		$this->supervisor = $controlModel;

		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'setSupervisor' ] ) ) {
			$this->getEntity()->setSupervisor( $controlModel->getModelName() . ':' . $controlModel->getName() );
		}
	}
}
