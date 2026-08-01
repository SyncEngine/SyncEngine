<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class SchemaFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'schema';
	}

	public function getChoices(): array
	{
		return $this->_get( 'choices', [] );
	}

	public function setChoices( array $choices ): static
	{
		$this->_set( 'choices', $choices );

		return $this;
	}

	public function getSource(): string
	{
		return (string) $this->_get( 'source', '' );
	}

	public function setSource( string $source ): static
	{
		$this->_set( 'source', $source );

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
}
