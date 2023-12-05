<?php

namespace App\Service;

use App\Model\ModuleModel;
use App\Model\WebserviceModel;
use Symfony\Component\DependencyInjection\ServiceLocator;

class Webservices
{
	public function __construct(
		private ServiceLocator $container,
		private readonly Modules $modulesService,
	) {}

	public function getWebservice( $name ): WebserviceModel|null
	{
		try {
			$webservice = $this->container->get( $name ) ?? null;

			if ( $webservice instanceof WebserviceModel ) {
				if ( str_contains( $name, ':' ) ) {
					$parts  = explode( ':', $name );
					$module = $this->modulesService->getModule( $parts[0] );

					$webservice->setModule( $module );
				}
				return $webservice;
			}
		} catch ( \Throwable $throwable ) {
			// Nope.
		}

		// @todo Error or log.
		return null;
	}

	/**
	 * @return WebserviceModel[]
	 */
	public function getWebservices(): array
	{
		static $webservices = [];
		if ( $webservices ) {
			return $webservices;
		}

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			$webservice = $this->getWebservice( $tag );
			if ( $webservice ) {
				$webservices[ $webservice::getClassName() ] = $webservice;
			}
		}

		return $webservices;
	}

	/**
	 * @return WebserviceModel[]
	 */
	public function getModuleWebservices( ModuleModel|string $module ): array
	{
		$webservices = [];

		$moduleName = $module instanceof ModuleModel ? $module::getClassName() : $module;

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			if ( str_starts_with( $tag . ':', $moduleName ) ) {
				$webservice = $this->getWebservice( $tag );
				if ( $webservice ) {
					$webservices[ $webservice::getClassName() ] = $webservice;
				}
			}
		}

		return $webservices;
	}

	/**
	 * @return array
	 */
	public function getWebserviceTypes(): array
	{
		return array_keys( $this->getWebservices() );
	}

	/**
	 * @return array[]
	 */
	public function getWebservicesNormalized(): array
	{
		$webservices = [];
		foreach ( $this->getWebservices() as $key => $webservice ) {
			$webservices[ $key ] = $webservice->normalize();
		}

		return $webservices;
	}
}
