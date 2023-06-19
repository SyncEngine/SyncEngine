<?php

namespace App\Model;

use App\Component\AutomationContext;
use App\Model\Trait\ModuleContext;

abstract class TaskModel
{
	use ModuleContext;

	public $type = '';
	public $name = '';
	public $description = '';

	public function __construct()
	{
		// Construct.
	}

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
		if ( $this->isModuleContext() ) {
			$props['module'] = $this->getModule() ? ( new \ReflectionClass( $this->getModule() ) )->getShortName() : '';
		}
		return $props;
	}

	final public function getSlug(): string
	{
		$prefix = '';
		if ( $this->isModuleContext() ) {
			$prefix = ( new \ReflectionClass( $this->getModule() ) )->getShortName() . ':';
		}
		return $prefix . $this->getType();
	}

	abstract function getFields(): array;

	abstract function execute( array $config, array $data, AutomationContext $context ): array;

	final static function isTask( $class ): bool
	{
		return $class instanceof TaskModel;
	}
}
