<?php

namespace App\Model;

use App\Component\ExecutionContext;
use App\Model\Trait\ModuleContext;
use App\Model\Trait\Tag;

abstract class TaskModel
{
	use ModuleContext;

	private string $_class = '';

	public string $type = '';
	public string $name = '';
	public string $description = '';

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
		$props['_class']   = $this->getClassName();
		$props['fields'] = $this->getFields();
		if ( $this->isModuleContext() ) {
			$props['module'] = $this->getModule()->getName();
		}
		return $props;
	}

	abstract function getFields(): array;

	abstract function execute( array $config, ExecutionContext $context, $data );

	final static function isTask( $class ): bool
	{
		return $class instanceof TaskModel;
	}

	final public function getClassName(): string
	{
		if ( ! $this->_class ) {
			$this->parseClassName();
		}

		$prefix = '';
		if ( $this->isModuleContext() ) {
			$prefix = $this->getModule()->getName() . ':';
		}
		return $prefix . $this->_class;
	}

	private function parseClassName(): void
	{
		$pos = strrpos(static::class, '\\');
		$this->_class = false === $pos ? static::class : substr(static::class, $pos + 1);
	}
}
