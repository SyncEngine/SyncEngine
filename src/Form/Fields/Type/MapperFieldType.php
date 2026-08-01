<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class MapperFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'mapper';
	}

	public function getConfig(): array
	{
		return $this->_get( 'config', [] );
	}

	public function setConfig( array $config ): static
	{
		$this->_set( 'config', $config );

		return $this;
	}

	public function getChoices(): string|array
	{
		return $this->_get( 'choices', [] );
	}

	public function setChoices( string|array $choices ): static
	{
		$this->_set( 'choices', $choices );

		return $this;
	}

	public function isCustomizable(): bool
	{
		return (bool) $this->_get( 'customizable', false );
	}

	public function setCustomizable( bool $customizable ): static
	{
		$this->_set( 'customizable', $customizable );

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

	public function isTaggable(): bool
	{
		return (bool) $this->_get( 'taggable', false );
	}

	public function setTaggable( bool $taggable ): static
	{
		$this->_set( 'taggable', $taggable );

		return $this;
	}

	public function getValues(): array
	{
		return $this->_get( 'values', [] );
	}

	public function setValues( array $values ): static
	{
		$this->_set( 'values', $values );

		return $this;
	}
}
