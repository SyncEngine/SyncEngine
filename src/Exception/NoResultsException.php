<?php

namespace SyncEngine\Exception;

class NoResultsException extends \Exception
{
	private mixed $data;

	public function __construct( string $message = "", mixed $data = null, int $code = 0, ?Throwable $previous = null )
	{
		$this->data = $data;

		parent::__construct(
			$message,
			$code,
			$previous
		);
	}

	public function getData(): mixed
	{
		return $this->data;
	}
}
