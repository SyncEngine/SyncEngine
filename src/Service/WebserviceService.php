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
		$webservices = DefaultController::getClassesInNamespace( DefaultController::getRootNamespace() . '\Webservice' );
		$coreWebservices = [];

		foreach ( $webservices as $class ) {
			$webservice = new $class;
			$coreWebservices[ $webservice->getType() ] = $webservice;
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
			$webservices = $module->getWebservices();
			foreach ( $webservices as $webservice ) {
				$moduleWebservices[ $webservice->getSlug() ] = $webservice;
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
		$webservices = $this->getWebservices();
		return $webservices[ $name ] ?? null;
	}
}
