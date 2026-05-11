<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Exception\AuthResultException;
use SyncEngine\Webservice\Exception\ResultException;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Trait\Client;
use SyncEngine\Webservice\Type\SqlWebserviceType;

class Sql extends WebserviceModel
{
	use Client;

	public function __construct()
	{
		parent::__construct();

		$this->type        = SqlWebserviceType::TYPE;
		$this->icon        = 'webservice-sql';
		$this->name        = $this->trans( 'SQL' );
		$this->description = $this->trans( 'Connect to a SQL server' );
	}

	public function getAuthFields(): array
	{
		return [
			'host'     => [
				'label' => $this->trans( 'Host' ),
				'type'  => 'text',
			],
			'driver'   => [
				'label'   => $this->trans( 'Select database driver' ),
				'type'    => 'select',
				'choices' => [
					'mysqli' => $this->trans( 'MySQLi' ),
					'pdo'    => $this->trans( 'PDO' ),
				],
			],
			'database' => [
				'label' => $this->trans( 'Database' ),
				'type'  => 'database',
			],
			'username' => [
				'label' => $this->trans( 'Username' ),
				'type'  => 'secret',
			],
			'password' => [
				'label' => $this->trans( 'Password' ),
				'type'  => 'secret',
			],
			'port'     => [
				'label'   => $this->trans( 'Port' ),
				'type'    => 'number',
				'help'    => $this->trans(
					'Aurora/MySQL/MariaDB: 3306 | PostgreSQL: 5431-5432 | SQL Server: 1433-1434'
				),
				'default' => 3306,
			],
		];
	}

	public function getFields( array $defaults = [] ): array
	{
		return $this->getRequestFields( $defaults );
	}

	public function getRequestFields( array $defaults = [] ): array
	{
		return [
			'query' => [
				'label' => $this->trans( 'Query' ),
				'type'  => 'code',
			],
		];
	}

