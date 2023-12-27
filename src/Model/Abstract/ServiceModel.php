<?php

namespace SyncEngine\Model\Abstract;

use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\Trait\Module;
use SyncEngine\Service\Modules;

abstract class ServiceModel extends AbstractModel
{
	use Module;

	const SERVICE = '';

	public static function get( string $name ): ?object
	{
		return DefaultController::getContainer()->get( static::SERVICE )->get( $name );
	}

	public static function getAll(): ?array
	{
		return DefaultController::getContainer()->get( static::SERVICE )->getAll();
	}

	final public static function getModelName(): string
	{
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
	 * @internal Used for auto wiring.
	 * @return string
	 */
	final public static function _getClassLocator(): string
	{
		$ref = ( new \ReflectionClass( static::class ) );

		$name      = $ref->getShortName();
		$namespace = $ref->getNamespaceName();

		if ( str_starts_with( $namespace, Modules::getRootNamespace() ) ) {
			$module = Modules::getModuleName( $namespace );

			return $module . ':' . $name;
		}

		return $name;
	}
}
