<?php

namespace App\Model;

use App\Model\Interface\Configurable;
use App\Model\Interface\Executable;
use App\Model\Interface\Taggable;
use App\Model\Trait\Config;
use App\Model\Trait\Module;
use App\Service\Modules;

abstract class TaskModel implements Executable, Configurable, Taggable
{
	use Config;
	use Module;

	private static string $_class = '';
	private static string $_namespace = '';

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

	public function getTags(): array
	{
		return [];
	}

	public function getTagsResource( array $config ): array
	{
		return [];
	}

	public function normalize(): array
	{
		$props = [
			'_class'      => $this->getClassName(),
			'type'        => $this->getType(),
			'name'        => $this->getName(),
			'description' => $this->getDescription(),
			'fields'      => $this->getFields(),
			'tags'        => $this->getTags(),
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

	final public static function getClassName(): string
	{
		if ( ! self::$_class ) {
			self::parseClassName();
		}

		if ( str_starts_with( self::$_namespace, Modules::getRootNamespace() ) ) {
			$parts  = explode( '\\', self::$_namespace );
			$module = $parts[1];

			return $module . ':' . self::$_class;
		}

		return self::$_class;
	}

	final protected static function parseClassName(): void
	{
		$pos = strrpos(static::class, '\\');
		self::$_namespace = false === $pos ? '' : substr(static::class, 0, $pos);
		self::$_class ??= false === $pos ? static::class : substr(static::class, $pos + 1);
	}
}
