<?php

namespace SyncEngine\Webservice\Helper;

class ResultException extends \Exception
{
	public function __construct(
		private readonly \Exception $e,
		private readonly array $debugInfo = []
	) {
		parent::__construct(
			$e->getMessage(),
			$e->getCode(),
			$e->getPrevious(),
		);
	}

	public function getDebugInfo(): array
	{
		return $this->debugInfo;
	}

	public function getResponse()
	{
		if ( method_exists( $this->e, 'getResponse' ) ) {
			return $this->e->getResponse();
		}
		return null;
	}
}
