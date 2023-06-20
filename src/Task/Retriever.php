<?php

namespace App\Task;

use App\Component\AutomationContext;
use App\Model\TaskModel;
use App\Service\ConnectionService;
use App\Service\WebserviceService;

class Retriever extends TaskModel
{
	public function __construct()
	{
		$this->type = 'retriever';
		$this->name = 'Retriever';
		$this->description = 'Retrieve your data from your specific connection';

		parent::__construct();
	}

	function getFields(): array
	{
		$webservices = WebserviceService::getWebservices();
		$connections = ConnectionService::getConnections();

		$connectionChoices = [];
		$connectionFields = [];
		foreach ( $connections as $connection ){
			$config = $connection->getConfig();
			if ( isset( $config['webservice']['type'] ) && isset( $webservices[ $config['webservice']['type'] ] ) ) {
				$webservice = $webservices[ $config['webservice']['type'] ];

				$connectionChoices[ $connection->getId() ] = $connection->getName();
				$connectionFields[ $connection->getId() ] = $webservice->getFields();
			}
		}

		return [
			'connection' => [
				'type'      => 'entity',
				'choices'   => $connectionChoices,
				'fields'    => $connectionFields,
			],
		];
	}

	function execute( array $config, AutomationContext $context, $data )
	{
		$connectionConfig = $config['connection'];

		if ( ! empty( $connectionConfig['id'] ) ) {
			$connection = ConnectionService::getConnection( $connectionConfig['id'] );
			// @todo Connection config handler.
			$connectionConfig = array_merge( $connection->getConfig()['webservice'] ?? [], $connectionConfig );
		}

		$webservice = WebserviceService::getWebservice( $connectionConfig['_type'] );

		// @todo Option to include in current dataset?
		return $webservice->retrieve( $connectionConfig );
	}
}
