<?php

namespace App\Controller;

class ModulesController extends AdminController
{
	public static function getModule( string $module ): ModuleController
	{
		$moduleClass = "modules\\".$module."\\".$module;
		return new $moduleClass;
	}
}
