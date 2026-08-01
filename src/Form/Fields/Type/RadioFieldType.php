<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class RadioFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'radio';
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

	public function getButton(): bool|string|array
	{
		return $this->_get( 'button', false );
	}

	public function setButton( bool|string|array $button ): static
	{
		$this->_set( 'button', $button );

		return $this;
	}

	public function isInline(): bool
	{
		return (bool) $this->_get( 'inline', false );
	}

	public function setInline( bool $inline ): static
	{
		$this->_set( 'inline', $inline );

		return $this;
	}

	public function isVertical(): bool
	{
		return (bool) $this->_get( 'vertical', false );
	}

	public function setVertical( bool $vertical ): static
	{
		$this->_set( 'vertical', $vertical );

		return $this;
	}
}
