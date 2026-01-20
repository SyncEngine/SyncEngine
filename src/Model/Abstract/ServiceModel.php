<?php

namespace SyncEngine\Model\Abstract;

use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\Trait\Module;
use SyncEngine\Service\Provider\AbstractServiceModelProvider;
use SyncEngine\Service\Provider\Modules;

abstract class ServiceModel extends AbstractModel
{
	use Module;

	/**
	 * @required
	 */
	const SERVICE = '';

	public static function get( string $name ): ?static
	{
		return static::getServiceModelProvider()->get( $name );
	}

	public static function getAll(): ?array
	{
		return static::getServiceModelProvider()->getAll();
	}

	public static function getModelName( string $name = '' ): string
	{
		if ( $name ) {
			if ( class_exists( $name ) && method_exists( $name , 'getModelName' ) ) {
				return $name::getModelName();
			}
			return parent::getModelName( $name );
		}

		return parent::getModelName(
			static::getServiceModelProvider()->getModelClass()::getClassName()
		);
	}

	public static function getServiceModelProvider(): AbstractServiceModelProvider
	{
		if ( ! static::SERVICE ) {
			throw new \ErrorException( 'No service provider defined.' );
		}
		return DefaultController::getContainer()->get( static::SERVICE );
	}

	public function getClassLocator(): string
	{
		return static::_getClassLocator();
	}

	/**
	 * @return string
	 * @internal Used for auto wiring.
	 */
	final public static function _getClassLocator(): string
	{
		$ref = ( new \ReflectionClass( static::class ) );

		$name      = $ref->getShortName();
		$namespace = $ref->getNamespaceName();

		if ( Modules::isFromModule( $namespace ) ) {
			$module = Modules::getModulePackageName( $namespace );

			return $module . ':' . $name;
		}

		return $name;
	}
}
