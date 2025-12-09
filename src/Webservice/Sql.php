<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Exception\AuthResultException;
use SyncEngine\Webservice\Exception\ResultException;
use SyncEngine\Webservice\Helper\Result;
use SyncEngine\Webservice\Type\SqlWebserviceType;

class Sql extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = SqlWebserviceType::TYPE;
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

	public function getMysqliConnection( array $config ): \mysqli
	{
		$mysqli = new \mysqli( $config['host'], $config['username'], $config['password'], $config['database'] );

		if ( $mysqli->connect_errno ) {
			throw new AuthResultException( "Failed to connect to MySQL: " . $mysqli->connect_error );
		}

		return $mysqli;
	}

	public function getPdoConnection( array $config, $options = [] ): \PDO
	{
		$pdoConn = new \PDO(
			"mysql:host=" . $config['host'] . ";dbname=" . $config['database'],
			$config['username'],
			$config['password'],
			$options
		);

		return $pdoConn;
	}

	public function connect( array $config ): Result
	{
		try {
			$conn = match ( $config['driver'] ) {
				'mysqli' => $this->getMysqliConnection( $config ),
				default => $this->getPdoConnection( $config, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ] ),
			};

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
		$data = match ( $config['driver'] ) {
			'mysqli' => $this->queryMysqli( $config, true ),
			default => $this->queryPdo( $config, true ),
		};

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
			match ( $config['driver'] ) {
				'mysqli' => $this->queryMysqli( $config, true ),
				default => $this->queryPdo( $config, true ),
			}
		);
	}

	public function queryMysqli( array $config, $retrieve = false )
	{
		$mysqli = $this->getMysqliConnection( $config );

		$mysqli->set_charset( 'utf8' );

		$success = $mysqli->real_query( $config['query'] );

		if ( ! $success ) {
			throw new ResultException( "Failed to execute SQL query: " . $mysqli->error );
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
			$mysqli->close();

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
}
