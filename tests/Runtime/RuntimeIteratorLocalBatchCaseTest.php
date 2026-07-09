<?php

namespace SyncEngine\Tests\Runtime;

use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\TraceModel;
use SyncEngine\Runtime\Execute;
use SyncEngine\Runtime\ExecuteContext;
use SyncEngine\Runtime\ExecuteData;
use SyncEngine\Runtime\ExecuteLocalBatch;
use SyncEngine\Tests\TestCase\RuntimeScenarioTestCase;

class RuntimeIteratorLocalBatchCaseTest extends RuntimeScenarioTestCase
{
	public function testIteratorLocalBatchCreatesStoredBatchesAndSchedulesContinuation(): void
	{
		$automation = $this->createAutomationScenario( 'Iterator Local Batch Automation', [
			'source' => [ 'request' ],
			'iterator' => true,
			'batch_method' => 'local',
			'limit' => 2,
			'actions' => [],
		] );

		$result = $this->executeAutomationScenario( $automation, [
			[ 'sku' => 'A-1' ],
			[ 'sku' => 'A-2' ],
			[ 'sku' => 'A-3' ],
			[ 'sku' => 'A-4' ],
			[ 'sku' => 'A-5' ],
		] );

		$this->assertTrue( $result['success'], $this->getLastErrorMessage( $result ) );
		$this->assertSame( 'Added to queue!', $result['message'] );
		$this->assertCount( 2, $result['data'] );

		$context = $this->getLastAutomationContext();
		$trace = $context?->getTrace();

		$this->assertNotNull( $trace );

		$batches = ExecuteLocalBatch::load( $trace );
		$this->assertSame( [ 'A-1', 'A-2' ], array_column( $batches->fetchBatch( 1 ), 'sku' ) );
		$this->assertSame( [ 'A-3', 'A-4' ], array_column( $batches->fetchBatch( 2 ), 'sku' ) );
		$this->assertSame( [ 'A-5' ], array_column( $batches->fetchBatch( 3 ), 'sku' ) );
	}

	public function testIteratorLocalBatchContinuationResumesFromStoredTraceFiles(): void
	{
		$automation = $this->createIteratorAutomationScenario( 'Iterator Local Batch Resume Automation' );

		$firstResult = $this->executeAutomationWithTrace( $automation, data: [
			[ 'sku' => 'A-1' ],
			[ 'sku' => 'A-2' ],
			[ 'sku' => 'A-3' ],
			[ 'sku' => 'A-4' ],
			[ 'sku' => 'A-5' ],
		] );

		$this->assertTrue( $firstResult['success'] );
		$this->assertSame( 'Added to queue!', $firstResult['message'] );
		$this->assertSame( [ 'A-1', 'A-2' ], array_column( $firstResult['data'], 'sku' ) );

		$initialTrace = $this->getLastAutomationContext()?->getTrace();
		$this->assertNotNull( $initialTrace );

		$traceId = $initialTrace->getId();

		$this->assertStoredTraceFiles( $initialTrace, [ 1 ] );
		$this->assertStoredBatchFiles( $initialTrace, [
			1 => [ 'A-1', 'A-2' ],
			2 => [ 'A-3', 'A-4' ],
			3 => [ 'A-5' ],
		] );

		$secondResult = $this->executeAutomationWithTrace( $automation, $this->loadTrace( $automation, $traceId ) );

		$this->assertTrue( $secondResult['success'] );
		$this->assertSame( 'Added to queue!', $secondResult['message'] );
		$this->assertSame( [ 'A-3', 'A-4' ], array_column( $secondResult['data'], 'sku' ) );

		$secondTrace = $this->loadTrace( $automation, $traceId );
		$this->assertSame( 2, $secondTrace->getCurrentIteration() );
		$this->assertSame( TraceStatus::SCHEDULED, $secondTrace->getStatus() );
		$this->assertStoredTraceFiles( $secondTrace, [ 1, 2 ] );

		$thirdResult = $this->executeAutomationWithTrace( $automation, $this->loadTrace( $automation, $traceId ) );

		$this->assertTrue( $thirdResult['success'] );
		$this->assertSame( 'Finished executing endpoint.', $thirdResult['message'] );
		$this->assertSame( [ 'A-5' ], array_column( $thirdResult['data'], 'sku' ) );

		$finalTrace = $this->loadTrace( $automation, $traceId );
		$this->assertSame( 0, $finalTrace->getCurrentIteration() );
		$this->assertSame( TraceStatus::SUCCESS, $finalTrace->getStatus() );
		$this->assertStoredTraceFiles( $finalTrace, [ 1, 2, 3 ] );
		$this->assertStoredBatchFiles( $finalTrace, [
			1 => [ 'A-1', 'A-2' ],
			2 => [ 'A-3', 'A-4' ],
			3 => [ 'A-5' ],
		] );
	}

