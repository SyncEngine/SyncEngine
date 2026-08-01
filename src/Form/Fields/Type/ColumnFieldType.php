<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class ColumnFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'column';
	}

	public function getColumnTypes(): array
	{
		return $this->_get( 'columnTypes', [] );
	}

	public function setColumnTypes( array $columnTypes ): static
	{
		$this->_set( 'columnTypes', $columnTypes );

		return $this;
	}

	public function getFilters(): array
	{
		return $this->_get( 'filters', [] );
	}

	public function setFilters( array $filters ): static
	{
		$this->_set( 'filters', $filters );

		return $this;
	}

	public function isCompact(): bool
	{
		return (bool) $this->_get( 'compact', false );
	}

	public function setCompact( bool $compact ): static
	{
		$this->_set( 'compact', $compact );

		return $this;
	}

	public function getView(): string
	{
		return (string) $this->_get( 'view', '' );
	}

	public function setView( string $view ): static
	{
		$this->_set( 'view', $view );

		return $this;
	}
}
