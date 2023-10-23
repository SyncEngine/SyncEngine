<?php

namespace App\Service;

use Doctrine\ORM\Decorator\EntityManagerDecorator;

class EntityManagerSandbox extends EntityManagerDecorator
{
	public function flush( $entity = null )
	{
		// Nope.
	}
}
