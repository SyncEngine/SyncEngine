<?php

namespace SyncEngine\Form\Fields\Collection;

use SyncEngine\Form\Fields\AbstractFieldType;
use SyncEngine\Structure\Collection\AbstractCollection;

class FieldCollection extends AbstractCollection
{
	public function __construct( array $array = [] )
	{
		foreach ( $array as $key => $value ) {
			$this->offsetSet( $key, $value );
		}
	}

	public function add( string $name, array|AbstractFieldType $value ): static
	{
		$this->offsetSet( $name, $value );

		return $this;
	}

	public function transform( array|AbstractFieldType $field ): AbstractFieldType
	{
		if ( $field instanceof AbstractFieldType ) {
			return $field;
		}

		// @todo.
		return new InputFieldType( $field );
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		$this->offsetSet( $offset, $this->transform( $value ) );
	}
}
