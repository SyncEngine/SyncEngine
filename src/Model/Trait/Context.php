<?php

namespace App\Model\Trait;

use App\Model\ModuleModel;
use App\Service\ModuleService;

trait Context
{
	public function isFromModule(): bool
	{
		return str_starts_with( ( new \ReflectionClass( $this ) )->getNamespaceName(), ModuleService::getRootNamespace() );
	}

	public function getModule(): ModuleModel
	{
		static $done;
		if ( $done ) {
			return $this->module;
		}
		$done = true;

		if ( ModuleModel::isModule( $this ) ) {
			$this->module = $this;
		} else {
			$parts = explode( "\\", get_class( $this ) );
			array_pop( $parts ); // Remove Class name.
			array_pop( $parts ); // Remove Class namespace.
			$className = array_pop( $parts );
			$moduleClass = implode( "\\", $parts ) . "\\" . $className . "\\" . $className;

			$this->module = new $moduleClass();
		}
		return $this->module;
	}
}
