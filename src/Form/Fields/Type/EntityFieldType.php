<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class EntityFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'entity';
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

	public function getActions(): array
	{
		return $this->_get( 'actions', [] );
	}

	public function setActions( array $actions ): static
	{
		$this->_set( 'actions', $actions );

		return $this;
	}

	public function isSelectable(): bool
	{
		return (bool) $this->_get( 'selectable', false );
	}

	public function setSelectable( bool $selectable ): static
	{
		$this->_set( 'selectable', $selectable );

		return $this;
	}

	public function getPrefix(): string|array
	{
		return $this->_get( 'prefix', '' );
	}

	public function setPrefix( string|array $prefix ): static
	{
		$this->_set( 'prefix', $prefix );

		return $this;
	}
}
