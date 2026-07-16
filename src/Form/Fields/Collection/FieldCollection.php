<?php

namespace SyncEngine\Form\Fields\Collection;

use SyncEngine\Form\Fields\FieldTypeFactory;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;
use SyncEngine\Service\ConditionsValidator;
use SyncEngine\Structure\Collection\AbstractCollection;
use SyncEngine\Structure\Data\ResourceData;

/**
 * @extends AbstractCollection<FieldConfigInterface>
 */
class FieldCollection extends AbstractCollection
{
	public function __construct( array $array = [] )
	{
		foreach ( $array as $key => $value ) {
			$this->add( $key, $value );
		}
	}

	public function merge( FieldCollection $collection ): static
	{
		$new = clone $this;

		foreach ( $collection as $name => $field ) {
			$new[ $name ] = clone $field;
		}

		return $new;
	}

	/**
	 * @todo Introduce more complex filters instad of just key=>value pairs.
	 *
	 * @param  array  $filters
	 * @param  bool   $strict   Whether to reject fields that don't have the specified filter key.'
	 */
	public function filter( iterable $filters, bool $strict = false ): FieldCollection
	{
		$new = clone $this;

		foreach ( $new as $index => $item ) {
			foreach ( $filters as $key => $filter ) {
				if ( $strict ) {
					if ( ! isset( $item[ $key ] ) || $item[ $key ] !== $filter ) {
						unset( $new[ $index ] );
					}
				} else {
					if ( ! empty( $item[ $key ] ) && $item[ $key ] !== $filter ) {
						unset( $new[ $index ] );
					}
				}
			}
		}

		return $new;
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

	public function bulkEdit( array|callable $changes, ?array $conditions = null, bool $recursive = false ): static
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

					if ( $recursive ) {
						foreach ( $field as $value ) {
							if ( $value instanceof FieldCollection ) {
								$value->bulkEdit( $changes, $conditions, $recursive );
							}
						}
					}
				}
			}
		}

		return $this;
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		parent::offsetSet( $offset, $this->createField( $value ) );
	}

	public function __clone(): void
	{
		foreach ( $this as $key => $field ) {
			$this[ $key ] = clone $field;
		}
	}

	public function __toString(): string
	{
		return json_encode( $this->normalize() );
	}

	public function normalize(): array
	{
		return ResourceData::create( $this->collection )->normalize();
	}
}
