<?php

namespace SyncEngine\Task;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Task\Abstract\AbstractRequest;
use SyncEngine\Task\Type\RequestTaskType;

class Retrieve extends AbstractRequest
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = RequestTaskType::TYPE;
		$this->icon        = 'task-retrieve';
		$this->name        = $this->trans( 'Retrieve' );
		$this->description = $this->trans( 'Retrieve your data from your specific connection' );
	}

	function getFields(): array
	{
		return [
			'key'         => [
				'label'    => $this->trans( 'Key / Column name' ),
				'help'        => [
					$this->trans( 'The data column name to pass to the webservice' ),
					$this->trans( 'Nested keys are supported: {example}', [ 'example' => 'key.nested_key', ] ),
					$this->trans( 'Leave empty for root' ),
				],
				'type'     => 'text', // @todo Column/Key selection field type.
				'taggable' => true,
			],
			'connection' => [
				'label'   => $this->trans( 'Connection' ),
				'type'    => 'entity',
				'entity'  => 'connection',
				'config'  => 'webservice:retrieve',
				'actions' => [ 'edit', 'create' ],
			],
			'response'   => [
				'label'  => $this->trans( 'Response data' ),
				'nested' => $this->getResponseFields(),
			],
		];
	}

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$connectionConfig = $config['connection'];
		$result           = null;

		$key     = $config['key'] ?? null;
		$package = $data->get( $key );

		if ( $key && empty( $package ) ) {
			// Only log error if a key was defined.
			$context->addLog( $this->trans( 'Data not found: {key}', [ 'key' => $key ] ), [ 'data' => $data ] );
		} elseif ( ! is_scalar( $package ) ) {
			$package = $data->normalize( $package );
		}

		try {
			if ( ! empty( $connectionConfig['id'] ) ) {
				$connection = ConnectionModel::get( $connectionConfig['id'] );
				$result     = $connection->handleRetrieve( $connectionConfig, $context, $package );
			} else {
				// @todo Custom webservice without Connection?
				$webservice = WebserviceModel::get( $connectionConfig['_class'] );
				$result     = $webservice->retrieve( $connectionConfig, $package );
			}

			// Do not translate for storage.
			$context->addLog( 'Response info', $result );
		} catch ( \Throwable $e ) {
			$context->addError( $e, [ 'data' => $package ] );
		}

		$responseConfig = $config['response'] ?? [];

		$return = $this->handleResult( $result, (array) $responseConfig, $data );

		// @todo Option to include in current storage?
		return ExecuteData::create( $return );
	}
}
