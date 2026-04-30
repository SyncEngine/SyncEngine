<?php

namespace SyncEngine\Tests\Integration;

use SyncEngine\Tests\Mock\Webservice\MockHttp;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationProductCatalogNormalizationCaseTest extends AutomationScenarioTestCase
{
	public function testProductCatalogNormalizationWithFlowOrchestration(): void
	{
		// ============ SETUP ============
		$categoryMapper = $this->createStorage(
			'category_mapper',
			'mapper',
			[
				'ELEC'  => 'Electronics',
				'CLOTH' => 'Clothing',
				'HOME'  => 'Home & Garden',
			]
		);

		MockHttp::primeMockResponses(
			[
				// 1. Source API: fetch products
				[
					'status' => 200,
					'body'   => [
						'products' => [
							[
								'prodName'    => 'Laptop Pro',
								'prodPrice'   => 1299.99,
								'sellAddress' => '123 Main St|New York',
								'categories'  => 'ELEC,HOME',
							],
							[
								'prodName'    => 'Winter Coat',
								'prodPrice'   => 89.50,
								'sellAddress' => '456 Oak Ave|Boston',
								'categories'  => 'CLOTH',
							],
						],
					],
				],
				// 2. Target API: accept normalized batch
				[ 'status' => 200, 'body' => [ 'success' => true, 'count' => 2 ] ],
			]
		);

		// ============ CREATE ROUTINES ============

		// Step 1: Retrieve products
		$retrieveAndSplitRoutine = $this->createRoutineScenario(
			'Fetch and Split Products',
			[
				[
					'_class'     => 'Retrieve',
					'_ref'       => 'retrieve_products',
					'name'       => 'Retrieve Products',
					'connection' => [
						'_class'   => MockHttp::_getClassLocator(),
						'host'     => 'https://source.example.test',
						'endpoint' => '/api/products?limit=1000',
						'request'  => [ 'method' => 'GET', 'format' => 'json' ],
						'response' => [ 'format' => 'json' ],
					],
					'response'   => [ 'param' => 'products', 'action' => 'replace' ],
				],
			]
		);

		// Step 2: Normalize each product row (split/map/rename)
		$mapAndTransformRoutine = $this->createRoutineScenario(
			'Map and Transform',
			[
				[
					'_class' => 'Loop',
					'_ref'   => 'normalize_products_loop',
					'name'   => 'Normalize Product Rows',
					'action' => 'tasks',
					'tasks'  => [
						[
							'_class' => 'Split',
							'_ref'   => 'split_address',
							'key'        => 'sellAddress',
							'action'     => 'both',
							'separator'  => '|',
							'key_method' => 'columns',
							'columns'    => [
								[ 'index' => 0, 'key' => 'seller_address_street' ],
								[ 'index' => 1, 'key' => 'seller_address_city' ],
							],
							'remove'     => true,
						],
						[
							'_class' => 'Map',
							'_ref'   => 'map_columns',
							'map'            => [
								'map_source' => 'manual',
								'manual'     => [
									[ 'source' => 'prodName', 'target' => 'product_name' ],
									[ 'source' => 'prodPrice', 'target' => 'product_price' ],
								],
							],
							'convert_schema' => true,
						],
						[
							'_class' => 'Split',
							'_ref'   => 'split_categories_to_array',
							'key'       => 'categories',
							'action'    => 'value',
							'separator' => ',',
						],
						[
							'_class' => 'Map',
							'_ref'   => 'map_categories',
							'key'    => 'categories',
							'action' => 'value',
							'map'    => [
								'map_source' => 'storage',
								'storage'    => [ 'id' => $categoryMapper->getId() ],
							],
						],
						[
							'_class' => 'Split',
							'_ref'   => 'split_categories_to_keys',
							'key'         => 'categories',
							'action'      => 'key',
							'key_method'  => 'indexed',
							'index_key'   => 'category_{*index*}',
							'index_start' => 1,
							'remove'      => true,
						],
					],
				],
			]
		);

		// Step 3: Send normalized batch
		$splitAndSendRoutine = $this->createRoutineScenario(
			'Split and Send',
			[
				[
					'_class'     => 'Send',
					'_ref'       => 'send_products',
					'name'       => 'Send Normalized Products',
					'connection' => [
						'_class'   => MockHttp::_getClassLocator(),
						'host'     => 'https://target.example.test',
						'endpoint' => '/api/products/batch',
						'request'  => [ 'method' => 'POST', 'format' => 'json' ],
						'response' => [ 'format' => 'json' ],
					],
				],
			]
		);

		// ============ CREATE FLOW ============
		$flow = $this->createFlowScenario(
			'Product Normalization Flow',
			[
				[ 'routine' => $retrieveAndSplitRoutine->getId(), 'config' => [] ],
				[ 'routine' => $mapAndTransformRoutine->getId(), 'config' => [] ],
				[ 'routine' => $splitAndSendRoutine->getId(), 'config' => [] ],
			]
		);

		// ============ EXECUTE ============
		$automation = $this->createAutomationScenario(
			'normalize_products_flow',
			[
				'actions' => 'flow',
				'flow'    => $flow->getId(),
			]
		);

		$result = $this->executeAutomationScenario( $automation );

		// ============ VERIFY ============
		$this->assertTrue( $result['success'] );
		$this->assertCount( 2, $result['data'] );
		$this->assertSame( 'Laptop Pro', $result['data'][0]['product_name'] );
		$this->assertSame( 1299.99, $result['data'][0]['product_price'] );
		$this->assertSame( '123 Main St', $result['data'][0]['seller_address_street'] );
		$this->assertSame( 'New York', $result['data'][0]['seller_address_city'] );
		$this->assertSame( 'Electronics', $result['data'][0]['category_1'] );
		$this->assertSame( 'Home & Garden', $result['data'][0]['category_2'] );

		$this->assertSame( 'Winter Coat', $result['data'][1]['product_name'] );
		$this->assertSame( 89.5, $result['data'][1]['product_price'] );
		$this->assertSame( '456 Oak Ave', $result['data'][1]['seller_address_street'] );
		$this->assertSame( 'Boston', $result['data'][1]['seller_address_city'] );
		$this->assertSame( 'Clothing', $result['data'][1]['category_1'] );

		$requests     = MockHttp::getMockRequests();
		$getRequests  = array_values( array_filter( $requests, fn( $r ) => $r['method'] === 'GET' ) );
		$postRequests = array_values( array_filter( $requests, fn( $r ) => $r['method'] === 'POST' ) );

		$this->assertCount( 1, $getRequests );
		$this->assertStringContainsString( '/api/products', $getRequests[0]['url'] );
		$this->assertCount( 1, $postRequests );
		$this->assertStringContainsString( '/api/products/batch', $postRequests[0]['url'] );

		$payload = $postRequests[0]['options']['data'] ?? [];
		$this->assertEquals( $result['data'], $payload );
	}
}
