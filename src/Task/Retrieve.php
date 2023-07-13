<?php

namespace App\Task;

use App\Component\ExecutionContext;
use App\Model\TaskModel;
use App\Service\ConnectionService;
use App\Service\WebserviceService;

class Retrieve extends TaskModel
{
	public function __construct()
	{
		$this->type = 'request';
		$this->name = 'Retrieve';
		$this->description = 'Retrieve your data from your specific connection';

		parent::__construct();
	}

	function getFields(): array
	{
		$webservices = WebserviceService::getWebservices();
		$connections = ConnectionService::getConnections();

		$connectionChoices = [];
		$connectionFields = [];
		foreach ( $connections as $connection ) {
			$config = $connection->getConfig();
			if ( isset( $config['webservice']['_class'] ) && isset( $webservices[ $config['webservice']['_class'] ] ) ) {
				$webservice = $webservices[ $config['webservice']['_class'] ];

				$connectionChoices[ $connection->getId() ] = $connection->getName();
				$connectionFields[ $connection->getId() ] = $webservice->getFields();
			}
		}

		return [
			'connection' => [
				'type'    => 'entity',
				'entity'  => 'connection',
				'choices' => $connectionChoices,
				'config'  => $connectionFields,
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		$connectionConfig = $config['connection'];

		if ( ! empty( $connectionConfig['id'] ) ) {
			$connection = ConnectionService::getConnection( $connectionConfig['id'] );
			$result = $connection->handleRetrieve( $connectionConfig );
		} else {
			// @todo Custom webservice without Connection?
			$webservice = WebserviceService::getWebservice( $connectionConfig['_class'] );
			$result = $webservice->retrieve( $connectionConfig );
		}

		if(is_string($result) or $result === NULL){
			$result = ['response' => $result];
		}

		// @todo Option to include in current dataset?
		return $result;
	}
}
