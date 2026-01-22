<?php

namespace SyncEngine\Service\Locator;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\TaskModel;

/**
 * @extends AbstractServiceModelLocator<TaskModel>
 * @method TaskModel get( $tag )
 * @method TaskModel[] getAll()
 */
class Tasks extends AbstractServiceModelLocator
{
	public function validate( ServiceModel $service ): bool
	{
		return $service instanceof TaskModel;
	}

	public function getModelClass(): string
	{
		return TaskModel::class;
	}
}
