<?php

namespace SyncEngine\Task;

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
		return array_merge(
			[
				'connection' => [
					'label'   => $this->trans( 'Connection' ),
					'type'    => 'entity',
					'entity'  => 'connection',
					'config'  => 'webservice:retrieve',
					'actions' => [ 'edit', 'create' ],
				],
			],
			$this->getResponseFields(),
		);
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$result = $this->handleRequest( $config, $context, $data );
		$return = $this->handleResult( $result, $config, $data );

		// @todo Option to include in current dataset?
		return new ExecuteData( $return );
	}
}
