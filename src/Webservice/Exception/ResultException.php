<?php

namespace SyncEngine\Webservice\Exception;

class ResultException extends \Exception
{
	public function __construct(
		private readonly string|\Exception $e,
		private readonly array $debugInfo = [],
		int                    $code = 200,
		\Throwable             $previous = null
	) {
		if ( ! $previous && $e instanceof \Exception ) {
			$previous = $e;
		}

		parent::__construct(
			is_string( $e ) ? $e : $e->getMessage(),
			$code,
			$previous,
		);
	}

	public function getDebugInfo(): array
	{
		$previous = $this->getPrevious();
		if ( $previous instanceof ResultException ) {
			return array_merge( $previous->getDebugInfo(), $this->debugInfo );
		}

		return $this->debugInfo;
	}

	public function getResponse()
	{
		if ( $this->e instanceof \Throwable ) {
			if ( method_exists( $this->e, 'getResponse' ) ) {
				return $this->e->getResponse();
			}
		}

		$previous = $this->getPrevious();

		if ( $previous && method_exists( $previous, 'getResponse' ) ) {
			return $previous->getResponse();
		}

		return null;
	}
}
