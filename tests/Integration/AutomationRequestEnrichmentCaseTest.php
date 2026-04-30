<?php

namespace SyncEngine\Tests\Integration;

use SyncEngine\Task\Retrieve;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationRequestEnrichmentCaseTest extends AutomationScenarioTestCase
{
	public function testRequestSourceCanBeEnrichedByRetrieveTaskUsingCurrentData(): void
	{
		$this->mockHttpWebservice( 'MockHttp', [
			[
				'body' => [
					'price' => 19.95,
					'currency' => 'EUR',
				],
			],
		] );

		$routine = $this->createRoutineScenario( 'Request Enrichment Routine', [
			[
				'_class' => Retrieve::_getClassLocator(),
				'_ref' => 'enrich_request_payload',
				'connection' => [
					'_class' => 'MockHttp',
					'host' => 'https://catalog.example.test',
					'endpoint' => '/products/lookup',
					'request' => [
						'method' => 'POST',
					],
				],
				'send' => true,
				'transport' => 'body',
				'response' => [
					'action' => 'merge',
				],
			],
		] );

		$automation = $this->createAutomationScenario( 'Request Enrichment Automation', [
			'source' => [ 'request' ],
			'actions' => 'routine',
			'routine' => $routine->getId(),
		] );

		$result = $this->executeAutomationScenario( $automation, [
			'sku' => 'SKU-ENRICH',
			'name' => 'Demo product',
		] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'SKU-ENRICH', $result['data']['sku'] );
		$this->assertSame( 'Demo product', $result['data']['name'] );
		$this->assertSame( 19.95, $result['data']['price'] );
		$this->assertSame( 'EUR', $result['data']['currency'] );

		$requests = $this->getHttpRequests( 'MockHttp' );
		$this->assertCount( 1, $requests );
		$this->assertSame( 'POST', $requests[0]['method'] );
		$this->assertSame( 'https://catalog.example.test/products/lookup', $requests[0]['url'] );
		$this->assertSame( 'SKU-ENRICH', $requests[0]['options']['data']['sku'] );
	}
}
