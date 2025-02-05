<?php

namespace SyncEngine\Tests\Service;

use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecuteLocalBatch;
use SyncEngine\Tests\TestCase\BaseTestCase;

class LocalBatchTest extends BaseTestCase
{
	public function testBatches()
	{
		$data = array_fill( 0, 300, [] );

		foreach ( $data as $key => $value ) {
			$data[ $key ] = [
				'test_id' => $key,
			];
		}

		$automation = AutomationModel::create();
		$automation->setName( 'Test Local Batch' );
		$automation->setEndpoint( 'test-local-batch' );
		$automation->setConfig(
			[
				'source'       => [ 'request' ],
				'iterator'     => true,
				'limit'        => 50,
				'batch_method' => 'local',
			]
		);

		$automation->save();

		$execute = static::getContainer()->get( Execute::class );

		$trace = TraceModel::create();
		$trace->setId( 0 );
		// Make sure we renew everything.
		$trace->removeTraceFiles();

		$context = new ExecuteContext( $execute, $automation );
		$context->setTrace( $trace );

		$result = $execute->execute( $automation, $context, new ExecuteData( $data ) );

		$batches = ExecuteLocalBatch::load( $context->getTrace() );

		/**
		 * Test if the batches are actually made.
		 */
		$this->assertArrayHasKey( 120, $batches->fetchBatch( 3 ) );
		$this->assertArrayHasKey( 280, $batches->fetchBatch( 6 ) );

		/**
		 * Modify the batch content to make sure the execute method returns the batch and does not re-generate the batches.
		 */

		$testIteration = 4;

		$batch = $batches->getBatch( $testIteration );

		foreach ( $batch as $key => $value ) {
			$value['test'] = 'changed';
			$batch[ $key ] = $value;
		}

		$batches->storeBatch( $batch, $testIteration );

		$automation->setIteration( $testIteration - 1 ); // -1 since the execute function adds 1 again.

		// Rerun automation.
		// Note that we pass the OLD data, not the modified batch data!
		$result = $execute->execute( $automation, new ExecuteContext( $execute, $automation ), new ExecuteData( $data ) );

		$this->assertArrayHasKey( 180, $result['data'] );
		$this->assertEquals( 180, $result['data'][ 180 ]['test_id'] );
		$this->assertEquals( 'changed', $result['data'][ 180 ]['test'] );
	}
}
