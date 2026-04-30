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

		// Step 1: Retrieve products and split address into street/city
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
				[
					'_class' => 'Split',
					'_ref'   => 'split_address',
					'name'   => 'Split Seller Address',
					'params' => [
						'key'        => 'sellAddress',
						'action'     => 'split',
						'separator'  => '|',
						'key_method' => 'columns',
						'columns'    => [
							[ 'index' => 0, 'key' => 'seller_address_street' ],
							[ 'index' => 1, 'key' => 'seller_address_city' ],
						],
						'remove'     => true,
					],
				],
			]
		);

		// Step 2: Rename fields and map category codes to labels
		$mapAndTransformRoutine = $this->createRoutineScenario(
			'Map and Transform',
			[
				[
					'_class' => 'Map',
					'_ref'   => 'map_columns',
					'name'   => 'Rename Columns',
					'params' => [
						'map'            => [
							'map_source' => 'manual',
							'manual'     => [
								[ 'source' => 'prodName', 'target' => 'product_name' ],
								[ 'source' => 'prodPrice', 'target' => 'product_price' ],
							],
						],
						'convert_schema' => true,
					],
				],
				[
					'_class' => 'Map',
					'_ref'   => 'map_categories',
					'name'   => 'Map Category Codes',
					'params' => [
						'key'    => 'categories',
						'action' => 'value',
						'map'    => [
							'map_source' => 'storage',
							'storage'    => [ 'id' => $categoryMapper->getId() ],
						],
					],
				],
			]
		);

		// Step 3: Split categories into indexed fields and send
		$splitAndSendRoutine = $this->createRoutineScenario(
			'Split and Send',
			[
				[
					'_class' => 'Split',
					'_ref'   => 'split_categories',
					'name'   => 'Split Categories',
					'params' => [
						'key'         => 'categories',
						'action'      => 'both',
						'separator'   => ',',
						'key_method'  => 'indexed',
						'index_key'   => 'category_{*index*}',
						'index_start' => 1,
						'remove'      => true,
					],
				],
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

		$this->executeAutomationScenario( $automation );

		// ============ VERIFY ============
		$requests     = MockHttp::getMockRequests();
		$getRequests  = array_values( array_filter( $requests, fn( $r ) => $r['method'] === 'GET' ) );
		$postRequests = array_values( array_filter( $requests, fn( $r ) => $r['method'] === 'POST' ) );

		$this->assertCount( 1, $getRequests );
		$this->assertStringContainsString( '/api/products', $getRequests[0]['url'] );
		$this->assertCount( 1, $postRequests );
		$this->assertStringContainsString( '/api/products/batch', $postRequests[0]['url'] );
	}
}
