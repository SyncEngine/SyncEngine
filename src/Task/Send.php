<?php

namespace App\Task;

use App\Component\ExecutionContext;
use App\Model\ConnectionModel;
use App\Model\TaskModel;
use App\Service\ConnectionService;
use App\Service\WebserviceService;

class Send extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'request';
		$this->name        = 'Send';
		$this->description = 'Send your data to your specific connection';

		parent::__construct();
	}

	function getFields(): array
	{
		$webservices = WebserviceService::getWebservices();
		$connections = ConnectionModel::getAll();

		$connectionChoices = [];
		$connectionFields  = [];
		foreach ( $connections as $connection ) {
			$config = $connection->getConfig();
			if ( isset( $config['webservice']['_class'] )
			     && isset( $webservices[ $config['webservice']['_class'] ] ) ) {
				$webservice = $webservices[ $config['webservice']['_class'] ];

				$connectionChoices[ $connection->getId() ] = $connection->getName();
				$connectionFields[ $connection->getId() ]  = $webservice->getFields();
			}
		}

		return [
			'connection' => [
				'label'   => 'Connection',
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
			$connection = ConnectionModel::get( $connectionConfig['id'] );
			$result     = $connection->handleSend( $connectionConfig, $data );
		} else {
			// @todo Custom webservice without Connection?
			$webservice = WebserviceService::getWebservice( $connectionConfig['_class'] );
			$result     = $webservice->send( $connectionConfig, $data );
		}

		// @todo Option to include result in current dataset?

		// @todo return type?
		return $data;
	}
}
