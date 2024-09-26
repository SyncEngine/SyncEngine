<?php

namespace SyncEngine\Model\Abstract;

use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\Trait\Module;
use SyncEngine\Service\Provider\Modules;

abstract class ServiceModel extends AbstractModel
{
	use Module;

	const SERVICE = '';

	public static function get( string $name ): ?static
	{
		return DefaultController::getContainer()->get( static::SERVICE )->get( $name );
	}

	public static function getAll(): ?array
	{
		return DefaultController::getContainer()->get( static::SERVICE )->getAll();
	}

	final public static function getModelName( string $name = '' ): string
	{
		if ( $name ) {
			return parent::getModelName( $name );
		}

		return match ( strtolower( static::SERVICE ) ) {
			'blueprints' => 'Blueprint',
			'tasks' => 'Task',
			'webservices' => 'Webservice',
			default => parent::getModelName(),
		};
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
