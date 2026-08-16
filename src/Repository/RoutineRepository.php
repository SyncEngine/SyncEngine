<?php

namespace SyncEngine\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SyncEngine\Entity\Routine;
use SyncEngine\Repository\Abstract\EngineRepository;
use SyncEngine\Repository\Trait\Supervisor;

/**
 * @extends EngineRepository<Routine>
 *
 * @method Routine|null find( $id, $lockMode = null, $lockVersion = null )
 * @method Routine|null findOneBy( array $criteria, array $orderBy = null )
 * @method Routine[]    findAll()
 * @method Routine[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 */
class RoutineRepository extends EngineRepository
{
	use Supervisor;

	public function __construct( ManagerRegistry $registry )
	{
		parent::__construct( $registry, Routine::class );
	}
}
