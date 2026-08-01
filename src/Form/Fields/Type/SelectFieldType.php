<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class SelectFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'select';
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

	public function getGroup(): array
	{
		return $this->_get( 'group', [] );
	}

	public function setGroup( array $group ): static
	{
		$this->_set( 'group', $group );

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

	public function isAsync(): bool
	{
		return (bool) $this->_get( 'async', false );
	}

	public function setAsync( bool $async ): static
	{
		$this->_set( 'async', $async );

		return $this;
	}

	public function getVariant(): string
	{
		return (string) $this->_get( 'variant', '' );
	}

	public function setVariant( string $variant ): static
	{
		$this->_set( 'variant', $variant );

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

	public function getPrefix(): string|array
	{
		return $this->_get( 'prefix', '' );
	}

	public function setPrefix( string|array $prefix ): static
	{
		$this->_set( 'prefix', $prefix );

		return $this;
	}

	public function getPostfix(): string|array
	{
		return $this->_get( 'postfix', '' );
	}

	public function setPostfix( string|array $postfix ): static
	{
		$this->_set( 'postfix', $postfix );

		return $this;
	}
}
