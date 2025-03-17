<?php

namespace SyncEngine\Column\Interface;

use SyncEngine\Structure\Data\SchemaData;

interface SchemaColumnInterface
{
	/**
	 * @return SchemaData
	 */
	public function getSchemaColumns(): SchemaData;
}
