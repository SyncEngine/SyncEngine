<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class AuthenticationFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'authentication';
	}

	public function getWebserviceTypes(): array
	{
		return $this->_get( 'webserviceTypes', [] );
	}

	public function setWebserviceTypes( array $webserviceTypes ): static
	{
		$this->_set( 'webserviceTypes', $webserviceTypes );

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
}
