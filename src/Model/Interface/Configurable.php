<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Form\Fields\Collection\FieldCollection;

interface Configurable
{
	public function getFields(): FieldCollection|array;

	public function getConfig( $key = null, $default = null );
}
