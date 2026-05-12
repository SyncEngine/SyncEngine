<?php

namespace SyncEngine\Tests\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Tests\Mock\Webservice\MockSql;
use SyncEngine\Tests\TestCase\BaseTestCase;
use SyncEngine\Webservice\Exception\ResultException;

class MockSqlTest extends BaseTestCase
{
	protected function tearDown(): void
	{
		MockSql::resetMockState();
		parent::tearDown();
	}

	public function testMockSqlWebserviceIsRegistered(): void
	{
		$this->assertInstanceOf( MockSql::class, $this->getWebservice() );
	}

	public function testSuccessfulMysqliRetrieveReusesCachedConnection(): void
	{
		MockSql::primeMockQueries( 'mysqli', [
			[
				'result' => [
					[ 'id' => 'alpha', 'value' => 'first' ],
				],
			],
			[
				'result' => [
					[ 'id' => 'beta', 'value' => 'second' ],
				],
			],
		] );

		$webservice = $this->getWebservice();
		$config = $this->getSqlConfig( 'mysqli' );

		$first = $webservice->retrieve( $config );
		$second = $webservice->retrieve( $config );
		$requests = MockSql::getMockRequests();

		$this->assertSame( [ [ 'id' => 'alpha', 'value' => 'first' ] ], $first->getData() );
		$this->assertSame( [ [ 'id' => 'beta', 'value' => 'second' ] ], $second->getData() );
		$this->assertCount( 2, $requests );
		$this->assertSame( 1, MockSql::getClientCreationCount( 'mysqli' ) );
		$this->assertSame( $requests[0]['connection_id'], $requests[1]['connection_id'] );
	}

	public function testRetryableMysqliFailureReconnectsAndRetriesOnce(): void
	{
		MockSql::primeMockQueries( 'mysqli', [
			[
				'exception' => [
					'message' => 'MySQL server has gone away',
					'code' => 2006,
				],
			],
			[
				'result' => [
					[ 'id' => 'alpha', 'value' => 'recovered' ],
				],
			],
		] );

		$webservice = $this->getWebservice();
		$result = $webservice->retrieve( $this->getSqlConfig( 'mysqli', [ 'key_column' => 'id' ] ) );
		$requests = MockSql::getMockRequests();

		$this->assertSame(
			[
				'alpha' => [ 'id' => 'alpha', 'value' => 'recovered' ],
			],
			$result->getData()
		);
		$this->assertCount( 2, $requests );
		$this->assertSame( 2, MockSql::getClientCreationCount( 'mysqli' ) );
		$this->assertNotSame( $requests[0]['connection_serial'], $requests[1]['connection_serial'] );
	}

	public function testNonRetryableMysqliFailureDoesNotRetry(): void
	{
		MockSql::primeMockQueries( 'mysqli', [
			[
				'exception' => [
					'message' => 'You have an error in your SQL syntax',
					'code' => 1064,
				],
			],
		] );

		$webservice = $this->getWebservice();

		try {
			$webservice->retrieve( $this->getSqlConfig( 'mysqli' ) );
			$this->fail( 'Expected non-retryable mysqli query to throw.' );
		} catch ( ResultException $exception ) {
			$this->assertStringContainsString( 'SQL syntax', $exception->getMessage() );
			$this->assertCount( 1, MockSql::getMockRequests() );
			$this->assertSame( 1, MockSql::getClientCreationCount( 'mysqli' ) );
		}
	}

	public function testRetryablePdoFailureReconnectsAndRetriesOnceForSend(): void
	{
		MockSql::primeMockQueries( 'pdo', [
			[
				'exception' => [
					'type' => 'pdo',
					'message' => 'SQLSTATE[08S01]: Communication link failure: 2006 MySQL server has gone away',
					'code' => 2006,
					'errorInfo' => [ '08S01', 2006, 'MySQL server has gone away' ],
				],
			],
			[
				'result' => true,
			],
		] );

		$webservice = $this->getWebservice();
		$result = $webservice->send( $this->getSqlConfig( 'pdo' ), [ 'payload' => 'value' ] );
		$requests = MockSql::getMockRequests();

		$this->assertTrue( $result->getData() );
		$this->assertCount( 2, $requests );
		$this->assertSame( 2, MockSql::getClientCreationCount( 'pdo' ) );
		$this->assertNotSame( $requests[0]['connection_serial'], $requests[1]['connection_serial'] );
	}

	public function testRetryableFailureStopsAfterSingleRetry(): void
	{
		MockSql::primeMockQueries( 'pdo', [
			[
				'exception' => [
					'type' => 'pdo',
					'message' => 'SQLSTATE[08S01]: Communication link failure: 2006 MySQL server has gone away',
					'code' => 2006,
					'errorInfo' => [ '08S01', 2006, 'MySQL server has gone away' ],
				],
			],
			[
				'exception' => [
					'type' => 'pdo',
					'message' => 'SQLSTATE[08S01]: Communication link failure: 2013 Lost connection to MySQL server during query',
					'code' => 2013,
					'errorInfo' => [ '08S01', 2013, 'Lost connection to MySQL server during query' ],
				],
			],
		] );

		$webservice = $this->getWebservice();

		try {
			$webservice->retrieve( $this->getSqlConfig( 'pdo' ) );
			$this->fail( 'Expected retryable PDO query to fail after the configured retry limit.' );
		} catch ( \PDOException $exception ) {
			$this->assertStringContainsString( 'Lost connection', $exception->getMessage() );
			$this->assertCount( 2, MockSql::getMockRequests() );
			$this->assertSame( 2, MockSql::getClientCreationCount( 'pdo' ) );
		}
	}

	public function testConnectReturnsFailureResultForConnectionError(): void
	{
		MockSql::primeMockConnections( 'mysqli', [
			[
				'exception' => [
					'type' => 'auth',
					'message' => 'Connection refused',
					'code' => 2002,
				],
			],
		] );

		$webservice = $this->getWebservice();
		$result = $webservice->connect( $this->getSqlConfig( 'mysqli' ) );
		$debugInfo = $result->getDebugInfo();

		$this->assertFalse( $result->isSuccess() );
		$this->assertSame( 'Connection refused', $debugInfo['Error']['Error'] );
		$this->assertSame( 0, MockSql::getClientCreationCount( 'mysqli' ) );
	}

	private function getWebservice(): MockSql
	{
		$webservice = WebserviceModel::get( 'MockSql' );
		$this->assertInstanceOf( MockSql::class, $webservice );

		return $webservice;
	}

	private function getSqlConfig( string $driver, array $overrides = [] ): array
	{
		return array_merge(
			[
				'driver' => $driver,
				'host' => 'db.local',
				'database' => 'sync_engine',
				'username' => 'tester',
				'password' => 'secret',
				'query' => 'SELECT 1 AS test_result',
			],
			$overrides
		);
	}
}



