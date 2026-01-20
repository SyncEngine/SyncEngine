<?php

namespace SyncEngine\Model;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Executable;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\Provider\Tasks;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Task\Type\TaskTypeInterface;

abstract class TaskModel extends ServiceModel implements Executable, Configurable, Taggable
{
	use Config;

	const SERVICE = Tasks::class;

	/**
	 * The type of task. Can be used for categorizing.
	 *
	 * @var string|TaskTypeInterface
	 */
	public string|TaskTypeInterface $type = '';

	/**
	 * Icon used in the interface.
	 *
	 * @var string
	 */
	public string $icon = '';

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

	public function getType(): string
	{
		return $this->type instanceof TaskTypeInterface ? $this->type->getType() : $this->type;
	}

	public function getIcon(): string
	{
		return $this->icon ?: $this->getModule()?->getIcon() ?? '';
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

	abstract public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData;

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
			'icon'        => $this->getIcon(),
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
}
