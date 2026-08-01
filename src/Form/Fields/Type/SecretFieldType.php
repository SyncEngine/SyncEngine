<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class SecretFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'secret';
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
}
