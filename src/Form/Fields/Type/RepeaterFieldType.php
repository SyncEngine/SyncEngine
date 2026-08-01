<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;
use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;

class RepeaterFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'repeater';
	}

	public function getFieldset(): array|FieldCollection
	{
		return $this->_get( 'fieldset', [] );
	}

	public function setFieldset( array|FieldCollection $fieldset ): static
	{
		$this->_set( 'fieldset', $fieldset );

		return $this;
	}

	public function getFields(): FieldCollection|array
	{
		return $this->getFieldset();
	}

	public function setFields( FieldCollection|array $fields ): static
	{
		return $this->setFieldset( $fields );
	}

	public function isInline(): bool
	{
		return (bool) $this->_get( 'inline', false );
	}

	public function setInline( bool $inline ): static
	{
		$this->_set( 'inline', $inline );

		return $this;
	}

	public function isSortable(): bool
	{
		return (bool) $this->_get( 'sortable', false );
	}

	public function setSortable( bool $sortable ): static
	{
		$this->_set( 'sortable', $sortable );

		return $this;
	}

	public function isLabeled(): bool
	{
		return (bool) $this->_get( 'labeled', false );
	}

	public function setLabeled( bool $labeled ): static
	{
		$this->_set( 'labeled', $labeled );

		return $this;
	}

	public function getActions(): array
	{
		return $this->_get( 'actions', [] );
	}

	public function setActions( array $actions ): static
	{
		$this->_set( 'actions', $actions );

		return $this;
	}

	public function getMax(): ?int
	{
		$value = $this->_get( 'max', null );

		return $value !== null ? (int) $value : null;
	}

	public function setMax( ?int $max ): static
	{
		$this->_set( 'max', $max );

		return $this;
	}

	public function getField( string $name ): ?FieldConfigInterface
	{
		$fields = $this->getFields();

		if ( $fields instanceof FieldCollection ) {
			return $fields->get( $name );
		}

		if ( isset( $fields[ $name ] ) ) {
			return $fields[ $name ];
		}

		return null;
	}
}
