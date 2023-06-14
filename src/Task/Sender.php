<?php

namespace App\Task;

use App\Model\TaskModel;
use App\Service\ConnectionService;
use App\Service\WebserviceService;

class Sender extends TaskModel
{
	public function __construct()
	{
		$this->type = 'sender';
		$this->name = 'Sender';
		$this->description = 'Send your data to your specific connection';

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

	function execute( array $config, $data ): array
	{
		$connectionConfig = $config['connection'];

		if ( ! empty( $connectionConfig['id'] ) ) {
			// @todo Connection service.
			$connection = ConnectionService::getConnection( $connectionConfig['id'] );
			$connectionConfig = array_merge( $connection->getConfig(), $connectionConfig );
		}

		$webservice = WebserviceService::getWebservice( $connectionConfig['webservice'] );

		// @todo Option to include in current dataset?
		$result = $webservice->send( $connectionConfig, $data );

		// @todo return type?
		return $data;
	}
}
