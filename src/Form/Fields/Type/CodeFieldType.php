<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;
use SyncEngine\Form\Fields\Trait\Taggable;

class CodeFieldType extends AbstractFieldType
{
	use Taggable;

	public function getType(): string
	{
		return 'code';
	}

	public function getLanguage(): string
	{
		return (string) $this->_get( 'language', '' );
	}

	public function setLanguage( string $language ): static
	{
		$this->_set( 'language', $language );

		return $this;
	}

	public function isContained(): bool
	{
		return (bool) $this->_get( 'contained', false );
	}

	public function setContained( bool $contained ): static
	{
		$this->_set( 'contained', $contained );

		return $this;
	}

	public function getHeight(): string
	{
		return (string) $this->_get( 'height', '' );
	}

	public function setHeight( string $height ): static
	{
		$this->_set( 'height', $height );

		return $this;
	}
}
