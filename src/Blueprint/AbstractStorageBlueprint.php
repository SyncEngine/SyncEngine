<?php

namespace SyncEngine\Blueprint;

use SyncEngine\Exception\InvalidConfigException;
use SyncEngine\Model\BlueprintModel;
use SyncEngine\Model\StorageModel;

abstract class AbstractStorageBlueprint extends BlueprintModel
{
	protected $storageType = '';

	public function init(): void
	{
		parent::init();
		$this->entity = StorageModel::getModelName();
	}

	public function beforeUpdate(): void
	{
		$model = $this->getSupervisable();

		if ( ! $model instanceof StorageModel ) {
			throw new InvalidConfigException( 'Invalid model' );
		}

		if ( $this->storageType ) {
			$model->setType( $this->storageType );
		}
	}
}
