<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class EntitiesFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'entities';
	}

	public function getEntity(): string
	{
		return (string) $this->_get( 'entity', '' );
	}

	public function setEntity( string $entity ): static
	{
		$this->_set( 'entity', $entity );

		return $this;
	}

	public function getConfig(): string|array
	{
		return $this->_get( 'config', '' );
	}

	public function setConfig( string|array $config ): static
	{
		$this->_set( 'config', $config );

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

	public function getChoices(): array
	{
		return $this->_get( 'choices', [] );
	}

	public function setChoices( array $choices ): static
	{
		$this->_set( 'choices', $choices );

		return $this;
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

	public function getActions(): array
	{
		return $this->_get( 'actions', [] );
	}

	public function setActions( array $actions ): static
	{
		$this->_set( 'actions', $actions );

		return $this;
	}

	public function getCreate(): bool|string
	{
		return $this->_get( 'create', false );
	}

	public function setCreate( bool|string $create ): static
	{
		$this->_set( 'create', $create );

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

	public function getActiveKey(): string
	{
		return (string) $this->_get( 'activeKey', '' );
	}

	public function setActiveKey( string $activeKey ): static
	{
		$this->_set( 'activeKey', $activeKey );

		return $this;
	}
}
