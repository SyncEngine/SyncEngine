<?php

namespace SyncEngine\Exception;

class ExecuteStopException extends \ErrorException
{
	private mixed $debugInfo;

	public function __construct( string $message = "", mixed $debugInfo = null, int $code = 0, ?\Throwable $previous = null )
	{
		$this->debugInfo = $debugInfo;

		parent::__construct(
			$message,
			$code,
			$previous
		);
	}

	public function getDebugInfo(): mixed
	{
		return $this->debugInfo;
	}
}
