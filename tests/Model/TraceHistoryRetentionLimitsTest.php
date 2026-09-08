<?php

namespace SyncEngine\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Entity\Trace;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\TraceModel;
use SyncEngine\Tests\TestCase\BaseTestCase;

class TraceHistoryRetentionLimitsTest extends BaseTestCase
{
	public function setUp(): void
	{
		parent::setUp();

		// Ensure EntityManager is available for this test.
		DefaultController::getEntityManager();
	}

	public function tearDown(): void
	{
		DefaultController::getEntityManager()->clear();
		parent::tearDown();
	}

	private function createAutomation( array $retention = [] ): AutomationModel
	{
		$model = AutomationModel::create();
		$model->setName( 'History Limits Test ' . uniqid() );
		$model->setEndpoint( 'history-retention-' . uniqid() );

		if ( ! empty( $retention ) ) {
			$model->setConfig( $retention, 'execution.history.retention' );
		}

		$model->save( true );
		return $model;
	}

	private function createTrace( AutomationModel $automation, TraceStatus $status, ?\DateTimeImmutable $created = null ): TraceModel
	{
		$trace = TraceModel::create();
		$trace->setStatus( $status );

		if ( $created instanceof \DateTimeImmutable ) {
			$trace->getEntity()->setCreatedAt( $created );
		}

		$trace->register( $automation );
		$automation->save( true );
		return $trace;
	}

	private function countByStatus( AutomationModel $automation, string $status ): int
	{
		return $automation->getTraces()->filter( fn( $t ) => $status === $t->getStatus() )->count();
	}

	public function testNumericLimitKeepsOnlyMostRecentTraces(): void
	{
		$model = $this->createAutomation( [
			[ 'limit' => 3, 'unit' => '' ],
		] );

		// Create 10 traces with increasing timestamps.
		$now = new \DateTimeImmutable();
		for ( $i = 0; $i < 10; $i++ ) {
			$this->createTrace( $model, TraceStatus::SUCCESS, $now->modify( "-{$i} minutes" ) );
		}

		// Should keep only the 3 most recent.
		$this->assertCount( 3, $model->getTraces()->filter( fn( $t ) => TraceStatus::SUCCESS->value === $t->getStatus() ) );
	}

	public function testNumericLimitPerStatusAppliesIndependently(): void
	{
		$model = $this->createAutomation( [
			[ 'status' => 'success', 'limit' => 2, 'unit' => '' ],
			[ 'status' => 'failed', 'limit' => 5, 'unit' => '' ],
			[ 'limit' => 1, 'unit' => '' ],
		] );

		$now = new \DateTimeImmutable();

		// Create multiple traces for each status.
		for ( $i = 0; $i < 5; $i++ ) {
			$this->createTrace( $model, TraceStatus::SUCCESS, $now->modify( "-{$i} minutes" ) );
		}

		for ( $i = 0; $i < 10; $i++ ) {
			$this->createTrace( $model, TraceStatus::FAILED, $now->modify( "-{$i} minutes" ) );
		}

		for ( $i = 0; $i < 3; $i++ ) {
			$this->createTrace( $model, TraceStatus::CANCELLED, $now->modify( "-{$i} minutes" ) );
		}

		// success: limit=2, failed: limit=5, cancelled (no specific config): limit=1 (default).
		$this->assertCount( 2, $model->getTraces()->filter( fn( $t ) => TraceStatus::SUCCESS->value === $t->getStatus() ) );
		$this->assertCount( 5, $model->getTraces()->filter( fn( $t ) => TraceStatus::FAILED->value === $t->getStatus() ) );
		$this->assertCount( 1, $model->getTraces()->filter( fn( $t ) => TraceStatus::CANCELLED->value === $t->getStatus() ) );
	}

