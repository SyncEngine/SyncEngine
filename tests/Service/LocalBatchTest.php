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

		$batches = ExecuteLocalBatch::load( $execute->trace() );

		/**
		 * Test if the batches are actually made.
		 */
		$this->assertArrayHasKey( 120, $batches->fetchBatch( 3 ) );
		$this->assertArrayHasKey( 280, $batches->fetchBatch( 6 ) );
	}
}
