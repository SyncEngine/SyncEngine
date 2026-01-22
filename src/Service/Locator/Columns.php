<?php

namespace SyncEngine\Service\Locator;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\ColumnModel;

/**
 * @extends AbstractServiceModelLocator<ColumnModel>
 * @method ColumnModel get( $tag )
 * @method ColumnModel[] getAll()
 */
class Columns extends AbstractServiceModelLocator
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