	public function testDateBasedLimitRemovesTracesOlderThanCutoff(): void
	{
		$model = $this->createAutomation( [
			[ 'status' => 'failed', 'limit' => 1, 'unit' => 'days' ],
		] );

		$now = new \DateTimeImmutable();

		// Create traces: 1 recent, 2 old (3 days ago).
		for ( $i = 0; $i < 1; $i++ ) {
			$this->createTrace( $model, TraceStatus::FAILED, $now );
		}

		for ( $i = 0; $i < 2; $i++ ) {
			$this->createTrace( $model, TraceStatus::FAILED, $now->modify( '-3 days' ) );
		}

		// Only the recent trace should remain.
		$this->assertCount( 1, $model->getTraces()->filter( fn( $t ) => TraceStatus::FAILED->value === $t->getStatus() ) );
	}

	public function testDateBasedLimitWithMultipleStatuses(): void
	{
		$model = $this->createAutomation( [
			[ 'status' => 'success', 'limit' => 7, 'unit' => 'days' ],
			[ 'status' => 'stopped', 'limit' => 1, 'unit' => 'days' ],
		] );

		$now = new \DateTimeImmutable();

		// Success: create 5 traces, 2 old (>7 days), 3 recent.
		for ( $i = 0; $i < 2; $i++ ) {
			$this->createTrace( $model, TraceStatus::SUCCESS, $now->modify( '-10 days' ) );
		}
		for ( $i = 0; $i < 3; $i++ ) {
			$this->createTrace( $model, TraceStatus::SUCCESS, $now->modify( "-{$i} days" ) );
		}

		// Stopped: create 4 traces, 2 old (>1 day), 2 recent.
		for ( $i = 0; $i < 2; $i++ ) {
			$this->createTrace( $model, TraceStatus::STOPPED, $now->modify( '-3 days' ) );
		}
		for ( $i = 0; $i < 2; $i++ ) {
			$this->createTrace( $model, TraceStatus::STOPPED, $now->modify( "-{$i} days" ) );
		}

		// Success: keep only last 7 days (3 traces).
		// Stopped: keep only last 1 day (2 traces).
		$this->assertCount( 3, $model->getTraces()->filter( fn( $t ) => TraceStatus::SUCCESS->value === $t->getStatus() ) );
		$this->assertCount( 2, $model->getTraces()->filter( fn( $t ) => TraceStatus::STOPPED->value === $t->getStatus() ) );
	}

	public function testDefaultFallbackAppliesToUnconfiguredStatuses(): void
	{
		$model = $this->createAutomation( [
			[ 'status' => 'success', 'limit' => 5, 'unit' => '' ],
			[ 'limit' => 3, 'unit' => '' ],
		] );

		$now = new \DateTimeImmutable();

		// Success has specific config (limit=5).
		for ( $i = 0; $i < 7; $i++ ) {
			$this->createTrace( $model, TraceStatus::SUCCESS, $now->modify( "-{$i} minutes" ) );
		}

		// Cancelled has no specific config, uses default (limit=3).
		for ( $i = 0; $i < 5; $i++ ) {
			$this->createTrace( $model, TraceStatus::CANCELLED, $now->modify( "-{$i} minutes" ) );
		}

		// Stopped has no specific config, uses default (limit=3).
		for ( $i = 0; $i < 4; $i++ ) {
			$this->createTrace( $model, TraceStatus::STOPPED, $now->modify( "-{$i} minutes" ) );
		}

		/**
		 * Note: Since Cancelled and Stopped traces have no specific config, they should be pruned to the default limit of 3 COMBINED, not individually.
		 * Therefore, the below results will depend on the creation date:
		 *
		 * Cancelled: 5 created, each one minute in the past.
		 * Stopped: 4 created, each one minute in the past.
		 *
		 * The 3 most recent traces (regardless of status) should remain, which will be:
		 * - Cancelled (0 minutes ago)
		 * - Stopped (0 minute ago)
		 * - Cancelled (1 minutes ago)
		 */

		$this->assertCount( 5, $model->getTraces()->filter( fn( $t ) => TraceStatus::SUCCESS->value === $t->getStatus() ) );
		$this->assertCount( 2, $model->getTraces()->filter( fn( $t ) => TraceStatus::CANCELLED->value === $t->getStatus() ) );
		$this->assertCount( 1, $model->getTraces()->filter( fn( $t ) => TraceStatus::STOPPED->value === $t->getStatus() ) );
	}

