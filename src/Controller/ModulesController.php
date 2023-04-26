<?php

namespace App\Controller;

class ModulesController extends AdminController
{
	public static function getModule( string $module )
	{
		$moduleClass = "modules\\".$module."\\".$module;
		return new $moduleClass;
	}
}
