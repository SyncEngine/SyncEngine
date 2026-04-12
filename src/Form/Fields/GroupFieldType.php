<?php

namespace SyncEngine\Form\Fields;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;

class GroupFieldType extends AbstractFieldType
{
	public function setFields( FieldCollection $fields ): static
	{
		$this['fields'] = $fields;

		return $this;
	}

	public function getFields(): FieldCollection
	{
		if ( ! isset( $this['fields'] ) ) {
			$this['fields'] = new FieldCollection();
		}

		return $this['fields'];
	}

	public function getField( string $name ): ?FieldConfigInterface
	{
		if ( isset( $this['fields'] ) ) {
			return $this['fields'][ $name ];
		}

		return null;
	}

	public function addField( string $name, FieldConfigInterface|array $config ): static
	{
		if ( empty( $this['fields'] ) ) {
			$this['fields'] = new FieldCollection();
		}

		$this['fields'][ $name ] = $config;

		return $this;
	}

	public function removeField( string $name ): static
	{
		if ( isset( $this['fields'] ) ) {
			unset( $this['fields'][ $name ] );
		}

		return $this;
	}
}
