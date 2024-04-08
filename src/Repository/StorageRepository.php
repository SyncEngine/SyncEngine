<?php

namespace SyncEngine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SyncEngine\Entity\Storage;
use SyncEngine\Repository\Interface\Searchable;
use SyncEngine\Repository\Trait\Search;

/**
 * @extends ServiceEntityRepository<Storage>
 *
 * @method Storage|null find( $id, $lockMode = null, $lockVersion = null )
 * @method Storage|null findOneBy( array $criteria, array $orderBy = null )
 * @method Storage[]    findAll()
 * @method Storage[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 */
class StorageRepository extends ServiceEntityRepository implements Searchable
{
	use Search;

	public function __construct( ManagerRegistry $registry )
	{
		parent::__construct( $registry, Storage::class );
	}

	public function save( Storage $entity, bool $flush = false ): void
	{
		$this->getEntityManager()->persist( $entity );

		if ( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	public function remove( Storage $entity, bool $flush = false ): void
	{
		$this->getEntityManager()->remove( $entity );

		if ( $flush ) {
			$this->getEntityManager()->flush();
		}
	}

	public function findOneByRef( $value ): ?Storage
	{
		return $this->createQueryBuilder( 'd' )
		            ->andWhere( 'd.ref = :val' )
		            ->setParameter( 'val', $value )
		            ->getQuery()
		            ->getOneOrNullResult();
	}

	/**
	 * @return Storage[] Returns an array of Storage objects
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

	public function findBySupervisorClassLocator( $classLocator )
	{
		return $this->createQueryBuilder( 'd' )->andWhere( 'd.supervisor LIKE :classLocator' )->setParameter(
			'classLocator',
			"%" . $classLocator . ":%"
		)->getQuery()->getResult();
	}
}
