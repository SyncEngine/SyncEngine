<?php

namespace SyncEngine\Tests\Integration;

use SyncEngine\Tests\Mock\Webservice\MockHttp;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationMultiRegionalOrderAggregationCaseTest extends AutomationScenarioTestCase
{
	public function testMultiRegionalOrderAggregationWithFlowOrchestration(): void
	{
		// ============ SETUP ============

		MockHttp::primeMockResponses(
			[
				// 1. US East orders
				[
					'status' => 200,
					'body'   => [
						'orders' => [
							[ 'order_num'        => 'ORD001',
							  'status_code'      => '1',
							  'fulfillment_type' => 'WAREHOUSE',
							  'amount'           => 150.00,
							],
							[ 'order_num'        => 'ORD002',
							  'status_code'      => '2',
							  'fulfillment_type' => 'DROP_SHIP',
							  'amount'           => 75.50,
							],
						],
					],
				],
				// 2. US West orders
				[
					'status' => 200,
					'body'   => [
						'orders' => [
							[ 'order_num'        => 'ORD101',
							  'status_code'      => '1',
							  'fulfillment_type' => 'WAREHOUSE',
							  'amount'           => 200.00,
							],
						],
					],
				],
				// 3. EU orders
				[
					'status' => 200,
					'body'   => [
						'orders' => [
							[ 'order_num'        => 'ORD201',
							  'status_code'      => '2',
							  'fulfillment_type' => 'DROP_SHIP',
							  'amount'           => 125.00,
							],
						],
					],
				],
				// 4. Fulfillment sends (one per fulfillment type batch)
				[ 'status' => 200, 'body' => [ 'batch_id' => 'BATCH_WAREHOUSE_001', 'count' => 2 ] ],
				[ 'status' => 200, 'body' => [ 'batch_id' => 'BATCH_DROP_SHIP_001', 'count' => 2 ] ],
			]
		);

		// ============ CREATE ROUTINES ============

		// Step 1: Retrieve orders from each region and merge into a single list
		$retrieveOrdersRoutine = $this->createRoutineScenario(
			'Retrieve Regional Orders',
			[
				[
					'_class'     => 'Retrieve',
					'_ref'       => 'retrieve_us_east',
					'name'       => 'Retrieve US East Orders',
					'connection' => [
						'_class'   => MockHttp::_getClassLocator(),
						'host'     => 'https://us-east.example.test',
						'endpoint' => '/api/orders?status=pending',
						'request'  => [ 'method' => 'GET', 'format' => 'json' ],
						'response' => [ 'format' => 'json' ],
					],
					'response'   => [ 'param' => 'orders', 'action' => 'replace' ],
				],
				[
					'_class'     => 'Retrieve',
					'_ref'       => 'retrieve_us_west',
					'name'       => 'Retrieve US West Orders',
					'connection' => [
						'_class'   => MockHttp::_getClassLocator(),
						'host'     => 'https://us-west.example.test',
						'endpoint' => '/api/orders?status=pending',
						'request'  => [ 'method' => 'GET', 'format' => 'json' ],
						'response' => [ 'format' => 'json' ],
					],
					'response'   => [ 'param' => 'orders', 'action' => 'merge' ],
				],
				[
					'_class'     => 'Retrieve',
					'_ref'       => 'retrieve_eu',
					'name'       => 'Retrieve EU Orders',
					'connection' => [
						'_class'   => MockHttp::_getClassLocator(),
						'host'     => 'https://eu.example.test',
						'endpoint' => '/api/orders?status=pending',
						'request'  => [ 'method' => 'GET', 'format' => 'json' ],
						'response' => [ 'format' => 'json' ],
					],
					'response'   => [ 'param' => 'orders', 'action' => 'merge' ],
				],
			]
		);

		// Step 2: Filter to keep only high-value orders (amount >= 100)
		$filterRoutine = $this->createRoutineScenario(
			'Merge and Normalize',
			[
				[
					'_class'     => 'Filter',
					'_ref'       => 'filter_high_value',
					'name'       => 'Filter High-Value Orders',
					'conditions' => [
						[ 'key' => 'amount', 'operator' => 'greater_or_equal', 'compare' => 100 ],
					],
				],
			]
		);

		// Step 3: Send fulfillment batches and track count in cache
		$sendRoutine = $this->createRoutineScenario(
			'Send Fulfillment Batches',
			[
				[
					'_class'     => 'Send',
					'_ref'       => 'send_fulfillment',
					'name'       => 'Send Fulfillment Batch',
					'connection' => [
						'_class'   => MockHttp::_getClassLocator(),
						'host'     => 'https://fulfillment.example.test',
						'endpoint' => '/api/fulfill/batch',
						'request'  => [ 'method' => 'POST', 'format' => 'json' ],
						'response' => [ 'format' => 'json' ],
					],
				],
				[
					'_class'  => 'Cache',
					'_ref'    => 'cache_batch_count',
					'name'    => 'Track Batch Counts',
					'action'  => 'set',
					'source'  => 'manual',
					'manual'  => 1,
					'tag'     => 'fulfillment_batches_sent',
				],
			]
		);

		// ============ CREATE FLOW ============
		$flow = $this->createFlowScenario(
			'Multi-Regional Order Aggregation Flow',
			[
				[ 'routine' => $retrieveOrdersRoutine->getId(), 'config' => [] ],
				[ 'routine' => $filterRoutine->getId(), 'config' => [] ],
				[ 'routine' => $sendRoutine->getId(), 'config' => [] ],
			]
		);

		// ============ EXECUTE ============
		$automation = $this->createAutomationScenario(
			'aggregate_regional_orders',
			[
				'actions' => 'flow',
				'flow'    => $flow->getId(),
			]
		);

		$result = $this->executeAutomationScenario( $automation );

		// ============ VERIFY ============
		$this->assertTrue( $result['success'] );
		$this->assertCount( 3, $result['data'] );
		$this->assertSame( 'ORD001', $result['data'][0]['order_num'] );
		$this->assertSame( 'ORD101', $result['data'][1]['order_num'] );
		$this->assertSame( 'ORD201', $result['data'][2]['order_num'] );

		$requests     = MockHttp::getMockRequests();
		$getRequests  = array_values( array_filter( $requests, fn( $r ) => $r['method'] === 'GET' ) );
		$postRequests = array_values( array_filter( $requests, fn( $r ) => $r['method'] === 'POST' ) );

		$this->assertCount( 3, $getRequests );
		$this->assertGreaterThan( 0, count( $postRequests ) );

		$batchCount = $this->getLastAutomationContext()?->getCacheTag( 'fulfillment_batches_sent' );
		$this->assertGreaterThan( 0, $batchCount );
	}
}