	public function testInFlightTracesAreNotPruned(): void
	{
		$model = $this->createAutomation( [
			[ 'limit' => 2, 'unit' => '' ],
		] );

		// Create SCHEDULED and QUEUED traces (should not be pruned).
		$scheduled = TraceModel::create();
		$scheduled->setStatus( TraceStatus::SCHEDULED )->register( $model )->save( true );

		$queued = TraceModel::create();
		$queued->setStatus( TraceStatus::QUEUED )->register( $model )->save( true );

		// Create 5 SUCCESS traces (should be pruned to 2).
		for ( $i = 0; $i < 5; $i++ ) {
			$this->createTrace( $model, TraceStatus::SUCCESS, ( new \DateTimeImmutable() )->modify( "-{$i} minutes" ) );
		}

		// SCHEDULED and QUEUED should still exist.
		$this->assertCount( 1, $model->getTraces()->filter( fn( $t ) => TraceStatus::SCHEDULED->value === $t->getStatus() ) );
		$this->assertCount( 1, $model->getTraces()->filter( fn( $t ) => TraceStatus::QUEUED->value === $t->getStatus() ) );
		// Only the 2 most recent SUCCESS traces should remain.
		$this->assertCount( 2, $model->getTraces()->filter( fn( $t ) => TraceStatus::SUCCESS->value === $t->getStatus() ) );
	}

	public function testBelowLimitRemovesNothing(): void
	{
		$model = $this->createAutomation( [
			[ 'status' => 'success', 'limit' => 10, 'unit' => '' ],
		] );

		// Create only 3 traces (below limit of 10).
		for ( $i = 0; $i < 3; $i++ ) {
			$this->createTrace( $model, TraceStatus::SUCCESS, ( new \DateTimeImmutable() )->modify( "-{$i} minutes" ) );
		}

		// Nothing should be removed.
		$this->assertCount( 3, $model->getTraces()->filter( fn( $t ) => TraceStatus::SUCCESS->value === $t->getStatus() ) );
	}

	public function testExactlyAtLimitRemovesNothing(): void
	{
		$model = $this->createAutomation( [
			[ 'status' => 'failed', 'limit' => 5, 'unit' => '' ],
		] );

		// Create exactly 5 traces (at limit).
		for ( $i = 0; $i < 5; $i++ ) {
			$this->createTrace( $model, TraceStatus::FAILED, ( new \DateTimeImmutable() )->modify( "-{$i} minutes" ) );
		}

		// Nothing should be removed.
		$this->assertCount( 5, $model->getTraces()->filter( fn( $t ) => TraceStatus::FAILED->value === $t->getStatus() ) );
	}

	public function testNoConfigKeepsAllTraces(): void
	{
		$model = $this->createAutomation();

		$now = new \DateTimeImmutable();

		// Create 10 traces of different statuses.
		for ( $i = 0; $i < 5; $i++ ) {
			$this->createTrace( $model, TraceStatus::SUCCESS, $now->modify( "-{$i} minutes" ) );
		}

		for ( $i = 0; $i < 5; $i++ ) {
			$this->createTrace( $model, TraceStatus::FAILED, $now->modify( "-{$i} minutes" ) );
		}

		// All traces should remain (no history retention configured).
		$this->assertCount( 5, $model->getTraces()->filter( fn( $t ) => TraceStatus::SUCCESS->value === $t->getStatus() ) );
		$this->assertCount( 5, $model->getTraces()->filter( fn( $t ) => TraceStatus::FAILED->value === $t->getStatus() ) );
	}

