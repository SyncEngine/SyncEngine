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
	 *  - modifiedAfter \DateTimeImmutable  (inclusive, t.modified >= value)
	 *  - modifiedBefore \DateTimeImmutable (exclusive, t.modified < value)
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
	 * Return lightweight scalar rows — no entity hydration — ordered newest-modified first.
	 * Suitable for state checks and UI summaries.
	 * Dates are returned as Unix timestamps.
	 *
	 * Supported criteria keys: same as existsBy().
	 *
	 * @return array<int, array{ id: int, status: string, created: int, modified: int }>
	 */
	public function findRowsBy( array $criteria ): array
	{
		$rows = $this->buildRowsCriteriaQuery( $criteria )
		             ->select( 't.id, t.status, t.created, t.modified' )
		             ->orderBy( 't.modified', 'DESC' )
		             ->getQuery()
		             ->getArrayResult();

		return array_map( static function ( array $row ): array {
			return [
				'id'       => (int) $row['id'],
				'status'   => (string) $row['status'],
				'created'  => $row['created'] instanceof \DateTimeInterface ? $row['created']->getTimestamp() : 0,
				'modified' => $row['modified'] instanceof \DateTimeInterface ? $row['modified']->getTimestamp() : 0,
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

		if ( isset( $criteria['modifiedAfter'] ) ) {
			$qb->andWhere( 't.modified >= :modifiedAfter' )
			   ->setParameter( 'modifiedAfter', $criteria['modifiedAfter'] );
		}

		if ( isset( $criteria['modifiedBefore'] ) ) {
			$qb->andWhere( 't.modified < :modifiedBefore' )
			   ->setParameter( 'modifiedBefore', $criteria['modifiedBefore'] );
		}

		return $qb;
	}
}
