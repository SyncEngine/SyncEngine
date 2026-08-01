<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class CodecFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'codec';
	}

	public function getCodecTypes(): array
	{
		return $this->_get( 'codecTypes', [] );
	}

	public function setCodecTypes( array $codecTypes ): static
	{
		$this->_set( 'codecTypes', $codecTypes );

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

	public function getDirection(): string
	{
		return (string) $this->_get( 'direction', '' );
	}

	public function setDirection( string $direction ): static
	{
		$this->_set( 'direction', $direction );

		return $this;
	}
}
