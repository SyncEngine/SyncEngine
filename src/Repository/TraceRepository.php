<?php

namespace SyncEngine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
	const STATUS_RUNNING = TraceStatus::RUNNING;

	public function __construct( ManagerRegistry $registry )
	{
		parent::__construct( $registry, Trace::class );
	}

	/**
	 * @return Trace[]
	 */
	public function findActiveRunningByAutomation( int $automationId, \DateTimeImmutable $freshAfter ): array
	{
		return $this->createQueryBuilder( 't' )
		            ->andWhere( 't.automation = :automation' )
		            ->andWhere( 't.status = :status' )
		            ->andWhere( 't.modified >= :freshAfter' )
		            ->setParameter( 'automation', $automationId )
		            ->setParameter( 'status', self::STATUS_RUNNING )
		            ->setParameter( 'freshAfter', $freshAfter )
		            ->orderBy( 't.modified', 'DESC' )
		            ->getQuery()
		            ->getResult();
	}

	/**
	 * @return Trace[]
	 */
	public function findStaleRunningByAutomation( int $automationId, \DateTimeImmutable $staleBefore ): array
	{
		return $this->createQueryBuilder( 't' )
		            ->andWhere( 't.automation = :automation' )
		            ->andWhere( 't.status = :status' )
		            ->andWhere( 't.modified < :staleBefore' )
		            ->setParameter( 'automation', $automationId )
		            ->setParameter( 'status', self::STATUS_RUNNING )
		            ->setParameter( 'staleBefore', $staleBefore )
		            ->getQuery()
		            ->getResult();
	}

	public function hasActiveRunningByAutomation( int $automationId, \DateTimeImmutable $freshAfter ): bool
	{
		$result = $this->createQueryBuilder( 't' )
		               ->select( 't.id' )
		               ->andWhere( 't.automation = :automation' )
		               ->andWhere( 't.status = :status' )
		               ->andWhere( 't.modified >= :freshAfter' )
		               ->setParameter( 'automation', $automationId )
		               ->setParameter( 'status', self::STATUS_RUNNING )
		               ->setParameter( 'freshAfter', $freshAfter )
		               ->setMaxResults( 1 )
		               ->getQuery()
		               ->getScalarResult();

		return ! empty( $result );
	}

	public function getLatestRunningModifiedByAutomation( int $automationId, \DateTimeImmutable $freshAfter ): ?\DateTimeImmutable
	{
		$scalar = $this->createQueryBuilder( 't' )
		               ->select( 'MAX(t.modified) AS latest' )
		               ->andWhere( 't.automation = :automation' )
		               ->andWhere( 't.status = :status' )
		               ->andWhere( 't.modified >= :freshAfter' )
		               ->setParameter( 'automation', $automationId )
		               ->setParameter( 'status', self::STATUS_RUNNING )
		               ->setParameter( 'freshAfter', $freshAfter )
		               ->getQuery()
		               ->getSingleScalarResult();

		if ( ! $scalar ) {
			return null;
		}

		return new \DateTimeImmutable( (string) $scalar );
	}

	/**
	 * Minimal Run rows used for fast state checks and UI summaries.
	 * Dates are returned as Unix timestamps so callers receive a uniform, ready-to-use shape.
	 *
	 * @return array<int, array{ id: int, status: string, created: int, modified: int }>
	 */
	public function findActiveRunRowsByAutomation( int $automationId, \DateTimeImmutable $freshAfter ): array
	{
		$rows = $this->createQueryBuilder( 't' )
		             ->select( 't.id, t.status, t.created, t.modified' )
		             ->andWhere( 't.automation = :automation' )
		             ->andWhere( 't.status = :status' )
		             ->andWhere( 't.modified >= :freshAfter' )
		             ->setParameter( 'automation', $automationId )
		             ->setParameter( 'status', self::STATUS_RUNNING )
		             ->setParameter( 'freshAfter', $freshAfter )
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
}
