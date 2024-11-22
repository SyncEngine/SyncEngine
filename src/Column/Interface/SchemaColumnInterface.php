<?php

namespace SyncEngine\Column\Interface;

use SyncEngine\Service\Data\SchemaData;

interface SchemaColumnInterface
{
	/**
	 * @return SchemaData
	 */
	public function getSchemaColumns(): SchemaData;
}
