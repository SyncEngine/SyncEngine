<?php

namespace SyncEngine\Messenger\Message;

/**
 * @internal
 */
class AutomationBatch
{
	public function __construct(
		private int $automationId,
		private int $traceId,
		private array $requestParams = [],
		private array $requestQuery = [],
	) {
	}

	public function getAutomationId(): int
	{
		return $this->automationId;
	}

	public function getTraceId(): int
	{
		return $this->traceId;
	}

	public function getRequestParams(): array
	{
		return $this->requestParams;
	}

	public function getRequestQuery(): array
	{
		return $this->requestQuery;
	}
}
