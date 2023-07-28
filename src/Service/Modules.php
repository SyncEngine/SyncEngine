<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Model\ModuleModel;

class Modules
{
	/**
	 * @todo Move to a service?
	 * @return ModuleModel|null
	 */
	public static function getModule( string $module ): ModuleModel|null
	{
		if ( class_exists( $module ) ) {
			$module = new $module();
		} else {
			$moduleClass = self::getRootNamespace() . "\\" . $module . "\\" . $module;
			if ( class_exists( $moduleClass ) ) {
				$module = new $moduleClass();
			}
		}

		if ( $module instanceof ModuleModel ) {
			return $module;
		}

		// @todo Error or log.
		return null;
	}

	/**
	 * @todo Move to a service?
	 * @return ModuleModel[]
	 */
	public static function getModules(): array
	{
		static $modules = [];
		if ( $modules ) {
			return $modules;
		}

		foreach ( DefaultController::getClassFinder()->getClassesInDir( self::getRootNamespace() ) as $class ) {
			$module = self::getModule( $class );
			if ( $module ) {
				$modules[] = $module;
			}
		}

		return $modules;
	}

	public static function getRootNamespace(): string
	{
		return 'modules';
	}
}
