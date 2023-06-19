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

	public function getModule(): ModuleModel|null
	{
		return $this->getContext( 'module' );
	}

	public function setModule( ModuleModel $module )
	{
		$this->setContext( $module, 'module' );
	}
}
