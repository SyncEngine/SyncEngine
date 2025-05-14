<?php

namespace SyncEngine\Exception;

class ExecuteException extends \Exception
{
	private mixed $debugInfo;

	public function __construct( string $message = "", mixed $debugInfo = null, int $code = 0, ?\Throwable $previous = null )
	{
		$this->debugInfo = $debugInfo;

		parent::__construct(
			$message ?: $previous?->getMessage(),
			$code ?: $previous?->getCode(),
			$previous
		);
	}

	public function getDebugInfo(): mixed
	{
		return $this->debugInfo;
	}
}
