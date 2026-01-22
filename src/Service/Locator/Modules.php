<?php

namespace SyncEngine\Service\Locator;

use Symfony\Component\DependencyInjection\ServiceLocator;
use SyncEngine\Model\ModuleModel;

class Modules
{
	public function __construct( private ServiceLocator $container ) {}

	/**
	 * @todo Move to a service?
	 * @return ModuleModel|null
	 */
	public function get( string $moduleName, string $vendor = '' ): ?ModuleModel
	{
		if ( $vendor ) {
			$moduleName = self::getModulePackageName( $moduleName, $vendor );
		}

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
		$parsed = self::parseModulePackageName( $class );

		return $parsed[0] ?? '';
	}

	public static function getModuleName( string|object $class ): string
	{
		$parsed = self::parseModulePackageName( $class );

		return $parsed[1] ?? '';
	}

	public static function getModulePackageName( string|object $class, ?string $vendor = null ): string
	{
		$parsed = self::parseModulePackageName( $class );

		if ( $parsed ) {
			return $parsed[0] . '/' . $parsed[1];
		} elseif( $vendor && ! str_contains( $class, '\\' ) ) {
			return $vendor . '/' . $class;
		}

		return '';
	}

	public static function parseModulePackageName( string|object $class ): ?array
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

			return [ $vendor, $name ];
		}

		return null;
	}

	public static function isFromModule( string|object $class ): bool
	{
		if ( is_object( $class ) ) {
			$class = $class::class;
		}

		return str_starts_with( $class, Modules::getRootNamespace() );
	}

	public static function getRootNamespace(): string
	{
		return 'SyncEngine\\Module';
	}
}