	public function testIteratorLocalBatchParallelTraceRunsStayIsolatedAcrossContinuations(): void
	{
		$automation = $this->createIteratorAutomationScenario( 'Iterator Local Batch Parallel Automation', [
			'execution' => [
				'mode' => 'parallel',
			],
		] );

		$result11 = $this->executeAutomationWithTrace( $automation, data: [
			[ 'sku' => 'R1-1' ],
			[ 'sku' => 'R1-2' ],
			[ 'sku' => 'R1-3' ],
			[ 'sku' => 'R1-4' ],
			[ 'sku' => 'R1-5' ],
		] );

		$traceOne = $this->getLastAutomationContext()?->getTrace();
		$this->assertNotNull( $traceOne );
		$traceOneId = $traceOne->getId();

		$this->assertTrue( $result11['success'] );
		$this->assertSame( 'Added to queue!', $result11['message'] );
		$this->assertSame( [ 'R1-1', 'R1-2' ], array_column( $result11['data'], 'sku' ) );
		$this->assertStoredTraceFiles( $traceOne, [ 1 ] );
		$this->assertStoredBatchFiles( $traceOne, [
			1 => [ 'R1-1', 'R1-2' ],
			2 => [ 'R1-3', 'R1-4' ],
			3 => [ 'R1-5' ],
		] );

		$result21 = $this->executeAutomationWithTrace( $automation, data: [
			[ 'sku' => 'R2-1' ],
			[ 'sku' => 'R2-2' ],
			[ 'sku' => 'R2-3' ],
		] );

		$traceTwo = $this->getLastAutomationContext()?->getTrace();
		$this->assertNotNull( $traceTwo );
		$traceTwoId = $traceTwo->getId();

		$this->assertTrue( $result21['success'] );
		$this->assertSame( 'Added to queue!', $result21['message'] );
		$this->assertSame( [ 'R2-1', 'R2-2' ], array_column( $result21['data'], 'sku' ) );
		$this->assertStoredTraceFiles( $traceTwo, [ 1 ] );
		$this->assertStoredBatchFiles( $traceTwo, [
			1 => [ 'R2-1', 'R2-2' ],
			2 => [ 'R2-3' ],
		] );

		$this->assertNotSame( $traceOne->getTraceDir(), $traceTwo->getTraceDir() );

		$result12 = $this->executeAutomationWithTrace( $automation, $this->loadTrace( $automation, $traceOneId ) );

		$this->assertTrue( $result12['success'] );
		$this->assertSame( 'Added to queue!', $result12['message'] );
		$this->assertSame( [ 'R1-3', 'R1-4' ], array_column( $result12['data'], 'sku' ) );

		$reloadedTraceOne = $this->loadTrace( $automation, $traceOneId );
		$reloadedTraceTwo = $this->loadTrace( $automation, $traceTwoId );

		$this->assertSame( 2, $reloadedTraceOne->getCurrentIteration() );
		$this->assertSame( 1, $reloadedTraceTwo->getCurrentIteration() );
		$this->assertStoredTraceFiles( $reloadedTraceOne, [ 1, 2 ] );
		$this->assertStoredTraceFiles( $reloadedTraceTwo, [ 1 ] );

		$result22 = $this->executeAutomationWithTrace( $automation, $this->loadTrace( $automation, $traceTwoId ) );

		$this->assertTrue( $result22['success'] );
		$this->assertSame( 'Finished executing endpoint.', $result22['message'] );
		$this->assertSame( [ 'R2-3' ], array_column( $result22['data'], 'sku' ) );

		$finalTraceTwo = $this->loadTrace( $automation, $traceTwoId );
		$this->assertSame( 0, $finalTraceTwo->getCurrentIteration() );
		$this->assertSame( TraceStatus::SUCCESS, $finalTraceTwo->getStatus() );
		$this->assertStoredTraceFiles( $finalTraceTwo, [ 1, 2 ] );

		$result13 = $this->executeAutomationWithTrace( $automation, $this->loadTrace( $automation, $traceOneId ) );

		$this->assertTrue( $result13['success'] );
		$this->assertSame( 'Finished executing endpoint.', $result13['message'] );
		$this->assertSame( [ 'R1-5' ], array_column( $result13['data'], 'sku' ) );

		$finalTraceOne = $this->loadTrace( $automation, $traceOneId );
		$this->assertSame( 0, $finalTraceOne->getCurrentIteration() );
		$this->assertSame( TraceStatus::SUCCESS, $finalTraceOne->getStatus() );
		$this->assertStoredTraceFiles( $finalTraceOne, [ 1, 2, 3 ] );
		$this->assertStoredBatchFiles( $finalTraceOne, [
			1 => [ 'R1-1', 'R1-2' ],
			2 => [ 'R1-3', 'R1-4' ],
			3 => [ 'R1-5' ],
		] );
		$this->assertStoredBatchFiles( $finalTraceTwo, [
			1 => [ 'R2-1', 'R2-2' ],
			2 => [ 'R2-3' ],
		] );
	}

