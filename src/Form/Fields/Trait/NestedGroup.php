<?php

namespace SyncEngine\Form\Fields;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;

class NestedGroupFieldType extends AbstractFieldType
{
	public function setNested( FieldCollection|array $nested ): Static
	{
		return $this->setFields( $nested );
	}

	public function getNested(): FieldCollection
	{
		return $this->getFields();
	}

	public function setFields( FieldCollection|array $fields ): static
	{
		if ( ! $fields instanceof FieldCollection ) {
			$fields = new FieldCollection( $fields );
		}

		parent::_set( 'nested', $fields );

		return $this;
	}

	public function getFields(): FieldCollection
	{
		if ( ! isset( $this['nested'] ) ) {
			$this->_set( 'nested', new FieldCollection() );
		}

		return parent::_get( 'nested' );
	}

	public function getField( string $name ): ?FieldConfigInterface
	{
		if ( isset( $this['nested'] ) ) {
			return $this['nested'][ $name ];
		}

		return null;
	}

	public function addField( string $name, FieldConfigInterface|array $config ): static
	{
		if ( empty( $this['nested'] ) ) {
			$this['nested'] = new FieldCollection();
		}

		$this['nested'][ $name ] = $config;

		return $this;
	}

	public function removeField( string $name ): static
	{
		if ( isset( $this['nested'] ) ) {
			unset( $this['nested'][ $name ] );
		}

		return $this;
	}
}
