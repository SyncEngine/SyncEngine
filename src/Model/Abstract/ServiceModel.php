<?php

namespace SyncEngine\Model\Abstract;

use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\Trait\Module;
use SyncEngine\Service\Locator\AbstractServiceModelLocator;
use SyncEngine\Service\Locator\Modules;

abstract class ServiceModel extends AbstractModel
{
	use Module;

	/**
	 * @required
	 */
	const SERVICE = '';

	public static function create( string $name ): ?static
	{
		// @todo Factory.
		return static::getServiceModelLocator()->get( $name );
	}

	public static function get( string $name ): ?static
	{
		return static::getServiceModelLocator()->get( $name );
	}

	public static function getAll(): ?array
	{
		return static::getServiceModelLocator()->getAll();
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
			static::getServiceModelLocator()->getModelClass()::getClassName()
		);
	}

	public static function getServiceModelLocator(): AbstractServiceModelLocator
	{
		if ( ! static::SERVICE ) {
			throw new \ErrorException( 'No service locator defined.' );
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