	private function createIteratorAutomationScenario( string $name, array $config = [] ): AutomationModel
	{
		return $this->createAutomationScenario( $name, array_replace_recursive( [
			'source' => [ 'request' ],
			'iterator' => true,
			'batch_method' => 'local',
			'limit' => 2,
			'actions' => [],
		], $config ) );
	}

	private function executeAutomationWithTrace( AutomationModel $automation, ?TraceModel $trace = null, mixed $data = null ): array
	{
		$execute = static::getContainer()->get( Execute::class );

		$context = new ExecuteContext( $execute, $automation );
		$context->registerTrace( ( $trace ?? TraceModel::create() )->disableAutoSave() );

		$this->lastAutomationContext = $context;

		if ( null !== $data && ! $data instanceof ExecuteData ) {
			$data = ExecuteData::create( $data );
		}

		return $execute->execute( $automation, $context, $data );
	}

	private function loadTrace( AutomationModel $automation, int $traceId ): TraceModel
	{
		return TraceModel::load( $automation, $traceId )->disableAutoSave();
	}

	/**
	 * @param  int[]  $iterations
	 */
	private function assertStoredTraceFiles( TraceModel $trace, array $iterations ): void
	{
		$files = $trace->getTraceFiles();
		ksort( $files );

		$this->assertSame( $iterations, array_map( 'intval', array_keys( $files ) ) );

		foreach ( $iterations as $iteration ) {
			$this->assertArrayHasKey( $iteration, $files );
			$this->assertFileExists( $trace->getTraceFilePath( $iteration ) );
			$this->assertNotSame( [], $trace->fetchTraceFileContent( $iteration ) );
		}
	}

	/**
	 * @param  array<int, string[]>  $expectedBatches
	 */
	private function assertStoredBatchFiles( TraceModel $trace, array $expectedBatches ): void
	{
		$batches = ExecuteLocalBatch::load( $trace );

		foreach ( $expectedBatches as $iteration => $expectedSkus ) {
			$this->assertFileExists( $batches->getBatchDir() . $batches->getBatchFilename( $iteration ) );
			$this->assertSame( $expectedSkus, array_column( $batches->fetchBatch( $iteration ) ?? [], 'sku' ) );
		}
	}
}
