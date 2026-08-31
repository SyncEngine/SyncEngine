<?php

namespace SyncEngine\Form\Fields\Trait;

use SyncEngine\Form\Fields\AbstractFieldType;
use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;

/**
 * @extends AbstractFieldType
 */
trait NestedGroup
{
	public function setNested( FieldCollection|array $nested ): Static
	{
		return $this->setNestedFields( $nested );
	}

	public function getNested(): FieldCollection
	{
		return $this->getNestedFields();
	}

	public function setNestedFields( FieldCollection|array $fields ): static
	{
		if ( ! $fields instanceof FieldCollection ) {
			$fields = new FieldCollection( $fields );
		}

		parent::_set( 'nested', $fields );

		return $this;
	}

	public function getNestedFields(): FieldCollection
	{
		if ( ! isset( $this['nested'] ) ) {
			$this->_set( 'nested', new FieldCollection() );
		}

		return parent::_get( 'nested' );
	}

	public function getNestedField( string $name ): ?FieldConfigInterface
	{
		if ( isset( $this['nested'] ) ) {
			return $this['nested'][ $name ];
		}

		return null;
	}

	public function addNestedField( string $name, FieldConfigInterface|array $config ): static
	{
		if ( empty( $this['nested'] ) ) {
			$this['nested'] = new FieldCollection();
		}

		$this['nested'][ $name ] = $config;

		return $this;
	}

	public function removeNestedField( string $name ): static
	{
		if ( isset( $this['nested'] ) ) {
			unset( $this['nested'][ $name ] );
		}

		return $this;
	}
}
