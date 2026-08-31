<?php

namespace SyncEngine\Form\Fields\Type\Structure;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;
use SyncEngine\Form\Fields\Trait\NestedGroup;

class NestedGroupFieldType extends GroupFieldType
{
	use NestedGroup;

	public function setNested( FieldCollection|array $nested ): static
	{
		return $this->setNestedFields( $nested );
	}

	public function getNested(): FieldCollection
	{
		return $this->getNestedFields();
	}

	public function setFields( FieldCollection|array $fields ): static
	{
		return $this->setNestedFields( $fields );
	}

	public function getFields(): FieldCollection
	{
		return $this->getNestedFields();
	}

	public function getField( string $name ): ?FieldConfigInterface
	{
		return $this->getNestedField( $name );
	}

	public function addField( string $name, FieldConfigInterface|array $config ): static
	{
		return $this->addNestedField( $name, $config );
	}

	public function removeField( string $name ): static
	{
		return $this->removeNestedField( $name );
	}
}
