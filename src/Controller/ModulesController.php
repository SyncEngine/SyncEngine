<?php

namespace App\Controller;

use Symfony\Component\Finder\Finder;

class ModulesController extends AdminController
{
	public static function getModule( string $module )
	{
		$moduleClass = "modules\\" . $module . "\\" . $module;
		if ( class_exists( $moduleClass ) ) {
			return new $moduleClass();
		}
		// @todo Error or log.
		return null;
	}

	public function getModules(): array {
		$modules = [];

		foreach ( $this->getClassesInDir( 'modules' ) as $class ) {
			$modules[] = $this->getModule( $class );
		}

		return $modules;
	}
}
