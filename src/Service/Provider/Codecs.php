<?php

namespace SyncEngine\Service\Provider;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\CodecModel;

/**
 * @extends AbstractServiceModelProvider<CodecModel>
 */
class Codecs extends AbstractServiceModelProvider
{
	public function validate( ServiceModel $service ): bool
	{
		return $service instanceof CodecModel;
	}

	public function get( $name ): ?ServiceModel
	{
		// Support all lowercase.
		return parent::get( ucfirst( $name ) );
	}
}
