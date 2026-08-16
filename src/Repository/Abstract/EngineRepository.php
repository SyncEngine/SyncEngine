<?php

namespace SyncEngine\Repository\Abstract;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use SyncEngine\Entity\Abstract\EngineEntity;
use SyncEngine\Repository\Interface\Searchable;
use SyncEngine\Repository\Trait\Search;
use SyncEngine\Repository\Trait\Supervisor;

/**
 * @template T of EngineEntity
 *
 * @extends ServiceEntityRepository<T>
 *
 * @method EngineEntity|null find( $id, $lockMode = null, $lockVersion = null )
 * @method EngineEntity|null findOneBy( array $criteria, array $orderBy = null )
 * @method EngineEntity[]    findAll()
 * @method EngineEntity[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 */
abstract class EngineRepository extends ServiceEntityRepository implements Searchable
{
	use Search;
	use Supervisor;

	/**
	 * @param  T  $entity
	 */
	public function save( EngineEntity $entity, bool $flush = false ): void
	{
		$this->getEntityManager()->persist( $entity );

		if ( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	/**
	 * @param  T  $entity
	 */
	public function remove( EngineEntity $entity, bool $flush = false ): void
	{
		$this->getEntityManager()->remove( $entity );

		if ( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	/**
	 * @return  T|null  $entity
	 */
	public function findOneByRef( $value ): ?EngineEntity
	{
		return $this->createQueryBuilder( 'd' )
		            ->andWhere( 'd.ref = :val' )
		            ->setParameter( 'val', $value )
		            ->getQuery()
		            ->getOneOrNullResult();
	}

	//    /**
	//     * @return Connection[] Returns an array of Connection objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('c')
	//            ->andWhere('c.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('c.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?Connection
	//    {
	//        return $this->createQueryBuilder('c')
	//            ->andWhere('c.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
