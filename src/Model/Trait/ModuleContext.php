<?php

namespace App\Model\Trait;

use App\Model\ModuleModel;
use App\Service\ModuleService;

trait ModuleContext
{
	use Context;

	public function isModuleContext(): bool
	{
		return str_starts_with( ( new \ReflectionClass( $this ) )->getNamespaceName(), ModuleService::getRootNamespace() );
	}

	public function getModule(): ModuleModel
	{
		if ( ModuleModel::isModule( $this ) ) {
			return $this;
		}

		static $done;
		if ( $done ) {
			return $this->getContext( 'module' );
		}
		$done = true;

		$parts = explode( "\\", get_class( $this ) );
		array_pop( $parts ); // Remove Class name.
		array_pop( $parts ); // Remove Class namespace.
		$className = array_pop( $parts );
		$moduleClass = implode( "\\", $parts ) . "\\" . $className . "\\" . $className;

		$this->setContext( new $moduleClass(), 'module' );
		return $this->getContext( 'module' );
	}
}
