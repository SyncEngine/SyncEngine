<?php

namespace SyncEngine\EventDispatcher\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class HookEvent extends Event
{
	abstract public function getHookType(): string;
}
