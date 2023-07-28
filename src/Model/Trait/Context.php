<?php

namespace App\Model\Trait;

use App\Service\Context as ContextService;

trait Context
{
	private ContextService $context;

	public function setContext( $value, string $key ): void
	{
		if ( ! isset( $this->context ) ) {
			$this->context = new ContextService();
		}

		$this->context[ $key ] = $value;
	}

	public function getContext( string $key = null ): mixed
	{
		if ( ! isset( $this->context ) ) {
			$this->context = new ContextService();
		}

		if ( $key ) {
			return $this->context[ $key ] ?? null;
		}

		return $this->context;
	}
}
