<?php

namespace SyncEngine\Form\Fields\Trait;

use SyncEngine\Form\Fields\AbstractFieldType;

/**
 * @extends AbstractFieldType
 */
trait Taggable
{
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
