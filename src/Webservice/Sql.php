<?php

namespace App\Webservice;

use App\Model\WebserviceModel;

class Sql extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'sql';
		$this->name        = 'SQL';
		$this->description = 'Connect to an SQL server';
	}

	public function getAuthFields(): array
	{
		return [
			'host'     => [
				'label' => 'Host',
				'type'  => 'text',
			],
			'driver'   => [
				'label'   => 'Select database driver',
				'type'    => 'select',
				'choices' => [
					'mysqli' => 'MySQLi',
					'pdo'    => 'PDO',
				],
			],
			'database' => [
				'label' => 'Database',
				'type'  => 'database',
			],
			'username' => [
				'label' => 'Username',
				'type'  => 'text',
			],
			'password' => [
				'label' => 'Password',
				'type'  => 'password',
			],
			'port'     => [
				'label'   => 'Port',
				'type'    => 'number',
				'help'    => 'Aurora/MySQL/MariaDB: 3306 | PostgreSQL: 5431-5432 | SQL Server: 1433-1434',
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
				'label' => 'Query',
				'type'  => 'text',
			],
		];
	}

	public function getRequestUrl( array $config ): string
	{
		return $config['host'] ?? '';
	}

	public function retrieve( array $config )
	{
		return ( 'mysqli' === $config['driver'] ) ? $this->MySqliQuery( $config ) : $this->PDOQuery( $config );
	}

	public function send( array $config, $data )
	{
		return ( 'mysqli' === $config['driver'] ) ? $this->MySqliQuery( $config ) : $this->PDOQuery( $config );
	}

	public function MySqliQuery( array $config )
	{
		$mysqli = new \mysqli( $config['host'], $config['username'], $config['password'], $config['database'] );

		if ( $mysqli->connect_errno ) {
			throw new \Exception( "Failed to connect to MySQL: " . $mysqli->connect_error );
		}

		$mysqli->set_charset( 'utf8' );
		$mysqli->real_query( $config['query'] );

		if ( $mysqli->field_count ) {
			$result  = $mysqli->store_result();
			$content = $result->fetch_all();
			$result->free_result();
			$mysqli->close();

			return $content;
		} else {
			return [];
		}
	}

	public function PDOQuery( array $config )
	{
		$conn = new \PDO( "mysql:host=" . $config['host'] . ";dbname=" . $config['database'], $config['username'], $config['password'] );

		$conn->exec( 'set names utf8' );

		$result = $conn->query( $config['query'] );

		$conn = null;

		return $result->fetchAll();
	}
}
