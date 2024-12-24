<?php

namespace SyncEngine\Messenger\Message;

class AsyncExecuteMessage
{
	public function __construct( private int $automationId, private int $traceId ) {}

	public function getAutomationId(): int
	{
		return $this->automationId;
	}

	public function getTraceId(): int
	{
		return $this->traceId;
	}
}
