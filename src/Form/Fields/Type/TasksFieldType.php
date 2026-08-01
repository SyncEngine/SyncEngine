<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class TasksFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'tasks';
	}

	public function getTaskTypes(): array
	{
		return $this->_get( 'taskTypes', [] );
	}

	public function setTaskTypes( array $taskTypes ): static
	{
		$this->_set( 'taskTypes', $taskTypes );

		return $this;
	}

	public function getQuery(): array
	{
		return $this->_get( 'query', [] );
	}

	public function setQuery( array $query ): static
	{
		$this->_set( 'query', $query );

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

	public function getMax(): ?int
	{
		$value = $this->_get( 'max', null );

		return $value !== null ? (int) $value : null;
	}

	public function setMax( ?int $max ): static
	{
		$this->_set( 'max', $max );

		return $this;
	}
}
