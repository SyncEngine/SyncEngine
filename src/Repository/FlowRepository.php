<?php

namespace SyncEngine\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SyncEngine\Entity\Flow;
use SyncEngine\Repository\Abstract\EngineRepository;

/**
 * @extends EngineRepository<Flow>
 *
 * @method Flow|null find( $id, $lockMode = null, $lockVersion = null )
 * @method Flow|null findOneBy( array $criteria, array $orderBy = null )
 * @method Flow[]    findAll()
 * @method Flow[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 */
class FlowRepository extends EngineRepository
{
	public function __construct( ManagerRegistry $registry )
	{
		parent::__construct( $registry, Flow::class );
	}
}
