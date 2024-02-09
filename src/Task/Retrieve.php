<?php

namespace SyncEngine\Task;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Abstract\AbstractRequest;

class Retrieve extends AbstractRequest
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'request';
		$this->name        = $this->trans( 'Retrieve' );
		$this->description = $this->trans( 'Retrieve your data from your specific connection' );
	}

	function getFields(): array
	{
		return [
			'connection' => [
				'label'   => $this->trans( 'Connection' ),
				'type'    => 'entity',
				'entity'  => 'connection',
				'config'  => 'webservice:retrieve',
				'actions' => [ 'edit', 'create' ],
			],
			'response' => [
				'label'  => $this->trans( 'Response data' ),
				'fields' => $this->getResponseFields(),
			]
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$connectionConfig = $config['connection'];
		$result           = null;

		try {
			if ( ! empty( $connectionConfig['id'] ) ) {
				$connection = ConnectionModel::get( $connectionConfig['id'] );
				$result     = $connection->handleRetrieve( $connectionConfig, $context, $data->get() );
			} else {
				// @todo Custom webservice without Connection?
				$webservice = WebserviceModel::get( $connectionConfig['_class'] );
				$result     = $webservice->retrieve( $connectionConfig, $data->get() );
			}

			$context->addLog( 'Response info for Task: ' . $config['_ref'], $result->getResponse() );
			if(!empty($result->getInfo()))
			{
				$context->addLog($result->getInfo());
			}
		} catch ( \Throwable $e ) {
			$context->addError( $e, $data->get() );
		}

		$return = $this->handleResult( $result, $config, $data );

		// @todo Option to include in current storage?
		return new ExecuteData( $return );
	}
}
