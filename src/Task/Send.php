<?php

namespace SyncEngine\Task;

use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Abstract\AbstractRequest;

class Send extends AbstractRequest
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
			'retrieve' => [
				'label'  => $this->trans( 'Retrieve response data' ),
				'type'   => 'switch',
				'nested' => $this->getResponseFields(),
			]
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$result = $this->handleRequest( $config, $context, $data );

		if ( ! empty( $config['retrieve'] ) ) {
			$return = $this->handleResult( $result, $config['retrieve'], $data );
			return new ExecuteData( $return );
		}

		return $data;
	}
}
