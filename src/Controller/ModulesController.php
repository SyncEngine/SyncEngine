<?php

namespace App\Controller;

class ModulesController extends AdminController
{
	public static function getModule( string $module )
	{
		$moduleClass = "modules\\" . $module . "\\" . $module;
		if ( class_exists( $moduleClass ) ) {
			return new $moduleClass;
		}
		// @todo Error or log.
		return null;
	}
}
