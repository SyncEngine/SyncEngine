<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class InputFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'text';
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

	public function getPlaceholder(): string
	{
		return (string) $this->_get( 'placeholder', '' );
	}

	public function setPlaceholder( string $placeholder ): static
	{
		$this->_set( 'placeholder', $placeholder );

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

	public function isMultiline(): bool|string
	{
		return $this->_get( 'multiline', false );
	}

	public function setMultiline( bool|string $multiline ): static
	{
		$this->_set( 'multiline', $multiline );

		return $this;
	}

	public function getAttr(): array
	{
		return $this->_get( 'attr', [] );
	}

	public function setAttr( array $attr ): static
	{
		$this->_set( 'attr', $attr );

		return $this;
	}
}
