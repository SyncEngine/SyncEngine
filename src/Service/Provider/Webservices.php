<?php

namespace SyncEngine\Service\Provider;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\WebserviceModel;

/**
 * @extends AbstractServiceModelProvider<WebserviceModel>
 */
class Webservices extends AbstractServiceModelProvider
{
	public function validate( ServiceModel $service ): bool
	{
		return $service instanceof WebserviceModel;
	}
}
