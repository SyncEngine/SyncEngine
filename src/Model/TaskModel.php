<?php

namespace SyncEngine\Model;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Executable;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Task\Type\TaskTypeInterface;

abstract class TaskModel extends ServiceModel implements Executable, Configurable, Taggable
{
	use Config;

	const SERVICE = 'Tasks';

	/**
	 * The type of task, can be used for categorizing.
	 *
	 * @var string|TaskTypeInterface
	 */
	public string|TaskTypeInterface $type = '';

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

	/**
	 * Icon used in the interface.
	 *
	 * @var string
	 */
	public string $icon = '';

	public function getType(): string
	{
		return $this->type instanceof TaskTypeInterface ? $this->type->getType() : $this->type;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getIcon(): string
	{
		return $this->icon;
	}

	abstract public function getFields(): array;

	abstract public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData;

	public function getTags(): array
	{
		return [];
	}

	public function getTagsResource( array $config = [], ?ExecuteContext $context = null ): array
	{
		return [];
	}

	public function normalize(): array
	{
		$props = [
			'_class'      => $this->getClassLocator(),
			'type'        => $this->getType(),
			'name'        => $this->getName(),
			'description' => $this->getDescription(),
			'icon'        => $this->getIcon(),
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
}
