<?php

namespace SyncEngine\Column\Interface;

use SyncEngine\Model\ColumnModel;

interface SchemaColumnInterface
{
	/**
	 * @return ColumnModel[]
	 */
	public function getSchemaColumns(): array;
}
