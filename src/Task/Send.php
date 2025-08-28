<?php

namespace SyncEngine\Task;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Task\Abstract\AbstractRequest;
use SyncEngine\Task\Interface\SkipPreviewInterface;
use SyncEngine\Task\Type\RequestTaskType;

class Send extends AbstractRequest implements SkipPreviewInterface
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = RequestTaskType::TYPE;
		$this->icon        = 'task-send';
		$this->name        = $this->trans( 'Send' );
		$this->description = $this->trans( 'Send your data to your specific connection' );
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

	public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
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

		$time_start = microtime( true );

		try {
			if ( ! empty( $connectionConfig['id'] ) ) {
				$connection = ConnectionModel::get( $connectionConfig['id'] );
				$result     = $connection->handleSend( $connectionConfig, $context, $package );
			} else {
				// @todo Custom webservice without Connection?
				$webservice = WebserviceModel::get( $connectionConfig['_class'] );
				$result     = $webservice->send( $connectionConfig, $package );
			}

			// Do not translate for storage.
			$context->addLog( 'Response info', $result );

			if ( ! empty( $config['retrieve'] ) ) {
				return $this->handleResult( $result, (array) $config['retrieve'], $data );
			}

		} catch ( \Throwable $e ) {
			$time_end = microtime( true );
			$info     = [
				'data'          => $package,
				'time_start'    => $time_start,
				'time_end'      => $time_end,
				'time_duration' => $time_end - $time_start,
			];
			$context->addError( $e, $info );
		}

		return $data;
	}
}
