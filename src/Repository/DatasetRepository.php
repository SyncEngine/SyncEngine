<?php

namespace App\Repository;

use App\Entity\Dataset;
use App\Repository\Interface\Searchable;
use App\Repository\Trait\Search;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dataset>
 *
 * @method Dataset|null find( $id, $lockMode = null, $lockVersion = null )
 * @method Dataset|null findOneBy( array $criteria, array $orderBy = null )
 * @method Dataset[]    findAll()
 * @method Dataset[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 */
class DatasetRepository extends ServiceEntityRepository implements Searchable
{
	use Search;

	public function __construct( ManagerRegistry $registry )
	{
		parent::__construct( $registry, Dataset::class );
	}

	public function save( Dataset $entity, bool $flush = false ): void
	{
		$this->getEntityManager()->persist( $entity );

		if ( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove( Dataset $entity, bool $flush = false ): void
	{
		$this->getEntityManager()->remove( $entity );

		if ( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	public function findOneByRef( $value ): ?Dataset
	{
		return $this->createQueryBuilder( 'd' )
		            ->andWhere( 'd.ref = :val' )
		            ->setParameter( 'val', $value )
		            ->getQuery()
		            ->getOneOrNullResult();
	}

	/**
	 * @return Dataset[] Returns an array of Dataset objects
	 */
	public function findByType( $value ): array
	{
		return $this->createQueryBuilder( 'd' )
		            ->andWhere( 'd.type = :val' )
		            ->setParameter( 'val', $value )
					//->orderBy( 'd.id', 'ASC' )
					//->setMaxResults( 10 )
			        ->getQuery()
			        ->getResult();
	}
}
