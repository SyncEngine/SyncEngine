<?php

namespace SyncEngine\Form\Fields;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;

class NestedGroupFieldType extends AbstractFieldType
{
	public function setFields( FieldCollection $fields ): static
	{
		$this['nested'] = $fields;

		return $this;
	}

	public function getFields(): FieldCollection
	{
		if ( ! isset( $this['nested'] ) ) {
			$this['nested'] = new FieldCollection();
		}

		return $this['nested'];
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