	public function getRetrieveFields( array $defaults = [] ): array
	{
		return array_merge(
			parent::getRetrieveFields( $defaults ),
			[
				'fetch'      => [
					'label'   => $this->trans( 'Fetch method' ),
					'type'    => 'select',
					'choices' => [
						''     => $this->trans( 'Associated array' ),
						'pair' => $this->trans( 'Key => Value pairs' ),
					],
				],
				'key_column' => [
					'label'      => $this->trans( 'Key column' ),
					'help'       => $this->trans( 'Choose the key you want to use as the row key' ),
					'type'       => 'text',
					'conditions' => [
						'fetch' => [ 'operator' => 'empty' ],
					],
				],
			]
		);
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	private function getClientReference( array $config ): string
	{
		$driver = $config['driver'] ?? 'pdo';

		return sprintf(
			'%s:%s:%s:%s',
			$driver,
			$config['host'] ?? '',
			$config['username'] ?? '',
			$config['database'] ?? ''
		);
	}

	public function getMysqliConnection( array $config ): \mysqli
	{
		$ref = $this->getClientReference( array_merge( $config, [ 'driver' => 'mysqli' ] ) );

		if ( $this->fetchClient( $ref ) instanceof \mysqli ) {
			return $this->fetchClient( $ref );
		}

		$mysqli = new \mysqli( $config['host'], $config['username'], $config['password'], $config['database'] );

		if ( $mysqli->connect_errno ) {
			throw new AuthResultException( "Failed to connect to MySQL: " . $mysqli->connect_error );
		}

		$this->cacheClient( $mysqli, $ref );

		return $mysqli;
	}

	public function getPdoConnection( array $config, $options = [] ): \PDO
	{
		$ref = $this->getClientReference( array_merge( $config, [ 'driver' => 'pdo' ] ) );

		if ( $this->fetchClient( $ref ) instanceof \PDO ) {
			return $this->fetchClient( $ref );
		}

		$pdoConn = new \PDO(
			"mysql:host=" . $config['host'] . ";dbname=" . $config['database'],
			$config['username'],
			$config['password'],
			$options
		);

		$this->cacheClient( $pdoConn, $ref );

		return $pdoConn;
	}

	public function connect( array $config ): Result
	{
		try {
			$conn = $this->getClient( $config );

			return new Result(
				true, true, [
					    'Message' => $this->trans(
						    'Successfully connected to {host}',
						    [ 'host' => $this->getRequestUrl( $config ) ]
					    ),
					    'Config'  => $config,
				    ]
			);
		} catch ( \Exception $e ) {
			return new Result(
				false, false, [
					     'Error'  => [
						     'Message' => $this->trans(
							     'Could not connect to {host}',
							     [ 'host' => $this->getRequestUrl( $config ) ]
						     ),
						     'Error'   => $e->getMessage(),
					     ],
					     'Config' => $config,
				     ]
			);
		}
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$data = $this->query( $config, $data, true );

		if ( ! empty( $config['key_column'] ) ) {
			$key = $config['key_column'];
			if ( ! isset( $data[0][ $key ] ) ) {
				throw new ResultException( 'Key column not found in response' );
			}
			$data = array_combine( array_column( $data, $key ), $data );
		}

		return new Result( $data );
	}

	public function send( array $config, $data ): Result
	{
		return new Result(
			$this->query( $config, $data, false )
		);
	}

	public function query( array $config, $data = null, $retrieve = true ): true|array
	{
		$maxRetries = 1;
		$attempt    = 0;

		while ( true ) {
			try {
				return match ( $config['driver'] ) {
					'mysqli' => $this->queryMysqli( $config, $retrieve ),
					default => $this->queryPdo( $config, $retrieve ),
				};
			} catch ( \Throwable $e ) {
				$driver = $config['driver'] ?? 'pdo';

				if ( $attempt >= $maxRetries || ! $this->isRetryableConnectionError( $e, $driver ) ) {
					throw $e;
				}

				$this->forgetClient( $this->getClientReference( $config ) );
				$attempt++;
				usleep( 200000 );
			}
		}
	}

	private function isRetryableConnectionError( \Throwable $exception, string $driver ): bool
	{
		$message = strtolower( $exception->getMessage() );
		$code    = (int) $exception->getCode();

		$retryableCodes = [ 2002, 2006, 2013, 2055 ];
		if ( in_array( $code, $retryableCodes, true ) ) {
			return true;
		}

		if ( $exception instanceof \PDOException ) {
			$sqlState = $exception->errorInfo[0] ?? '';
			if ( is_string( $sqlState ) && str_starts_with( $sqlState, '08' ) ) {
				return true;
			}
		}

		$retryableFragments = [
			'server has gone away',
			'lost connection',
			'connection refused',
			'no connection to the server',
			'error while sending',
			'is dead or not enabled',
			'broken pipe',
			'connection reset by peer',
		];

		foreach ( $retryableFragments as $fragment ) {
			if ( str_contains( $message, $fragment ) ) {
				return true;
			}
		}

		if ( $driver === 'pdo' && str_contains( $message, 'sqlstate[08' ) ) {
			return true;
		}

		return false;
	}

	public function queryMysqli( array $config, $retrieve = false )
	{
		$mysqli = $this->getMysqliConnection( $config );

		$mysqli->set_charset( 'utf8' );

		$success = $mysqli->real_query( $config['query'] );

		if ( ! $success ) {
			throw new ResultException( "Failed to execute SQL query: " . $mysqli->error, $mysqli->errno );
		}

		if ( ! $retrieve ) {
			return true;
		}

		if ( $mysqli->field_count ) {
			$result  = $mysqli->store_result();
			$content = [];

			switch ( $config['fetch'] ?? '' ) {
				case 'pair':
					while ( $row = $result->fetch_row() ) {
						if ( isset( $row[0] ) ) {
							$content[ $row[0] ] = $row[1] ?? null;
						}
					}
				break;
				default:
					while ( $row = $result->fetch_assoc() ) {
						$content[] = $row;
					}
				break;
			}

			$result->free_result();
			//$mysqli->close();

			return $content;
		} else {
			return [];
		}
	}

	public function queryPdo( array $config, $retrieve = false )
	{
		$pdoConn = $this->getPdoConnection( $config );

		$pdoConn->exec( 'set names utf8' );

		$pdo = $pdoConn->query( $config['query'] );

		if ( ! $pdo ) {
			throw new ResultException( "Failed to execute SQL query: " . $pdoConn->errorInfo()[2] );
		}

		if ( ! $retrieve ) {
			return true;
		}

		// Close connection.
		$pdoConn = null;

		switch ( $config['fetch'] ?? '' ) {
			case 'pair':
				return $pdo->fetchAll( \PDO::FETCH_KEY_PAIR );
			default:
				return $pdo->fetchAll( \PDO::FETCH_ASSOC );
		}
	}

	public function getClient( array $config ): \PDO|\mysqli
	{
		return match ( $config['driver'] ) {
			'mysqli' => $this->getMysqliConnection( $config ),
			default => $this->getPdoConnection( $config, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ] ),
		};
	}
}
