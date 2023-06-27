<?php

namespace App\Component;

class Context implements \ArrayAccess
{
	protected array $context = [];

	public function getContext(): array
	{
		return $this->context;
	}

	public function offsetExists( mixed $offset ): bool
	{
		return isset( $this->context[ $offset ] );
	}

	public function offsetGet( mixed $offset ): mixed
	{
		return $this->context[ $offset ] ?? null;
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		if ( is_null( $offset ) ) {
			$this->context[] = $value;
		} else {
			$this->context[ $offset ] = $value;
		}
	}

	public function offsetUnset( mixed $offset ): void
	{
		unset( $this->context[ $offset ] );
	}
}
