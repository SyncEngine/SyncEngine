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

	public function getFields(): array
	{
		return $this->getRequestFields();
	}

	public function getRequestFields( $defaults = [] ): array
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
		return ( $config["driver"] == "mysqli" ) ? $this->MySqliQuery( $config ) : $this->PDOQuery( $config );
	}

	public function send( array $config, $data )
	{

		return ( $config["driver"] == "mysqli" ) ? $this->MySqliQuery( $config ) : $this->PDOQuery( $config );
	}

	public function MySqliQuery( array $config )
	{
		$mysqli = new \mysqli( $config['host'], $config['username'], $config['password'], $config['database'] );

		if ( $mysqli->connect_errno ) {
			echo "Failed to connect to MySQL: " . $mysqli->connect_error;
			exit();
		}

		try {
			$mysqli->real_query( $config['query'] );
		} catch ( \Exception $e ) {
			//@todo eror handling
			echo $e;
			exit();
		}
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
		try {
			$conn = new \PDO( "mysql:host=" . $config['host'] . ";dbname=" . $config['database'], $config['username'], $config['password'] );
		} catch ( PDOException $e ) {
			echo "Connection failed: " . $e->getMessage();
		}
		try {
			$result = $conn->query( $config['query'] );
		} catch ( \Exception $e ) {
			//@todo eror handling
			echo $e;
			exit();
		}

		$conn = null;

		return $result;
	}

}
