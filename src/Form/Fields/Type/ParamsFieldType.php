<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class ParamsFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'params';
	}

	public function getColumns(): array
	{
		return $this->_get( 'columns', [] );
	}

	public function setColumns( array $columns ): static
	{
		$this->_set( 'columns', $columns );

		return $this;
	}

	public function getFormats(): array
	{
		return $this->_get( 'formats', [] );
	}

	public function setFormats( array $formats ): static
	{
		$this->_set( 'formats', $formats );

		return $this;
	}

	public function getFormat(): string|array
	{
		return $this->_get( 'format', '' );
	}

	public function setFormat( string|array $format ): static
	{
		$this->_set( 'format', $format );

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

	public function isTaggable(): bool
	{
		return (bool) $this->_get( 'taggable', false );
	}

	public function setTaggable( bool $taggable ): static
	{
		$this->_set( 'taggable', $taggable );

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
}
