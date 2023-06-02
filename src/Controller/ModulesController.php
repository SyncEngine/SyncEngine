<?php

namespace App\Controller;

use Symfony\Component\Finder\Finder;

class ModulesController extends AdminController
{
	public static function getModule( string $module )
	{
		if ( class_exists( $module ) ) {
			$module = new $module();
		} else {
			$moduleClass = "modules\\" . $module . "\\" . $module;
			if ( class_exists( $moduleClass ) ) {
				$module = new $moduleClass();
			}
		}

		if ( $module instanceof ModuleController ) {
			return $module;
		}

		// @todo Error or log.
		return null;
	}

	public function getModules(): array {
		$modules = [];

		foreach ( $this->getClassesInDir( 'modules' ) as $class ) {
			$module = $this->getModule( $class );
			if ( $module ) {
				$modules[] = $module;
			}
		}

		return $modules;
	}
}
