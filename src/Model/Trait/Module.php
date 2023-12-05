<?php

namespace App\Model\Trait;

use App\Model\Interface\Persistable;
use App\Model\ModuleModel;
use App\Service\Modules;

trait Module
{
	private ModuleModel $module;

	public function isFromModule(): bool
	{
		return ! empty( $this->getModule() );
	}

	public function getModule(): ModuleModel|null
	{
		if ( ! empty( $this->module ) ) {
			return $this->module;
		}

		if ( $this instanceof Persistable  && is_callable( [ $this->getEntity(), 'getModule' ] ) ) {
			$module = $this->getEntity()->getModule();
			if ( $module ) {
				$this->setModule( $module );
			}
		}

		return $this->module ?? null;
	}

	/**
	 * @throws \Exception
	 */
	public function setModule( ModuleModel $module ): void
	{
		$this->module = $module;

		if ( $this instanceof Persistable &&  is_callable( [ $this->getEntity(), 'setModule' ] ) ) {
			$this->getEntity()->setModule( $module->getName() );
		}
	}
}
