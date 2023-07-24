<?php

namespace App\Repository;

use App\Entity\Connection;
use App\Repository\Interface\Searchable;
use App\Repository\Trait\Search;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Connection>
 *
 * @method Connection|null find( $id, $lockMode = null, $lockVersion = null )
 * @method Connection|null findOneBy( array $criteria, array $orderBy = null )
 * @method Connection[]    findAll()
 * @method Connection[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 */
class ConnectionRepository extends ServiceEntityRepository implements Searchable
{
	use Search;

	public function __construct( ManagerRegistry $registry )
	{
		parent::__construct( $registry, Connection::class );
	}

	public function save( Connection $entity, bool $flush = false ): void
	{
		$this->getEntityManager()->persist( $entity );

		if ( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove( Connection $entity, bool $flush = false ): void
	{
		$this->getEntityManager()->remove( $entity );

		if ( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	public function findOneByRef( $value ): ?Connection
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
