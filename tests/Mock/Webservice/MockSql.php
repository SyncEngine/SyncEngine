<?php

namespace SyncEngine\Tests\Mock\Webservice;

use SyncEngine\Webservice\Exception\AuthResultException;
use SyncEngine\Webservice\Exception\ResultException;
use SyncEngine\Webservice\Sql;

class MockSqlMysqliConnection extends \mysqli
{
	public function __construct(
		public readonly string $ref = '',
		public readonly int $serial = 0,
	) {}
}

class MockSqlPdoConnection extends \PDO
{
	public function __construct(
		public readonly string $ref = '',
		public readonly int $serial = 0,
	) {}
}

class MockSql extends Sql
{
	/**
	 * @var array<string, array<int, array{exception?: mixed}>>
	 */
	protected static array $connectionBehaviors = [
		'mysqli' => [],
		'pdo' => [],
	];

	/**
	 * @var array<string, array<int, array{result?: mixed, exception?: mixed}>>
	 */
	protected static array $queryBehaviors = [
		'mysqli' => [],
		'pdo' => [],
	];

	/**
	 * @var array<int, array{driver: string, retrieve: bool, query: string, connection_id: int, connection_serial: int, data: mixed}>
	 */
	protected static array $requests = [];

	/**
	 * @var array<string, int>
	 */
	protected static array $clientCreationCounts = [
		'mysqli' => 0,
		'pdo' => 0,
	];

	public function __construct()
	{
		parent::__construct();

		$this->icon = 'webservice-sql';
		$this->name = $this->trans( 'SQL Mock (tests)' );
		$this->description = $this->trans( 'Test-only SQL webservice with queued connection and query behaviors.' );
	}

	/**
	 * @param  array<int, array{result?: mixed, exception?: mixed}>  $behaviors
	 */
	public static function primeMockResponses( array $behaviors, string $driver = 'mysqli' ): void
	{
		static::primeMockQueries( $driver, $behaviors );
	}

	/**
	 * @param  array<int, array{exception?: mixed}>  $behaviors
	 */
	public static function primeMockConnections( string $driver, array $behaviors ): void
	{
		static::$connectionBehaviors[ static::normalizeDriver( $driver ) ] = array_values( $behaviors );
	}

	/**
	 * @param  array<int, array{result?: mixed, exception?: mixed}>  $behaviors
	 */
	public static function primeMockQueries( string $driver, array $behaviors ): void
	{
		static::$queryBehaviors[ static::normalizeDriver( $driver ) ] = array_values( $behaviors );
	}

	public static function resetMockState(): void
	{
		static::$connectionBehaviors = [
			'mysqli' => [],
			'pdo' => [],
		];
		static::$queryBehaviors = [
			'mysqli' => [],
			'pdo' => [],
		];
		static::$requests = [];
		static::$clientCreationCounts = [
			'mysqli' => 0,
			'pdo' => 0,
		];
		static::$clients = [];
	}

	/**
	 * @return array<int, array{driver: string, retrieve: bool, query: string, connection_id: int, connection_serial: int, data: mixed}>
	 */
	public static function getMockRequests(): array
	{
		return static::$requests;
	}

	public static function getClientCreationCount( string $driver ): int
	{
		return static::$clientCreationCounts[ static::normalizeDriver( $driver ) ] ?? 0;
	}

	public function getMysqliConnection( array $config ): \mysqli
	{
		$ref = $this->getMockClientReference( array_merge( $config, [ 'driver' => 'mysqli' ] ) );

		if ( $this->fetchClient( $ref ) instanceof \mysqli ) {
			return $this->fetchClient( $ref );
		}

		$behavior = array_shift( static::$connectionBehaviors['mysqli'] );
		if ( is_array( $behavior ) && array_key_exists( 'exception', $behavior ) ) {
			throw $this->buildThrowable( $behavior['exception'], 'Failed to connect to MySQL mock', true );
		}

		static::$clientCreationCounts['mysqli']++;
		$mysqli = new MockSqlMysqliConnection( $ref, static::$clientCreationCounts['mysqli'] );
		$this->cacheClient( $mysqli, $ref );

		return $mysqli;
	}

