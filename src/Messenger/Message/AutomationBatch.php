<?php

namespace SyncEngine\Messenger\Message;

use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class AutomationBatch
{
	private array $request;
	private array $query;

	public function __construct(
		private int $automationId,
		private int $traceId,
		?Request $request = null,
	) {
		if ( $request ) {
			$this->request = $request->request->all();
			$this->query = $request->query->all();
		}
	}

	public function getAutomationId(): int
	{
		return $this->automationId;
	}

	public function getTraceId(): int
	{
		return $this->traceId;
	}

	public function getRequest(): array
	{
		return $this->request;
	}

	public function getQuery(): array
	{
		return $this->query;
	}
}
