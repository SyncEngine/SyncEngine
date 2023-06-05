<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Model\ModuleModel;

class ModuleService
{
	/**
	 * @return ModuleModel|null
	 *@todo Move to a service?
	 */
	public static function getModule( string $module ): ModuleModel|null
	{
		if ( class_exists( $module ) ) {
			$module = new $module();
		} else {
			$moduleClass = "modules\\" . $module . "\\" . $module;
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
	 * @return ModuleModel[]
	 *@todo Move to a service?
	 */
	public static function getModules(): array {
		$modules = [];

		foreach ( DefaultController::getClassesInDir( 'modules' ) as $class ) {
			$module = self::getModule( $class );
			if ( $module ) {
				$modules[] = $module;
			}
		}

		return $modules;
	}
}
