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
		$finder  = new Finder();

		$finder->files()->in('../modules')->name('*.php');

		foreach ( $finder as $file ) {
			$class_name = "modules\\" . $file->getFilenameWithoutExtension() . "\\" . $file->getFilenameWithoutExtension();

			if ( class_exists( $class_name ) ) {
				try {
					$module = new $class_name();
				} catch ( \Throwable $e ) {
					// @todo Error or log.
					continue;
				}
			}
		}

		return $modules;
	}
}
