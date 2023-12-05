<?php

namespace SyncEngine\Task;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\Webservices;

class Send extends TaskModel
{
	public function __construct( private readonly Webservices $webservicesService )
	{
		$this->type        = 'request';
		$this->name        = $this->trans( 'Send' );
		$this->description = $this->trans( 'Send your data to your specific connection' );

		parent::__construct();
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

	public function execute( array $config, ExecutionContext $context, array $data ): array
	{
		$connectionConfig = $config['connection'];

		try {
			if ( ! empty( $connectionConfig['id'] ) ) {
				$connection = ConnectionModel::get( $connectionConfig['id'] );
				$result     = $connection->handleSend( $connectionConfig, $data );
			} else {
				// @todo Custom webservice without Connection?
				$webservice = $this->webservicesService->getWebservice( $connectionConfig['_class'] );
				$result     = $webservice->send( $connectionConfig, $data );
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
