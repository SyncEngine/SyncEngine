<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Form\Fields\Collection\FieldCollection;

interface Configurable
{
	/**
	 * Returns the fields available for this entity.
	 *
	 * @return array|FieldCollection
	 */
	public function getFields(): FieldCollection|array;

	public function getConfig( $key = null, $default = null );
}
