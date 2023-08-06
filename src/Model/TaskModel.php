<?php

namespace App\Model;

use App\Model\Interface\Configurable;
use App\Model\Interface\Executable;
use App\Model\Trait\Config;
use App\Model\Trait\Module;

abstract class TaskModel implements Executable, Configurable
{
	use Config;
	use Module;

	private string $_class = '';

	/**
	 * The type of task, can be used for categorizing.
	 *
	 * @var string
	 */
	public string $type = '';

	/**
	 * Human-readable name used in the interface.
	 *
	 * @var string
	 */
	public string $name = '';

	/**
	 * Human-readable description used in the interface.
	 *
	 * @var string
	 */
	public string $description = '';

	public function __construct()
	{
		// Construct.
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	abstract public function getFields(): array;

	public function normalize(): array
	{
		$props = [
			'_class'      => $this->getClassName(),
			'type'        => $this->getType(),
			'name'        => $this->getName(),
			'description' => $this->getDescription(),
			'fields'      => $this->getFields(),
		];

		if ( $this->isFromModule() ) {
			$props['module'] = $this->getModule()->getName();
		}

		return $props;
	}

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
		if ( $this->isFromModule() ) {
			$prefix = $this->getModule()->getName() . ':';
		}

		return $prefix . $this->_class;
	}

	private function parseClassName(): void
	{
		$pos          = strrpos( static::class, '\\' );
		$this->_class = false === $pos ? static::class : substr( static::class, $pos + 1 );
	}
}
