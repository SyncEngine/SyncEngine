<?php

namespace SyncEngine\Service\Locator;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\NotifierModel;

/**
 * @extends AbstractServiceModelLocator<NotifierModel>
 * @method NotifierModel get( $tag )
 * @method NotifierModel[] getAll()
 */
class Notifiers extends AbstractServiceModelLocator
{
	public function validate( ServiceModel $service ): bool
	{
		return $service instanceof NotifierModel;
	}

	public function getModelClass(): string
	{
		return NotifierModel::class;
	}
}
