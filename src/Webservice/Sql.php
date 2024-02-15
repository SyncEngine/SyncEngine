<?php

namespace SyncEngine\Webservice;

use SyncEngine\Model\WebserviceModel;
use SyncEngine\Webservice\Helper\Result;

class Sql extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'sql';
		$this->name        = $this->trans( 'SQL',[],"sql+intl-icu");
		$this->description = $this->trans( 'description',[],"sql+intl-icu");
	}

	public function getAuthFields(): array
	{
		return [
			'host'     => [
				'label' => $this->trans( 'Host',[],"sql+intl-icu" ),
				'type'  => 'text',
			],
			'driver'   => [
				'label'   => $this->trans( 'Select_db_driver',[],"sql+intl-icu" ),
				'type'    => 'select',
				'choices' => [
					'mysqli' => $this->trans( 'MySQLi',[],"sql+intl-icu" ),
					'pdo'    => $this->trans( 'PDO',[],"sql+intl-icu" ),
				],
			],
			'database' => [
				'label' => $this->trans( 'Database',[],"sql+intl-icu" ),
				'type'  => 'database',
			],
			'username' => [
				'label' => $this->trans( 'Username',[],"sql+intl-icu" ),
				'type'  => 'secret',
			],
			'password' => [
				'label' => $this->trans( 'Password',[],"sql+intl-icu" ),
				'type'  => 'secret',
			],
			'port'     => [
				'label'   => $this->trans( 'Port',[],"sql+intl-icu" ),
				'type'    => 'number',
				'help'    => $this->trans( 'Port_help',[],"sql+intl-icu" ),
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
				'label' => $this->trans( 'Query',[],"sql+intl-icu" ),
				'type'  => 'code',
			],
		];
	}

	public function getRetrieveFields( array $defaults = [] ): array
	{
		return array_merge( parent::getRetrieveFields( $defaults ), [
				'fetch'      => [
					'label'   => $this->trans( 'Fetch_method',[],"sql+intl-icu" ),
					'type'    => 'select',
					'choices' => [
						''     => $this->trans( 'Associated_array',[],"sql+intl-icu" ),
						'pair' => $this->trans( 'Key_value_pairs',[],"sql+intl-icu" ),
					],
				],
				'key_column' => [
					'label'      => $this->trans( 'Key_column',[],"sql+intl-icu" ),
					'help'       => $this->trans( 'Choose_key',[],"sql+intl-icu" ),
					'type'       => 'text',
					'conditions' => [
						'fetch' => '',
					],
				],
			] );
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	public function retrieve( array $config, $data = null ): Result
	{
		$data = ( 'mysqli' === $config['driver'] ) ? $this->MySqliQuery( $config, true ) : $this->PDOQuery( $config, true );
		if ( ! empty( $config['key_column'] ) ) {
			$key = $config['key_column'];
			if ( ! isset( $data[0][ $key ] ) ) {
				throw new \Exception( 'Key column not found in response' );
			}
			$data = array_combine( array_column( $data, $key ), $data );
		}

		return new Result( $data );
	}

	public function send( array $config, $data ): Result
	{
		return new Result( ( 'mysqli' === $config['driver'] ) ? $this->MySqliQuery( $config ) : $this->PDOQuery( $config ) );
	}

	public function MySqliQuery( array $config, $retrieve = false )
	{
		$mysqli = new \mysqli( $config['host'], $config['username'], $config['password'], $config['database'] );

		if ( $mysqli->connect_errno ) {
			throw new \Exception( "Failed to connect to MySQL: " . $mysqli->connect_error );
		}

		$mysqli->set_charset( 'utf8' );

		$success = $mysqli->real_query( $config['query'] );

		if ( ! $success ) {
			throw new \Exception( "Failed to execute SQL query: " . $mysqli->error );
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

	public function PDOQuery( array $config, $retrieve = false )
	{
		$pdoConn = new \PDO(
			"mysql:host=" . $config['host'] . ";dbname=" . $config['database'],
			$config['username'], $config['password']
		);

		$pdoConn->exec( 'set names utf8' );

		$pdo = $pdoConn->query( $config['query'] );

		if ( ! $pdo ) {
			throw new \Exception( "Failed to execute SQL query: " . $pdoConn->errorInfo()[2] );
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
