<?php

namespace SyncEngine\Form\Fields\Collection;

use SyncEngine\Form\Fields\FieldTypeFactory;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;
use SyncEngine\Service\ConditionsValidator;
use SyncEngine\Structure\Collection\AbstractCollection;

/**
 * @extends AbstractCollection<FieldConfigInterface>
 */
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

	public function generateLabels( array $fields = [] ): static
	{
		$fields = $fields ? array_intersect_key( $this->collection, array_flip( $fields ) ) : $this->collection;
		foreach ( $fields as $name => $field ) {
			if ( $field->getLabel() ) {
				continue;
			}
			if ( $field instanceof FieldConfigInterface ) {
				$field->generateLabel( $field->getName() ?: $name );
			}
		}

		return $this;
	}

	public function createField( array|FieldConfigInterface $field ): FieldConfigInterface
	{
		if ( $field instanceof FieldConfigInterface ) {
			return $field;
		}

		return ( new FieldTypeFactory() )->create( $field );
	}

	public function bulkEdit( array|callable $changes, ?array $conditions = null ): static
	{
		$validator = new ConditionsValidator();
		foreach ( $this as $field ) {
			if ( ! $conditions || $validator->validate( $field, $conditions ) ) {
				if ( is_callable( $changes ) ) {
					$changes( $field );
					continue;
				}
				foreach ( $changes as $key => $change ) {
					$field[ $key ] = $change;
				}
			}
		}

		return $this;
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		parent::offsetSet( $offset, $this->createField( $value ) );
	}
}
