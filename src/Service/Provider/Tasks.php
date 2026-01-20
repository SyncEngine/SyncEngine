<?php

namespace SyncEngine\Service\Provider;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\TaskModel;

/**
 * @extends AbstractServiceModelProvider<TaskModel>
 * @method TaskModel get( $tag )
 * @method TaskModel[] getAll()
 */
class Tasks extends AbstractServiceModelProvider
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
