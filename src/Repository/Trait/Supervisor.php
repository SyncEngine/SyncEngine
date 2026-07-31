<?php

namespace SyncEngine\Repository\Trait;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use SyncEngine\Model\Interface\Supervisable;

/**
 * @extends ServiceEntityRepository<Supervisable>
 */
trait Supervisor
{
	public function findBySupervisorClassLocator( $classLocator )
	{
		return $this->createQueryBuilder( 'd' )->andWhere( 'd.supervisor LIKE :classLocator' )->setParameter(
			'classLocator',
			"%" . $classLocator . ":%"
		)->getQuery()->getResult();
	}
}
