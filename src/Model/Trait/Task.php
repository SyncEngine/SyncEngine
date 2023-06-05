<?php

namespace App\Model\Trait;

use App\Model\ModuleModel;
use App\Service\ModuleService;

trait Task
{
	public function getType(): string {
		return $this->type;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function getArgs(): array
	{
		$props = get_object_vars( $this );
		$props['slug']   = $this->getSlug();
		$props['fields'] = $this->getFields();
		if ( $this->isModuleTask() ) {
			$props['module'] = $this->getModule() ? ( new \ReflectionClass( $this->getModule() ) )->getShortName() : '';
		}
		return $props;
	}

	final public function getSlug(): string
	{
		$prefix = '';
		if ( $this->isModuleTask() ) {
			$prefix = ( new \ReflectionClass( $this->getModule() ) )->getShortName() . ':';
		}
		return $prefix . $this->getType();
	}

	public function isModuleTask(): bool
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
			array_pop( $parts ); // Remove Task namespace.
			$className = array_pop( $parts );
			$moduleClass = implode( "\\", $parts ) . "\\" . $className . "\\" . $className;

			$this->module = new $moduleClass();
		}
		return $this->module;
	}

	abstract function getFields(): array;

	abstract function execute( array $config, array $data ): array;
}
