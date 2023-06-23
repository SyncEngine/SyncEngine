<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\Connection;
use App\Model\ConnectionModel;

class ConnectionService
{
	public static function getConnection( Connection|int $connection ): ConnectionModel|null
	{
		if ( ! $connection instanceof Connection ){
			$connection = DefaultController::getEntityManager()->getRepository( Connection::class )->findOneBy( [ 'id' => $connection ] );
		}
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
