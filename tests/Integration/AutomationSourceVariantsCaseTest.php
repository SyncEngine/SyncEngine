<?php

namespace SyncEngine\Tests\Integration;

use SyncEngine\Task\Retrieve;
use SyncEngine\Task\Send;
use SyncEngine\Task\Set;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationSourceVariantsCaseTest extends AutomationScenarioTestCase
{
	public function testAutomationFlowRoutineScenarioWithMockedApi(): void
	{
		$this->mockHttpWebservice( 'MockHttp', [
			[
				'body' => [
					'order' => [
						'id' => 'ORD-42',
						'total' => 120.5,
					],
				],
			],
			[
				'body' => [
					'result' => [
						'status' => 'accepted',
						'remote_id' => 'ERP-99',
					],
				],
			],
		] );

		$normalizeRoutine = $this->createRoutineScenario( 'Scenario Normalize Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'normalize_set',
				'params' => [
					[
						'key' => 'workflow.order_id',
						'value' => '{{ data.id }}',
					],
					[
						'key' => 'workflow.total',
						'value' => '{{ data.total }}',
					],
				],
			],
		] );

		$dispatchRoutine = $this->createRoutineScenario( 'Scenario Dispatch Routine', [
			[
				'_class' => Send::_getClassLocator(),
				'_ref' => 'dispatch_send',
				'connection' => [
					'_class' => 'MockHttp',
					'host' => 'https://erp.example.test',
					'endpoint' => '/orders',
					'request' => [
						'method' => 'POST',
						'format' => 'json',
					],
					'response' => [
						'format' => 'json',
					],
				],
				'transport' => 'body',
				'retrieve' => [
					'param' => 'result',
					'key' => 'dispatch',
					'action' => 'merge',
				],
			],
		] );

		$flow = $this->createFlowScenario( 'Scenario Flow', [
			[
				'_ref' => 'normalize',
				'routine' => $normalizeRoutine->getId(),
			],
			[
				'_ref' => 'dispatch',
				'routine' => $dispatchRoutine->getId(),
				'config' => [
					'input' => [
						'id' => '{{ step.normalize.workflow.order_id }}',
						'total' => '{{ step.normalize.workflow.total }}',
					],
				],
			],
		] );

		$automation = $this->createAutomationScenario( 'Scenario Automation', [
			'source' => [ 'retrieve' ],
			'retrieve' => [
				[
					'_class' => Retrieve::_getClassLocator(),
					'_ref' => 'source_retrieve',
					'connection' => [
						'_class' => 'MockHttp',
						'host' => 'https://source.example.test',
						'endpoint' => '/orders/42',
						'request' => [
							'method' => 'GET',
						],
						'response' => [
							'format' => 'json',
						],
					],
					'response' => [
						'param' => 'order',
						'action' => 'replace',
					],
				],
			],
			'actions' => 'flow',
			'flow' => $flow->getId(),
		] );

		$result = $this->executeAutomationScenario( $automation );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'ORD-42', $result['data']['id'] );
		$this->assertSame( 120.5, $result['data']['total'] );
		$this->assertSame( 'accepted', $result['data']['dispatch']['status'] );
		$this->assertSame( 'ERP-99', $result['data']['dispatch']['remote_id'] );

		$requests = $this->getHttpRequests( 'MockHttp' );

		$this->assertCount( 2, $requests );
		$this->assertSame( 'GET', $requests[0]['method'] );
		$this->assertSame( 'https://source.example.test/orders/42', $requests[0]['url'] );
		$this->assertSame( 'POST', $requests[1]['method'] );
		$this->assertSame( 'https://erp.example.test/orders', $requests[1]['url'] );
	}

	public function testAutomationRoutineScenarioWithRequestSource(): void
	{
		$routine = $this->createRoutineScenario( 'Request Routine', [
			[
				'_class' => Set::_getClassLocator(),
				'_ref' => 'request_set',
				'params' => [
					[
						'key' => 'transformed.full_name',
						'value' => '{{ data.first_name }} {{ data.last_name }}',
					],
					[
						'key' => 'transformed.customer_id',
						'value' => '{{ data.customer_id }}',
					],
				],
			],
		] );

		$automation = $this->createAutomationScenario( 'Request Source Automation', [
			'source' => [ 'request' ],
			'actions' => 'routine',
			'routine' => $routine->getId(),
		] );

		$result = $this->executeAutomationScenario( $automation, [
			'first_name' => 'Jane',
			'last_name' => 'Doe',
			'customer_id' => 'C-7788',
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'Jane Doe', $result['data']['transformed']['full_name'] );
		$this->assertSame( 'C-7788', $result['data']['transformed']['customer_id'] );
	}
}
