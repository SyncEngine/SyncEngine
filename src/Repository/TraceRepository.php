<?php

namespace SyncEngine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SyncEngine\Entity\Trace;
use SyncEngine\Model\Enum\TraceStatus;

/**
 * @extends ServiceEntityRepository<Trace>
 *
 * @method Trace|null find( $id, $lockMode = null, $lockVersion = null )
 * @method Trace|null findOneBy( array $criteria, array $orderBy = null )
 * @method Trace[]    findAll()
 * @method Trace[]    findBy( array $criteria, array $orderBy = null, $limit = null, $offset = null )
 */
class TraceRepository extends ServiceEntityRepository
{
	public function __construct( ManagerRegistry $registry )
	{
		parent::__construct( $registry, Trace::class );
	}

	/**
	 * Check whether at least one trace matching the given criteria exists.
	 *
	 * Supported criteria keys:
	 *  - automation    int
	 *  - status        TraceStatus
	 *  - updatedAfter \DateTimeImmutable  (inclusive, t.updatedAt >= value)
	 *  - updatedBefore \DateTimeImmutable (exclusive, t.updatedAt < value)
	 */
	public function existsBy( array $criteria ): bool
	{
		$result = $this->buildRowsCriteriaQuery( $criteria )
		               ->select( 't.id' )
		               ->setMaxResults( 1 )
		               ->getQuery()
		               ->getScalarResult();

		return ! empty( $result );
	}

	/**
	 * Return lightweight scalar rows — no entity hydration — ordered newest-updated first.
	 * Suitable for state checks and UI summaries.
	 * Dates are returned as Unix timestamps.
	 *
	 * Supported criteria keys: same as existsBy().
	 *
	 * @return array<int, array{ id: int, status: string, createdAt: int, updatedAt: int }>
	 */
	public function findRowsBy( array $criteria ): array
	{
		$rows = $this->buildRowsCriteriaQuery( $criteria )
		             ->select( 't.id, t.status, t.createdAt, t.updatedAt' )
		             ->orderBy( 't.updatedAt', 'DESC' )
		             ->getQuery()
		             ->getArrayResult();

		return array_map( static function ( array $row ): array {
			return [
				'id'        => (int) $row['id'],
				'status'    => (string) $row['status'],
				'createdAt' => $row['createdAt'] instanceof \DateTimeInterface ? $row['createdAt']->getTimestamp() : 0,
				'updatedAt' => $row['updatedAt'] instanceof \DateTimeInterface ? $row['updatedAt']->getTimestamp() : 0,
			];
		}, $rows );
	}

	private function buildRowsCriteriaQuery( array $criteria ): QueryBuilder
	{
		$qb = $this->createQueryBuilder( 't' );

		if ( isset( $criteria['automation'] ) ) {
			$qb->andWhere( 't.automation = :automation' )
			   ->setParameter( 'automation', $criteria['automation'] );
		}

		if ( isset( $criteria['status'] ) ) {
			$status = $criteria['status'] instanceof TraceStatus
				? $criteria['status']->value
				: $criteria['status'];

			$qb->andWhere( 't.status = :status' )
			   ->setParameter( 'status', $status );
		}

		if ( isset( $criteria['updatedAfter'] ) ) {
			$qb->andWhere( 't.updatedAt >= :updatedAfter' )
			   ->setParameter( 'updatedAfter', $criteria['updatedAfter'] );
		}

		if ( isset( $criteria['updatedBefore'] ) ) {
			$qb->andWhere( 't.updatedAt < :updatedBefore' )
			   ->setParameter( 'updatedBefore', $criteria['updatedBefore'] );
		}

		return $qb;
	}
}
