<?php

namespace SyncEngine\Service\Locator;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\WebserviceModel;

/**
 * @extends AbstractServiceModelLocator<WebserviceModel>
 * @method WebserviceModel get( $tag )
 * @method WebserviceModel[] getAll()
 */
class Webservices extends AbstractServiceModelLocator
{
	public function validate( ServiceModel $service ): bool
	{
		return $service instanceof WebserviceModel;
	}

	public function getModelClass(): string
	{
		return WebserviceModel::class;
	}
}
