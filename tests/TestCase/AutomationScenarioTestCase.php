<?php

namespace SyncEngine\Tests\TestCase;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\RetryableHttpClient;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\Execute;
use SyncEngine\Model\TraceModel;

abstract class AutomationScenarioTestCase extends ExecuteTestCase
{
	protected ?ExecuteContext $lastAutomationContext = null;

	/**
	 * @var array<string, array<int, array{method: string, url: string, options: array}>>
	 */
	protected array $httpRequests = [];

	/**
	 * @var string[]
	 */
	protected array $mockedWebservices = [];

	protected function tearDown(): void
	{
		foreach ( $this->mockedWebservices as $locator ) {
			$webservice = WebserviceModel::get( $locator );

			if ( $webservice ) {
				$class = $webservice::class;
				if ( method_exists( $class, 'resetMockState' ) ) {
					$class::resetMockState();
				}
			}

			if ( $webservice && method_exists( $webservice, 'setClient' ) ) {
				$webservice->setClient( new RetryableHttpClient( HttpClient::create() ) );
			}
		}

		$this->mockedWebservices = [];
		$this->httpRequests = [];
		$this->lastAutomationContext = null;

		parent::tearDown();
	}

	protected function createRoutineScenario( string $name, array $tasks, array $conditions = [] ): RoutineModel
	{
		$routine = RoutineModel::create();
		$routine->setName( $name );
		$routine->setConfig( [
			'tasks' => $tasks,
			'conditions' => $conditions,
		] );
		$routine->save( true );

		return $routine;
	}

	protected function createFlowScenario( string $name, array $steps ): FlowModel
	{
		$flow = FlowModel::create();
		$flow->setName( $name );
		$flow->setConfig( [
			'steps' => $steps,
		] );
		$flow->save( true );

		return $flow;
	}

	protected function createAutomationScenario( string $name, array $config ): AutomationModel
	{
		$automation = AutomationModel::create();
		$automation->setName( $name );
		$automation->setEndpoint( $name . '-' . uniqid() );
		$automation->setConfig( $config );
		$automation->save( true );

		return $automation;
	}

	protected function executeAutomationScenario( AutomationModel $automation, mixed $data = null ): array
	{
		$execute = static::getContainer()->get( Execute::class );

		$context = new ExecuteContext( $execute, $automation );
		$context->registerTrace( TraceModel::create()->disableAutoSave() );
		$this->lastAutomationContext = $context;

		if ( null !== $data && ! $data instanceof ExecuteData ) {
			$data = ExecuteData::create( $data );
		}

		return $execute->execute( $automation, $context, $data );
	}

	protected function getLastAutomationContext(): ?ExecuteContext
	{
		return $this->lastAutomationContext;
	}

	/**
	 * @param  string  $locator
	 * @param  array<int, MockResponse|array{body?: mixed, status?: int, headers?: array, info?: array}>  $responses
	 *
	 * @return void
	 */
	protected function mockHttpWebservice( string $locator, array $responses ): void
	{
		$webservice = WebserviceModel::get( $locator );

		$this->assertNotNull( $webservice, sprintf( 'Webservice "%s" should exist', $locator ) );
		$this->assertTrue( method_exists( $webservice, 'setClient' ), sprintf( 'Webservice "%s" should support HTTP client injection', $locator ) );

		$this->mockedWebservices[] = $locator;
		$this->httpRequests[ $locator ] = [];

		$class = $webservice::class;
		if ( method_exists( $class, 'primeMockResponses' ) ) {
			$class::primeMockResponses( $responses );
			return;
		}

		$queue = array_values( $responses );

		$client = new MockHttpClient( function( string $method, string $url, array $options = [] ) use ( &$queue, $locator ) {
			$this->httpRequests[ $locator ][] = [
				'method' => $method,
				'url' => $url,
				'options' => $options,
			];

			if ( empty( $queue ) ) {
				return new MockResponse( 'No mocked response available', [ 'http_code' => 500 ] );
			}

			$response = array_shift( $queue );
			if ( $response instanceof MockResponse ) {
				return $response;
			}

			$body = $response['body'] ?? '';
			if ( is_array( $body ) || is_object( $body ) ) {
				$body = json_encode( $body, JSON_THROW_ON_ERROR );
			}

			return new MockResponse(
				(string) $body,
				array_replace(
					[
						'http_code' => (int) ( $response['status'] ?? 200 ),
						'response_headers' => $response['headers'] ?? [],
					],
					$response['info'] ?? []
				)
			);
		} );

		$webservice->setClient( $client );

		$this->overrideWebserviceService( $locator, $webservice );
	}

	protected function overrideWebserviceService( string $locator, WebserviceModel $webservice ): void
	{
		$ids = array_unique( [
			$locator,
			$webservice::class,
			$webservice->getClassLocator(),
		] );

		$containers = [
			static::getContainer(),
			DefaultController::getContainer(),
		];

		foreach ( $containers as $container ) {
			if ( ! $container ) {
				continue;
			}

			foreach ( $ids as $id ) {
				try {
					$container->set( $id, $webservice );
				} catch ( \Throwable ) {
					// Some container IDs are not settable in compiled test container.
				}
			}
		}
	}

	/**
	 * @return array<int, array{method: string, url: string, options: array}>
	 */
	protected function getHttpRequests( string $locator ): array
	{
		$webservice = WebserviceModel::get( $locator );
		if ( $webservice ) {
			$class = $webservice::class;
			if ( method_exists( $class, 'getMockRequests' ) ) {
				return $class::getMockRequests();
			}
		}

		return $this->httpRequests[ $locator ] ?? [];
	}
}
