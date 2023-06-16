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
			if ( isset( $config['webservice'] ) && isset( $webservices[ $config['webservice'] ] ) ) {
				$webservice = $webservices[ $config['webservice'] ];

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

	function execute( array $config, array $data, AutomationContext $context ): array
	{
		$connectionConfig = $config['connection'];

		if ( ! empty( $connectionConfig['id'] ) ) {
			// @todo Connection service.
			$connection = ConnectionService::getConnection( $connectionConfig['id'] );
			$connectionConfig = array_merge( $connection->getConfig(), $connectionConfig );
		}

		$webservice = WebserviceService::getWebservice( $connectionConfig['webservice'] );

		// @todo Option to include in current dataset?
		return $webservice->retrieve( $connectionConfig );
	}
}
