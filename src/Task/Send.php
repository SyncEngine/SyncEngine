<?php

namespace SyncEngine\Task;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Abstract\AbstractRequest;
use SyncEngine\Task\Type\RequestTaskType;

class Send extends AbstractRequest
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = RequestTaskType::TYPE;
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
			'retrieve'   => [
				'label'  => $this->trans( 'Retrieve response data' ),
				'type'   => 'switch',
				'nested' => $this->getResponseFields(),
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$connectionConfig = $config['connection'];
		$result           = null;

		try {
			if ( ! empty( $connectionConfig['id'] ) ) {
				$connection = ConnectionModel::get( $connectionConfig['id'] );
				$result     = $connection->handleSend( $connectionConfig, $context, $data->normalize() );
			} else {
				// @todo Custom webservice without Connection?
				$webservice = WebserviceModel::get( $connectionConfig['_class'] );
				$result     = $webservice->send( $connectionConfig, $data->normalize() );
			}

			$context->addLog(
				$this->trans(
					'Response info for Task: {ref}',
					[ 'ref' => $config['_ref'] ]
				),
				$result->getResponse()
			);
		} catch ( \Throwable $e ) {
			$context->addError( $e, $data->get() );
		}

		if ( ! empty( $config['retrieve'] ) ) {
			$return = $this->handleResult( $result, $config['retrieve'], $data );

			return new ExecuteData( $return );
		}

		return $data;
	}
}
