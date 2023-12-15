<?php

namespace SyncEngine\Task;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\Webservices;

class Send extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'request';
		$this->name        = $this->trans( 'Send' );
		$this->description = $this->trans( 'Send your data to your specific connection' );
	}

	function getFields(): array
	{
		return [
			'connection' => [
				'label'   => $this->trans( 'Connection' ),
				'type'    => 'entity',
				'entity'  => 'connection',
				'config'  => 'webservice:send',
				'actions' => [ 'edit', 'create' ],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$connectionConfig = $config['connection'];

		try {
			if ( ! empty( $connectionConfig['id'] ) ) {
				$connection = ConnectionModel::get( $connectionConfig['id'] );
				$result     = $connection->handleSend( $connectionConfig, $data->get() );
			} else {
				// @todo Custom webservice without Connection?
				$webservice = WebserviceModel::get( $connectionConfig['_class'] );
				$result     = $webservice->send( $connectionConfig, $data->get() );
			}

			$context->addLog( 'Response info for Task: ' . $config['_ref'], $result->getInfo() );
		} catch ( \Throwable $e ) {
			$context->addError( $e );
		}

		// @todo Option to include result in current dataset?

		// @todo return type?
		return $data;
	}
}
