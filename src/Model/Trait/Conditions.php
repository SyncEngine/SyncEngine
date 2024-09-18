<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Service\Conditions as ConditionsService;

trait Conditions
{
	public function validateConditions( array $conditions, mixed $data = null ): bool
	{
		return ( new ConditionsService() )->validate( $conditions, $data );
	}
}
