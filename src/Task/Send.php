<?php

namespace App\Task;

use App\Model\ConnectionModel;
use App\Model\TaskModel;
use App\Service\ExecutionContext;
use App\Service\Webservices;

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
		return [
			'connection' => [
				'label'   => 'Connection',
				'type'    => 'entity',
				'entity'  => 'connection',
				'config'  => 'webservice',
				'actions' => [ 'edit', 'create' ],
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		$connectionConfig = $config['connection'];

		try {
			if ( ! empty( $connectionConfig['id'] ) ) {
				$connection = ConnectionModel::get( $connectionConfig['id'] );
				$result     = $connection->handleSend( $connectionConfig, $data );
			} else {
				// @todo Custom webservice without Connection?
				$webservice = Webservices::getWebservice( $connectionConfig['_class'] );
				$result     = $webservice->send( $connectionConfig, $data );
			}
		} catch ( \Throwable $e ) {
			$context->addError( $e );
		}

		// @todo Option to include result in current dataset?

		// @todo return type?
		return $data;
	}
}
