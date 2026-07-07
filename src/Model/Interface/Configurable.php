<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Form\Fields\Collection\FieldCollection;

interface Configurable
{
	/**
	 * Returns the fields available for this entity.
	 *
	 * @return FieldCollection
	 */
	public function getFields(): FieldCollection;

	/**
	 * Returns the configuration data.
	 *
	 * @param  null  $key
	 * @param  null  $default
	 *
	 * @return mixed
	 */
	public function getConfig( $key = null, $default = null ): mixed;
}
