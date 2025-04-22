<?php

namespace SyncEngine\Form\Fields\Collection;

use SyncEngine\Form\Fields\FieldTypeFactory;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;
use SyncEngine\Structure\Collection\AbstractCollection;

class FieldCollection extends AbstractCollection
{
	public function __construct( array $array = [] )
	{
		foreach ( $array as $key => $value ) {
			$this->offsetSet( $key, $value );
		}
	}

	public function add( string $name, array|FieldConfigInterface $value ): static
	{
		$this->offsetSet( $name, $value );

		return $this;
	}

	public function get( string $name ): ?FieldConfigInterface
	{
		return $this->offsetGet( $name );
	}

	public function remove( string $name ): static
	{
		$this->offsetUnset( $name );

		return $this;
	}

	public function transform( array|FieldConfigInterface $field ): FieldConfigInterface
	{
		if ( $field instanceof FieldConfigInterface ) {
			return $field;
		}

		return ( new FieldTypeFactory() )->create( $field );
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		parent::offsetSet( $offset, $this->transform( $value ) );
	}
}
