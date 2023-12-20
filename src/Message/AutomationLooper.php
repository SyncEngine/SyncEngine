<?php

namespace SyncEngine\Message;

class AutomationLooper
{
	public function __construct( private int $automationId, private string $traceId ) {}

	public function getAutomationId(): int
	{
		return $this->automationId;
	}

	public function getTraceId()
	{
		return $this->traceId;
	}
}
