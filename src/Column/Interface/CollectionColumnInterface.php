<?php

namespace SyncEngine\Column\Interface;

use SyncEngine\Model\ColumnModel;

interface CollectionColumnInterface
{
	public function getCollectionColumn(): ColumnModel;
}
