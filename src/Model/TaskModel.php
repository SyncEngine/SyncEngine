<?php

namespace App\Model;

use App\Model\Trait\Task;
use App\Service\ModuleService;

abstract class TaskModel
{
	use Task;

	public $type = '';
	public $name = '';
	public $description = '';

	public function __construct()
	{
		// Construct.
	}

	final static function isTask( $class ): bool
	{
		return $class instanceof TaskModel || in_array( "App\Model\Trait\Task", class_uses( $class ), true );
	}

	final static function isModuleTask( $class ): bool
	{
		return str_starts_with( ( new \ReflectionClass( $class ) )->getNamespaceName(), ModuleService::getRootNamespace() );
	}

	final static function getModule( $class ): bool
	{
		$parts = explode( "\\", get_class( $class ) );
		array_pop( $parts ); // Remove Class name.
		array_pop( $parts ); // Remove Task namespace.
		$className = array_pop( $parts );
		$moduleClass = implode( "\\", $parts ) . "\\" . $className . "\\" . $className;
		return new $moduleClass();
	}
}
