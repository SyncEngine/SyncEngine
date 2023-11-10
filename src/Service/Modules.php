<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Model\ModuleModel;
use Psr\Container\ContainerInterface;

class Modules
{
	public function __construct(private ContainerInterface $container) {}

	/**
	 * @todo Move to a service?
	 * @return ModuleModel|null
	 */
	public function getModule( string $modulename ): ModuleModel|null
	{
		$module =  $this->container->get($modulename);

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