	public function testZeroLimitRemovesAllTraces(): void
	{
		$model = $this->createAutomation( [
			[ 'status' => 'success', 'limit' => 0, 'unit' => '' ],
		] );

		// Create 5 traces.
		for ( $i = 0; $i < 5; $i++ ) {
			$this->createTrace( $model, TraceStatus::SUCCESS, ( new \DateTimeImmutable() )->modify( "-{$i} minutes" ) );
		}

		// Zero limit means remove all.
		$this->assertCount( 0, $model->getTraces()->filter( fn( $t ) => TraceStatus::SUCCESS->value === $t->getStatus() ) );
	}

	public function testDateBasedLimitWithExactBoundary(): void
	{
		$model = $this->createAutomation( [
			[ 'status' => 'failed', 'limit' => 1, 'unit' => 'days' ],
		] );

		$now = new \DateTimeImmutable();

		// Create traces exactly at the boundary (24 hours ago).
		// One just inside the boundary (23h59m ago) - should be kept.
		// One just outside the boundary (24h01m ago) - should be removed.
		$this->createTrace( $model, TraceStatus::FAILED, $now->modify( '-23 hours -59 minutes' ) );
		$this->createTrace( $model, TraceStatus::FAILED, $now->modify( '-24 hours -1 minute' ) );

		// Only the recent trace should remain.
		$this->assertCount( 1, $model->getTraces()->filter( fn( $t ) => TraceStatus::FAILED->value === $t->getStatus() ) );
	}

	public function testAllFourFinishedStatusesPrunedCorrectly(): void
	{
		$model = $this->createAutomation( [
			[ 'status' => 'success', 'limit' => 2, 'unit' => '' ],
			[ 'status' => 'failed', 'limit' => 3, 'unit' => '' ],
			[ 'status' => 'stopped', 'limit' => 1, 'unit' => '' ],
			[ 'status' => 'cancelled', 'limit' => 4, 'unit' => '' ],
		] );

		$now = new \DateTimeImmutable();

		// Create 5 traces for each status.
		foreach ( [ TraceStatus::SUCCESS, TraceStatus::FAILED, TraceStatus::STOPPED, TraceStatus::CANCELLED ] as $status ) {
			for ( $i = 0; $i < 5; $i++ ) {
				$this->createTrace( $model, $status, $now->modify( "-{$i} minutes" ) );
			}
		}

		// Each status should be pruned to its configured limit.
		$this->assertCount( 2, $model->getTraces()->filter( fn( $t ) => TraceStatus::SUCCESS->value === $t->getStatus() ) );
		$this->assertCount( 3, $model->getTraces()->filter( fn( $t ) => TraceStatus::FAILED->value === $t->getStatus() ) );
		$this->assertCount( 1, $model->getTraces()->filter( fn( $t ) => TraceStatus::STOPPED->value === $t->getStatus() ) );
		$this->assertCount( 4, $model->getTraces()->filter( fn( $t ) => TraceStatus::CANCELLED->value === $t->getStatus() ) );
	}

	public function testMultipleConfigurationsForSameStatusUsesLast(): void
	{
		$model = $this->createAutomation( [
			[ 'status' => 'success', 'limit' => 5, 'unit' => '' ],
			[ 'status' => 'success', 'limit' => 3, 'unit' => '' ],
		] );

		$now = new \DateTimeImmutable();

		// Create 4 traces (between the two retention).
		for ( $i = 0; $i < 4; $i++ ) {
			$this->createTrace( $model, TraceStatus::SUCCESS, $now->modify( "-{$i} minutes" ) );
		}

		// The last config should win (limit=3).
		$this->assertCount( 3, $model->getTraces()->filter( fn( $t ) => TraceStatus::SUCCESS->value === $t->getStatus() ) );
	}
}
