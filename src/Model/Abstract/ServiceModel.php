<?php

namespace SyncEngine\Model\Abstract;

use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\Trait\Module;
use SyncEngine\Service\Modules;

abstract class ServiceModel extends AbstractModel
{
	use Module;

	const SERVICE = '';

	final public static function getClassName(): string
	{
		$ref = ( new \ReflectionClass( static::class ) );

		$name      = $ref->getShortName();
		$namespace = $ref->getNamespaceName();

		if ( str_starts_with( $namespace, Modules::getRootNamespace() ) ) {
			$parts  = explode( '\\', $namespace );
			$module = $parts[1];

			return $module . ':' . $name;
		}

		return $name;
	}
}
