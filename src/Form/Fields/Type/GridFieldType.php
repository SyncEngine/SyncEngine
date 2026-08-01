<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class GridFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'grid';
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

	public function isNested(): bool
	{
		return (bool) $this->_get( 'nested', false );
	}

	public function setNested( bool $nested ): static
	{
		$this->_set( 'nested', $nested );

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

	public function isRemovable(): bool
	{
		return (bool) $this->_get( 'removable', false );
	}

	public function setRemovable( bool $removable ): static
	{
		$this->_set( 'removable', $removable );

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

	public function getIndexed(): bool|string
	{
		return $this->_get( 'indexed', false );
	}

	public function setIndexed( bool|string $indexed ): static
	{
		$this->_set( 'indexed', $indexed );

		return $this;
	}
}
