<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class ConditionsFieldType extends AbstractFieldType
{
	public function getType(): string
	{
		return 'conditions';
	}

	public function getConditionTypes(): array
	{
		return $this->_get( 'conditionTypes', [] );
	}

	public function setConditionTypes( array $conditionTypes ): static
	{
		$this->_set( 'conditionTypes', $conditionTypes );

		return $this;
	}

	public function getSource(): array
	{
		return $this->_get( 'source', [] );
	}

	public function setSource( array $source ): static
	{
		$this->_set( 'source', $source );

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

	public function isTaggable(): bool
	{
		return (bool) $this->_get( 'taggable', false );
	}

	public function setTaggable( bool $taggable ): static
	{
		$this->_set( 'taggable', $taggable );

		return $this;
	}
}
