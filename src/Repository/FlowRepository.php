<?php

namespace SyncEngine\Repository;

use SyncEngine\Entity\Flow;
use SyncEngine\Repository\Interface\Searchable;
use SyncEngine\Repository\Trait\Search;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Flow>
 *
 * @method Flow|null find( $id, $lockMode = null, $lockVersion = null )
 * @method Flow|null findOneBy( array $criteria, array $orderBy = null )
 * @method Flow[]    findAll()
 * @method Flow[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 */
class FlowRepository extends ServiceEntityRepository implements Searchable
{
	use Search;

	public function __construct( ManagerRegistry $registry )
	{
		parent::__construct( $registry, Flow::class );
	}

	public function save( Flow $entity, bool $flush = false ): void
	{
		$this->getEntityManager()->persist( $entity );

		if ( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove( Flow $entity, bool $flush = false ): void
	{
		$this->getEntityManager()->remove( $entity );

		if ( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	public function findOneByRef( $value ): ?Flow
	{
		return $this->createQueryBuilder( 'd' )
		            ->andWhere( 'd.ref = :val' )
		            ->setParameter( 'val', $value )
		            ->getQuery()
		            ->getOneOrNullResult();
	}

	public function findBySupervisorClassLocator( $classLocator )
	{
		return $this->createQueryBuilder( 'd' )->andWhere( 'd.supervisor LIKE :classLocator' )->setParameter(
			'classLocator',
			"%" . $classLocator . ":%"
		)->getQuery()->getResult();
	}

	//    /**
	//     * @return Flow[] Returns an array of Flow objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('f')
	//            ->andWhere('f.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('f.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?Flow
	//    {
	//        return $this->createQueryBuilder('f')
	//            ->andWhere('f.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
