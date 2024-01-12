<?php

namespace SyncEngine\Service\Helper;

use Doctrine\ORM\Decorator\EntityManagerDecorator;

class EntityManagerSandbox extends EntityManagerDecorator
{
	public function flush( $entity = null ): void
	{
		// Nope.
	}
}
