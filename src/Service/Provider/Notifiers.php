<?php

namespace SyncEngine\Service\Provider;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\NotifierModel;

/**
 * @extends AbstractServiceModelProvider<NotifierModel>
 * @method NotifierModel get( $tag )
 * @method NotifierModel[] getAll()
 */
class Notifiers extends AbstractServiceModelProvider
{
	public function validate( ServiceModel $service ): bool
	{
		return $service instanceof NotifierModel;
	}
}
