<?php

namespace SyncEngine\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SyncEngine\Entity\Connection;
use SyncEngine\Repository\Abstract\EngineRepository;

/**
 * @extends EngineRepository<Connection>
 *
 * @method Connection|null find( $id, $lockMode = null, $lockVersion = null )
 * @method Connection|null findOneBy( array $criteria, array $orderBy = null )
 * @method Connection[]    findAll()
 * @method Connection[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 */
class ConnectionRepository extends EngineRepository
{
	public function __construct( ManagerRegistry $registry )
	{
		parent::__construct( $registry, Connection::class );
	}
}
