<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Model\ModuleModel;
use App\Model\WebserviceModel;

class Webservices
{
	/**
	 * @return WebserviceModel[]
	 */
	public static function getWebservices(): array
	{
		return array_merge( self::getModuleWebservices(), self::getCoreWebservices() );
	}

	/**
	 * @return WebserviceModel[]
	 */
	public static function getCoreWebservices(): array
	{
		$namespace   = DefaultController::getClassFinder()->getRootNamespace() . '\Webservice';
		$webservices = DefaultController::getClassFinder()->getClassesInNamespace( $namespace );

		$coreWebservices = [];

		foreach ( $webservices as $class ) {
			/* @var WebserviceModel $webservice */
			$webservice = new $class;

			$coreWebservices[ $webservice->getClassName() ] = $webservice;
		}

		return $coreWebservices;
	}

	/**
	 * @return WebserviceModel[]
	 */
	public static function getModuleWebservices( $module = null ): array
	{
		$moduleWebservices = [];

		if ( $module ) {
			$modules   = [];
			$modules[] = Modules::getModule( $module );
		} else {
			$modules = Modules::getModules();
		}

		foreach ( $modules as $module ) {
			$webservices = $module->getWebservices();
			foreach ( $webservices as $webservice ) {
				$moduleWebservices[ $webservice->getClassName() ] = $webservice;
			}
		}

		return $moduleWebservices;
	}

	public static function getWebservice( $name ): WebserviceModel|null
	{
		if ( ! str_contains( ':', $name ) ) {
			return self::getCoreWebservice( $name );
		}

		$name = explode( ':', $name );

		return self::getModuleWebservice( $name[0], $name[1] );
	}

	public static function getCoreWebservice( $name ): WebserviceModel|null
	{
		$class = DefaultController::getClassFinder()->getRootNamespace() . '\Webservice\\' . $name;
		if ( class_exists( $class ) ) {
			$webservice = new $class();
			if ( $webservice instanceof WebserviceModel ) {
				return $webservice;
			}
		}

		return null;
	}

	public static function getModuleWebservice( $module, $name ): WebserviceModel|null
	{
		$module = Modules::getModule( $module );
		if ( ModuleModel::isModule( $module ) ) {
			return $module->getWebservice( $name );
		}

		return null;
	}

	/**
	 * @return array
	 */
	public static function getWebserviceTypes(): array
	{
		return array_keys( self::getWebservices() );
	}

	/**
	 * @return array[]
	 */
	public static function getWebservicesNormalized(): array
	{
		$webservices = [];
		foreach ( self::getWebservices() as $key => $webservice ) {
			$webservices[ $key ] = $webservice->normalize();
		}

		return $webservices;
	}
}
