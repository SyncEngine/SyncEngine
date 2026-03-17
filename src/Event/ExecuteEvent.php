<?php

namespace SyncEngine\Event;

use Symfony\Contracts\EventDispatcher\Event;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;

class ExecuteEvent extends Event
{
	public function __construct(
		private readonly Execute $executeService,
		private readonly ExecuteContext $executeContext,
		private readonly string $eventName,
	) {}

	public function getEventName(): string
	{
		return $this->eventName;
	}

	public function getExecuteService(): Execute
	{
		return $this->executeService;
	}

	public function getExecuteContext(): ExecuteContext
	{
		return $this->executeContext;
	}
}
