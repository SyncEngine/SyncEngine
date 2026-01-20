<?php

namespace SyncEngine\Service\Provider;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\ColumnModel;

/**
 * @extends AbstractServiceModelProvider<ColumnModel>
 * @method ColumnModel get( $tag )
 * @method ColumnModel[] getAll()
 */
class Columns extends AbstractServiceModelProvider
{
	public function validate( ServiceModel $service ): bool
	{
		return $service instanceof ColumnModel;
	}

	public function getModelClass(): string
	{
		return ColumnModel::class;
	}
}