	public function getPdoConnection( array $config, $options = [] ): \PDO
	{
		$ref = $this->getMockClientReference( array_merge( $config, [ 'driver' => 'pdo' ] ) );

		if ( $this->fetchClient( $ref ) instanceof \PDO ) {
			return $this->fetchClient( $ref );
		}

		$behavior = array_shift( static::$connectionBehaviors['pdo'] );
		if ( is_array( $behavior ) && array_key_exists( 'exception', $behavior ) ) {
			throw $this->buildThrowable( $behavior['exception'], 'Failed to connect to PDO mock', true );
		}

		static::$clientCreationCounts['pdo']++;
		$pdo = new MockSqlPdoConnection( $ref, static::$clientCreationCounts['pdo'] );
		$this->cacheClient( $pdo, $ref );

		return $pdo;
	}

	public function queryMysqli( array $config, $retrieve = false )
	{
		$mysqli = $this->getMysqliConnection( $config );

		static::$requests[] = [
			'driver' => 'mysqli',
			'retrieve' => (bool) $retrieve,
			'query' => (string) ( $config['query'] ?? '' ),
			'connection_id' => spl_object_id( $mysqli ),
			'connection_serial' => $mysqli->serial,
			'data' => null,
		];

		$behavior = array_shift( static::$queryBehaviors['mysqli'] ) ?? [];
		if ( array_key_exists( 'exception', $behavior ) ) {
			throw $this->buildThrowable( $behavior['exception'], 'Mock mysqli query failure' );
		}

		if ( ! $retrieve ) {
			return true;
		}

		return $this->normalizeQueryResult( $behavior['result'] ?? [] );
	}

	public function queryPdo( array $config, $retrieve = false )
	{
		$pdo = $this->getPdoConnection( $config );

		static::$requests[] = [
			'driver' => 'pdo',
			'retrieve' => (bool) $retrieve,
			'query' => (string) ( $config['query'] ?? '' ),
			'connection_id' => spl_object_id( $pdo ),
			'connection_serial' => $pdo->serial,
			'data' => null,
		];

		$behavior = array_shift( static::$queryBehaviors['pdo'] ) ?? [];
		if ( array_key_exists( 'exception', $behavior ) ) {
			throw $this->buildThrowable( $behavior['exception'], 'Mock PDO query failure' );
		}

		if ( ! $retrieve ) {
			return true;
		}

		return $this->normalizeQueryResult( $behavior['result'] ?? [] );
	}

	private static function normalizeDriver( string $driver ): string
	{
		if ( ! in_array( $driver, [ 'mysqli', 'pdo' ], true ) ) {
			throw new \InvalidArgumentException( sprintf( 'Unsupported SQL mock driver "%s"', $driver ) );
		}

		return $driver;
	}

	private function getMockClientReference( array $config ): string
	{
		return sprintf(
			'%s:%s:%s:%s',
			$config['driver'] ?? 'pdo',
			$config['host'] ?? '',
			$config['username'] ?? '',
			$config['database'] ?? ''
		);
	}

	private function buildThrowable( mixed $definition, string $fallbackMessage, bool $auth = false ): \Throwable
	{
		if ( $definition instanceof \Throwable ) {
			return $definition;
		}

		if ( is_string( $definition ) ) {
			return $auth
				? new AuthResultException( $definition )
				: new ResultException( $definition );
		}

		$definition = is_array( $definition ) ? $definition : [];
		$message = (string) ( $definition['message'] ?? $fallbackMessage );
		$code = (int) ( $definition['code'] ?? 200 );
		$errorInfo = $definition['errorInfo'] ?? null;
		$type = $definition['type'] ?? null;

		if ( 'pdo' === $type || \PDOException::class === ( $definition['class'] ?? null ) ) {
			$exception = new \PDOException( $message, $code );
			if ( is_array( $errorInfo ) ) {
				$exception->errorInfo = $errorInfo;
			}

			return $exception;
		}

		if ( $auth || 'auth' === $type || AuthResultException::class === ( $definition['class'] ?? null ) ) {
			return new AuthResultException( $message, $definition['debugInfo'] ?? [], $code );
		}

		return new ResultException( $message, $definition['debugInfo'] ?? [], $code );
	}

	private function normalizeQueryResult( mixed $result ): array
	{
		if ( null === $result ) {
			return [];
		}

		if ( is_array( $result ) ) {
			return $result;
		}

		return [ $result ];
	}
}

