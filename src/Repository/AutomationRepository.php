<?php

namespace SyncEngine\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SyncEngine\Entity\Automation;
use SyncEngine\Repository\Abstract\EngineRepository;
use SyncEngine\Repository\Trait\Supervisor;

/**
 * @extends EngineRepository<Automation>
 *
 * @method Automation|null find( $id, $lockMode = null, $lockVersion = null )
 * @method Automation|null findOneBy( array $criteria, array $orderBy = null )
 * @method Automation[]    findAll()
 * @method Automation[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 */
class AutomationRepository extends EngineRepository
{
	use Supervisor;

	public function __construct( ManagerRegistry $registry )
	{
		parent::__construct( $registry, Automation::class );
	}
}
