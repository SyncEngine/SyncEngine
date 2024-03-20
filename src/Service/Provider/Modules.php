<?php

namespace SyncEngine\Service\Provider;

use Symfony\Component\DependencyInjection\ServiceLocator;
use SyncEngine\Model\ModuleModel;

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

		foreach ( $this->container->getProvidedServices() as $tag => $service ) {
			$module = $this->get( $tag );
			if ( $module ) {
				$modules[] = $module;
			}
		}

		return $modules;
	}

	public static function getModuleVendor( string|object $class ): string
	{
		if ( is_object( $class ) ) {
			$class = $class::class;
		}

		$namespace = self::getRootNamespace();

		if ( str_starts_with( $class, $namespace ) ) {
			$class  = substr( $class, strlen( $namespace ) );
			$parts  = array_filter( explode( '\\', $class ) );
			return array_shift( $parts );
		}

		return '';
	}

	public static function getModuleName( string|object $class ): string
	{
		if ( is_object( $class ) ) {
			$class = $class::class;
		}

		$namespace = self::getRootNamespace();

		if ( str_starts_with( $class, $namespace ) ) {
			$class  = substr( $class, strlen( $namespace ) );
			$parts  = array_filter( explode( '\\', $class ) );
			$vendor = array_shift( $parts );
			$name   = array_shift( $parts );

			return $vendor . '/' . $name;
		}

		return '';
	}

	public static function getRootNamespace(): string
	{
		return 'SyncEngine\\Module';
	}
}
