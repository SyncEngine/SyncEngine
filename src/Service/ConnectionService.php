<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\Connection;
use App\Model\ConnectionModel;

class ConnectionService
{
	public static function getConnection( int $id ): ConnectionModel|null
	{
		$connection = DefaultController::getEntityManager()->getRepository( Connection::class )->findOneBy( [ 'id' => $id ] );
		if ( $connection ) {
			return new ConnectionModel( $connection );
		}
		return null;
	}

	public static function getConnections(): array
	{
		$connections = DefaultController::getEntityManager()->getRepository( Connection::class )->findAll();
		$models = [];
		foreach ( $connections as $connection ) {
			$models[ $connection->getId() ] = new ConnectionModel( $connection );
		}
		return $models;
	}
}
