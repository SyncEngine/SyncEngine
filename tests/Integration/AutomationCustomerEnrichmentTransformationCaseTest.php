<?php

namespace SyncEngine\Tests\Integration;

use SyncEngine\Tests\Mock\Webservice\MockHttp;
use SyncEngine\Tests\TestCase\AutomationScenarioTestCase;

class AutomationCustomerEnrichmentTransformationCaseTest extends AutomationScenarioTestCase
{
	public function testCustomerEnrichmentAndSchemaTransformationWithFlow(): void
	{
		// ============ SETUP ============
		MockHttp::primeMockResponses(
			[
				// 1. Source export: all customers
				[
					'status' => 200,
					'body'   => [
						'customers' => [
							[
								'name'       => 'John Michael Smith',
								'address'    => '123 Main St|New York|NY',
								'phone'      => '+1-555-0100',
								'gender'     => 'M',
								'company_id' => 42,
								'email'      => 'john@example.com',
							],
							[
								'name'       => 'Jane Marie Doe',
								'address'    => '456 Oak Ave|Boston|MA',
								'phone'      => '+1-555-0101',
								'gender'     => 'F',
								'company_id' => 43,
								'email'      => 'jane@example.com',
							],
						],
					],
				],
				// 2. Company lookup for customer 1 (company_id = 42)
				[ 'status' => 200, 'body' => [ 'id' => 42, 'name' => 'Tech Corp', 'industry' => 'Software' ] ],
				// 3. Company lookup for customer 2 (company_id = 43)
				[ 'status' => 200, 'body' => [ 'id' => 43, 'name' => 'Finance Inc', 'industry' => 'Banking' ] ],
				// 4. Target import endpoint
				[ 'status' => 200, 'body' => [ 'success' => true, 'imported' => 2 ] ],
			]
		);

		// ============ CREATE ROUTINES ============

		// Step 1: Retrieve customers and split name and address fields
		$retrieveAndSplitRoutine = $this->createRoutineScenario(
			'Retrieve and Parse',
			[
				[
					'_class'     => 'Retrieve',
					'_ref'       => 'retrieve_customers',
					'name'       => 'Retrieve Customers',
					'connection' => [
						'_class'   => MockHttp::_getClassLocator(),
						'host'     => 'https://crm-source.example.test',
						'endpoint' => '/api/customers/export?format=json',
						'request'  => [ 'method' => 'GET', 'format' => 'json' ],
						'response' => [ 'format' => 'json' ],
					],
					'response'   => [ 'param' => 'customers', 'action' => 'replace' ],
				],
				[
					'_class' => 'Loop',
					'_ref'   => 'split_customer_rows',
					'name'   => 'Split Customer Fields',
					'action' => 'tasks',
					'tasks'  => [
						[
							'_class' => 'Split',
							'_ref'   => 'split_name',
							'key'        => 'name',
							'action'     => 'both',
							'separator'  => ' ',
							'key_method' => 'columns',
							'columns'    => [
								[ 'index' => 0, 'key' => 'first_name' ],
								[ 'index' => 1, 'key' => 'middle_name' ],
								[ 'index' => 2, 'key' => 'last_name' ],
							],
							'remove'     => true,
						],
						[
							'_class' => 'Split',
							'_ref'   => 'split_address',
							'key'        => 'address',
							'action'     => 'both',
							'separator'  => '|',
							'key_method' => 'columns',
							'columns'    => [
								[ 'index' => 0, 'key' => 'street' ],
								[ 'index' => 1, 'key' => 'city' ],
								[ 'index' => 2, 'key' => 'state' ],
							],
							'remove'     => true,
						],
					],
				],
			]
		);

		// Step 2: Filter to keep only customers with a valid email
		$mapAndTransformRoutine = $this->createRoutineScenario(
			'Transform and Map',
			[
				[
					'_class'     => 'Filter',
					'_ref'       => 'filter_valid_email',
					'name'       => 'Filter Valid Customers',
					'conditions' => [
						[ 'key' => 'email', 'operator' => 'not_empty' ],
					],
				],
			]
		);

		// Step 3a: Per-customer company lookup (called by the Loop in step 3)
		$companyLookupRoutine = $this->createRoutineScenario(
			'Company Lookup',
			[
				[
					'_class'     => 'Retrieve',
					'_ref'       => 'retrieve_company',
					'name'       => 'Retrieve Company Info',
					'connection' => [
						'_class'   => MockHttp::_getClassLocator(),
						'host'     => 'https://companies.example.test',
						'endpoint' => '/api/companies/{{ data.company_id }}',
						'request'  => [ 'method' => 'GET', 'format' => 'json' ],
						'response' => [ 'format' => 'json' ],
					],
					'response'   => [ 'key' => 'company_data' ],
				],
				[
					'_class' => 'Set',
					'_ref'   => 'set_company_name',
					'name'   => 'Extract Company Name',
					'params' => [
						[ 'key' => 'company_name', 'value' => '{{ data.company_data.name }}' ],
					],
				],
			]
		);

		// Step 3: Loop over each customer and enrich with company data
		$enrichRoutine = $this->createRoutineScenario(
			'Enrich with Companies',
			[
				[
					'_class'  => 'Loop',
					'_ref'    => 'loop_customers',
					'name'    => 'Enrich Each Customer',
					'action'  => 'routine',
					'routine' => $companyLookupRoutine->getId(),
				],
			]
		);

		// Step 4: Send enriched customers and track import count
		$sendAndCacheRoutine = $this->createRoutineScenario(
			'Send Enriched Customers',
			[
				[
					'_class'     => 'Send',
					'_ref'       => 'send_customers',
					'name'       => 'Send Customers',
					'connection' => [
						'_class'   => MockHttp::_getClassLocator(),
						'host'     => 'https://crm-target.example.test',
						'endpoint' => '/api/customers/import',
						'request'  => [ 'method' => 'POST', 'format' => 'json' ],
						'response' => [ 'format' => 'json' ],
					],
				],
				[
					'_class'  => 'Cache',
					'_ref'    => 'cache_import_count',
					'name'    => 'Count Imported',
					'action'  => 'set',
					'source'  => 'manual',
					'manual'  => 1,
					'tag'     => 'customers_imported',
				],
			]
		);

		// ============ CREATE FLOW ============
		$flow = $this->createFlowScenario(
			'Customer Enrichment and Transformation Flow',
			[
				[ 'routine' => $retrieveAndSplitRoutine->getId(), 'config' => [] ],
				[ 'routine' => $mapAndTransformRoutine->getId(), 'config' => [] ],
				[ 'routine' => $enrichRoutine->getId(), 'config' => [] ],
				[ 'routine' => $sendAndCacheRoutine->getId(), 'config' => [] ],
			]
		);

		// ============ EXECUTE ============
		$automation = $this->createAutomationScenario(
			'enrich_customers',
			[
				'actions' => 'flow',
				'flow'    => $flow->getId(),
			]
		);

		$result = $this->executeAutomationScenario( $automation );

		// ============ VERIFY ============
		$this->assertTrue( $result['success'] );
		$this->assertCount( 2, $result['data'] );
		$this->assertSame( 'John', $result['data'][0]['first_name'] );
		$this->assertSame( '123 Main St', $result['data'][0]['street'] );
		$this->assertSame( 'Tech Corp', $result['data'][0]['company_name'] );
		$this->assertSame( 'Jane', $result['data'][1]['first_name'] );
		$this->assertSame( '456 Oak Ave', $result['data'][1]['street'] );
		$this->assertSame( 'Finance Inc', $result['data'][1]['company_name'] );

		$requests     = MockHttp::getMockRequests();
		$getRequests  = array_values( array_filter( $requests, fn( $r ) => $r['method'] === 'GET' ) );
		$postRequests = array_values( array_filter( $requests, fn( $r ) => $r['method'] === 'POST' ) );

		// 1 export GET + 2 company lookup GETs = 3 GETs; 1 import POST
		$this->assertCount( 3, $getRequests );
		$this->assertCount( 1, $postRequests );

		$this->assertStringContainsString( '/api/customers/export', $getRequests[0]['url'] );
		$this->assertStringContainsString( '/api/companies/', $getRequests[1]['url'] );
		$this->assertStringContainsString( '/api/companies/', $getRequests[2]['url'] );
		$this->assertStringContainsString( '/api/customers/import', $postRequests[0]['url'] );

		$cachedCount = $this->getLastAutomationContext()?->getCacheTag( 'customers_imported' );
		$this->assertGreaterThan( 0, $cachedCount );
	}
}
