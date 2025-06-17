<?php

namespace SyncEngine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SyncEngine\Entity\Routine;
use SyncEngine\Repository\Interface\Searchable;
use SyncEngine\Repository\Trait\Search;
use SyncEngine\Repository\Trait\Supervisor;

/**
 * @extends ServiceEntityRepository<Routine>
 *
 * @method Routine|null find( $id, $lockMode = null, $lockVersion = null )
 * @method Routine|null findOneBy( array $criteria, array $orderBy = null )
 * @method Routine[]    findAll()
 * @method Routine[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 */
class RoutineRepository extends ServiceEntityRepository implements Searchable
{
	use Search;
	use Supervisor;

	public function __construct( ManagerRegistry $registry )
	{
		parent::__construct( $registry, Routine::class );
	}

	public function save( Routine $entity, bool $flush = false ): void
	{
		$this->getEntityManager()->persist( $entity );

		if ( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove( Routine $entity, bool $flush = false ): void
	{
		$this->getEntityManager()->remove( $entity );

		if ( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	public function findOneByRef( $value ): ?Routine
	{
		return $this->createQueryBuilder( 'd' )
		            ->andWhere( 'd.ref = :val' )
		            ->setParameter( 'val', $value )
		            ->getQuery()
		            ->getOneOrNullResult();
	}

	//    /**
	//     * @return Routine[] Returns an array of Routine objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('s')
	//            ->andWhere('s.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('s.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?Routine
	//    {
	//        return $this->createQueryBuilder('s')
	//            ->andWhere('s.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
