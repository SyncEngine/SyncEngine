<?php

namespace SyncEngine\Service;

use SyncEngine\Structure\Data\ResourceData;

class ExecuteData extends ResourceData
{
	protected bool $raw = false;

	public function __construct( $array = [], int $flags = 0, string $iteratorClass = "ArrayIterator" ) {
		if ( ! is_iterable( $array ) && ! is_object( $array ) ) {
			$this->raw = true;
			$array = [ 'raw' => $array ];
		}

		parent::__construct( $array, $flags, $iteratorClass );
	}

	public function isRaw(): bool
	{
		return $this->raw;
	}

	public function get( $key = null, $default = null ): mixed
	{
		if ( $this->isRaw() ) {
			if ( $this->isKey( $key ) ) {
				return $default;
			}
			return parent::get( 'raw', $default );
		}

		return parent::get( $key, $default );
	}

	public function set( $value, $key = null ): static
	{
		if ( $this->isKey( $key ) ) {
			if ( $this->isRaw() ) {
				return $this; // @todo error?
			}

			return parent::set( $value, $key );
		}

		if ( ! is_iterable( $value ) && ! is_object( $value ) ) {
			$this->raw = true;
			return parent::set( $value, 'raw' );
		} else {
			$this->raw = false;
			return parent::set( $value );
		}
	}
}
