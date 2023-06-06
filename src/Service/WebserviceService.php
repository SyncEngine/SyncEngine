<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Model\WebserviceModel;

class WebserviceService
{
	/**
	 * @todo Move to a service?
	 * @return WebserviceModel[]
	 */
	public function getCoreWebservices(): array
	{
		$tasks = DefaultController::getClassesInNamespace( DefaultController::getRootNamespace() . '\Webservice' );
		$coreWebservices = [];

		foreach ( $tasks as $class ) {
			$task = new $class;
			$coreWebservices[ $task->getType() ] = $task;
		}

		return $coreWebservices;
	}

	/**
	 * @todo Move to a service?
	 * @return WebserviceModel[]
	 */
	public function getModuleWebservices( $module = null ): array
	{
		$moduleWebservices = [];

		if ( $module ) {
			$modules = [];
			$modules[] = ModuleService::getModule( $module );
		} else {
			$modules = ModuleService::getModules();
		}

		foreach ( $modules as $module ) {
			$tasks = $module->getWebservices();
			foreach ( $tasks as $task ) {
				$moduleWebservices[ $task->getSlug() ] = $task;
			}
		}

		return $moduleWebservices;
	}

	/**
	 * @todo Move to a service?
	 * @return WebserviceModel[]
	 */
	public function getWebservices(): array
	{
		return array_merge( $this->getModuleWebservices(), $this->getCoreWebservices() );
	}

	/**
	 * @todo Move to a service?
	 * @return array
	 */
	public function getWebserviceTypes(): array
	{
		return array_keys( $this->getWebservices() );
	}

	public function getWebservice( $name ): WebserviceModel|null
	{
		$tasks = $this->getWebservices();
		return $tasks[ $name ] ?? null;
	}
}
