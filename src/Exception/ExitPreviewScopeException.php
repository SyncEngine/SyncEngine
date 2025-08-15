<?php

namespace SyncEngine\Exception;

use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;

class ExitPreviewScopeException extends \Exception
{
	public static bool $SYNCENGINE_EXITPREVIEW = true;
	protected ExecuteData $data;
	protected ExecuteContext $context;

	public function __construct(
		ExecuteData $data,
		ExecuteContext $context,
		string $message = "SyncEngine Exit Preview",
		int $code = 0,
		?\Throwable $previous = null
	) {
		parent::__construct( $message, $code, $previous );
		$this->data    = $data;
		$this->context = $context;
	}

	public function getData(): ExecuteData
	{
		return $this->data;
	}

	public function getContext(): ExecuteContext
	{
		return $this->context;
	}
}
