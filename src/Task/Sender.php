<?php

namespace App\Task;

use App\Component\AutomationContext;
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
		    if ( isset( $config['webservice']['_class'] ) && isset( $webservices[ $config['webservice']['_class'] ] ) ) {
			    $webservice = $webservices[ $config['webservice']['_class'] ];

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

		$webservice = WebserviceService::getWebservice( $connectionConfig['_class'] );

		// @todo Option to include in current dataset?
		$result = $webservice->send( $connectionConfig, $data );

		// @todo return type?
		return $data;
	}
}
