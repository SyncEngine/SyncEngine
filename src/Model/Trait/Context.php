<?php

namespace App\Model\Trait;

use App\Component\Context as ContextComponent;

trait Context
{
	private ContextComponent $context;

	public function setContext( $value, string $key ): void
	{
		$this->context[ $key ] = $value;
	}

	public function getContext( string $key = null ): mixed
	{
		if ( $key ) {
			return $this->context[ $key ] ?? null;
		}
		return $this->context;
	}
}
