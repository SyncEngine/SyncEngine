<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\Connection;

class ConnectionService
{
	public static function getConnection( int $id ): Connection
	{
		return DefaultController::getEntityManager()->getRepository( Connection::class )->findOneBy( [ 'id' => $id ] );
	}

	public static function getConnections(): array
	{
		return DefaultController::getEntityManager()->getRepository( Connection::class )->findAll();
	}
}
