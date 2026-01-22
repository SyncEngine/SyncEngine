<?php

namespace SyncEngine\Model;

use Symfony\Component\Notifier\Notification\Notification;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Service\Locator\Notifiers;
use SyncEngine\Task\Type\TaskTypeInterface;

abstract class NotifierModel extends ServiceModel implements Configurable
{
	use Config;

	const SERVICE = Notifiers::class;

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

	abstract public function getFields(): array;

	abstract public function notify( array $config, Notification $notification );

	public function normalize(): array
	{
		$props = [
			'_class'      => $this->getClassLocator(),
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

	final static function isNotifier( $class ): bool
	{
		return $class instanceof NotifierModel;
	}
}
