<?php

namespace SyncEngine\Service\Sandbox;

use Doctrine\ORM\Decorator\EntityManagerDecorator;

class EntityManagerSandbox extends EntityManagerDecorator
{
	public function flush( $entity = null ): void
	{
		// Nope.
	}
}
