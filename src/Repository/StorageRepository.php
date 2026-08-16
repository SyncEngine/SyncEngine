<?php

namespace SyncEngine\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SyncEngine\Entity\Storage;
use SyncEngine\Repository\Abstract\EngineRepository;

/**
 * @extends EngineRepository<Storage>
 *
 * @method Storage|null find( $id, $lockMode = null, $lockVersion = null )
 * @method Storage|null findOneBy( array $criteria, array $orderBy = null )
 * @method Storage[]    findAll()
 * @method Storage[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 */
class StorageRepository extends EngineRepository
{
	public function __construct( ManagerRegistry $registry )
	{
		parent::__construct( $registry, Storage::class );
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
}
