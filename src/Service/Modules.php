<?php

namespace SyncEngine\Service;

use SyncEngine\Model\ModuleModel;
use Symfony\Component\DependencyInjection\ServiceLocator;

class Modules
{
	public function __construct( private ServiceLocator $container ) {}

	/**
	 * @todo Move to a service?
	 * @return ModuleModel|null
	 */
	public function get( string $moduleName ): ModuleModel|null
	{
		$module =  $this->container->get( $moduleName );

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
	public function getAll(): array
	{
		static $modules = [];
		if ( $modules ) {
			return $modules;
		}

		foreach ( $this->container->getProvidedServices() as $tag ) {
			if ( $tag::name ) {
				$module = $this->get( $tag::name );
				if ( $module ) {
					$modules[] = $module;
				}
			}
		}

		return $modules;
	}

	public static function getRootNamespace(): string
	{
		return 'SyncEngine\\Module';
	}
}
