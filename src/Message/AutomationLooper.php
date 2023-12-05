<?php

namespace SyncEngine\Message;

class AutomationLooper
{
	public function __construct( private int $automationId ) {}

	public function getAutomationId(): int
	{
		return $this->automationId;
	}
}
