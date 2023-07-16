<?php

namespace App\Model\Trait;

use App\Model\ModuleModel;
use App\Service\ModuleService;

trait Module
{
	public function isFromModule(): bool
	{
		return ! empty( $this->getModule() );
	}

	public function getModule(): ModuleModel|null
	{
		if ( ! empty( $this->module ) ) {
			return $this->module;
		}

		if ( isset( $this->entity ) && is_callable( [ $this->entity, 'getModule' ] ) ) {
			$module = $this->entity->getModule();
			if ( $module ) {
				$this->setModule( $module );
			}
		}

		return $this->module ?? null;
	}

	/**
	 * @throws \Exception
	 */
	public function setModule( ModuleModel|string $module )
	{
		if ( is_string( $module ) ) {
			$module = ModuleService::getModule( $module );
			if ( ! $module ) {
				throw new \Exception( 'Module not found' );
			}
		}

		$this->module = $module;

		if ( isset( $this->entity ) && is_callable( [ $this->entity, 'setModule' ] ) ) {
			$this->entity->setModule( $module->getName() );
		}
	}
}
