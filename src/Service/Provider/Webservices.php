<?php

namespace SyncEngine\Service\Provider;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\WebserviceModel;

/**
 * @extends AbstractServiceModelProvider<WebserviceModel>
 * @method WebserviceModel get( $tag )
 * @method WebserviceModel[] getAll()
 */
class Webservices extends AbstractServiceModelProvider
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
