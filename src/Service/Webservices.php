<?php

namespace App\Service;

use App\Model\ModuleModel;
use App\Model\WebserviceModel;
use Symfony\Component\DependencyInjection\ServiceLocator;

class Webservices
{
	public function __construct( private ServiceLocator $container ) {}

	public function getWebservice( $name ): WebserviceModel|null
	{
		if ( class_exists( $name ) && is_callable( [ $name, 'getClassName' ] ) && $name::getClassName() ) {
			$name = $name::getClassName();
		}

		try {
			$webservice = $this->container->get( $name ) ?? null;

			if ( $webservice instanceof ModuleModel ) {
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

		foreach ( $this->container->getProvidedServices() as $tag ) {
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
	public function getModuleWebservices( $module = null ): array
	{
		$webservices = [];

		$moduleName = $module instanceof ModuleModel ? $module::getClassName() : $module;

		foreach ( $this->container->getProvidedServices() as $tag ) {

			if ( class_exists( $tag ) && is_callable( [ $tag, 'getClassName' ] ) && $tag::getClassName() ) {
				$name = $tag::getClassName();
			}

			if ( str_starts_with( $name . ':', $moduleName ) ) {
				$webservice = $this->getWebservice( $name );
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
