<?php

namespace SyncEngine\Service;

use SyncEngine\Model\ModuleModel;
use SyncEngine\Model\WebserviceModel;
use Symfony\Component\DependencyInjection\ServiceLocator;

class Webservices
{
	public function __construct(
		private readonly ServiceLocator $container,
		private readonly Modules $modulesService,
	) {}

	public function get( $name ): WebserviceModel|null
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
	public function getAll(): array
	{
		static $webservices = [];
		if ( $webservices ) {
			return $webservices;
		}

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			$webservice = $this->get( $tag );
			if ( $webservice ) {
				$webservices[ $webservice::getClassName() ] = $webservice;
			}
		}

		return $webservices;
	}

	/**
	 * @return WebserviceModel[]
	 */
	public function getAllFromModule( ModuleModel|string $module ): array
	{
		$webservices = [];

		$moduleName = $module instanceof ModuleModel ? $module::getClassName() : $module;

		foreach ( $this->container->getProvidedServices() as $tag => $class ) {
			if ( str_starts_with( $tag . ':', $moduleName ) ) {
				$webservice = $this->get( $tag );
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
	public function getTypes(): array
	{
		return array_keys( $this->getAll() );
	}

	/**
	 * @return array[]
	 */
	public function getNormalized(): array
	{
		$webservices = [];
		foreach ( $this->getAll() as $key => $webservice ) {
			$webservices[ $key ] = $webservice->normalize();
		}

		return $webservices;
	}
}
